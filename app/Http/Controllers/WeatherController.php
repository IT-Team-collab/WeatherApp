<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WeatherController extends Controller
{
    /**
     * Show the weather page and optionally search for a city or coordinates.
     */
    public function show(Request $request): Response
    {
        $city = trim((string) $request->query('city', ''));
        $lat = trim((string) $request->query('lat', ''));
        $lon = trim((string) $request->query('lon', ''));
        
        $weather = null;
        $error = null;
        
        $latitude = null;
        $longitude = null;
        $cityName = null;
        $countryName = null;

        if ($city !== '' || ($lat !== '' && $lon !== '')) {
            try {
                // If coordinates are provided, do reverse geocoding
                if ($lat !== '' && $lon !== '') {
                    $latitude = (float) $lat;
                    $longitude = (float) $lon;
                    
                    $geoResponse = Http::get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'localityLanguage' => 'en',
                    ]);
                    $geoData = $geoResponse->json();
                    
                    $cityName = $geoData['city'] ?? $geoData['locality'] ?? 'Current Location';
                    $countryName = $geoData['countryName'] ?? '';
                    
                    // Set $city so the search bar shows the found name
                    if ($city === '') {
                        $city = $cityName;
                    }
                } else {
                    // Find coordinates by city name
                    $geoResponse = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
                        'name' => $city,
                        'count' => 1,
                        'language' => 'en',
                        'format' => 'json',
                    ]);

                    $geoResponse->throw();
                    $geoData = $geoResponse->json('results.0');

                    if ($geoData) {
                        $latitude = $geoData['latitude'];
                        $longitude = $geoData['longitude'];
                        $cityName = $geoData['name'];
                        $countryName = $geoData['country'] ?? '';
                    } else {
                        $error = 'We could not find that city. Please try another name.';
                    }
                }

                if ($latitude !== null && $longitude !== null) {
                    // Concurrently fetch forecast and AQI
                    $responses = Http::pool(function (Pool $pool) use ($latitude, $longitude) {
                        return [
                            $pool->as('forecast')->get('https://api.open-meteo.com/v1/forecast', [
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                                'current' => 'temperature_2m,relativehumidity_2m,dewpoint_2m,rain,showers,vapour_pressure_deficit,windspeed_180m,weather_code',
                                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                                'timezone' => 'auto',
                            ]),
                            $pool->as('aqi')->get('https://air-quality-api.open-meteo.com/v1/air-quality', [
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                                'current' => 'us_aqi',
                                'timezone' => 'auto',
                            ]),
                        ];
                    });

                    $forecastResponse = $responses['forecast'];
                    $aqiResponse = $responses['aqi'];

                    $forecastResponse->throw();
                    $aqiResponse->throw();

                    $forecastData = $forecastResponse->json('current');
                    $dailyData = $forecastResponse->json('daily');
                    $timezone = $forecastResponse->json('timezone');
                    $aqiData = $aqiResponse->json('current');

                    // Shape daily forecast data
                    $dailyForecast = [];
                    if ($dailyData) {
                        foreach ($dailyData['time'] as $index => $time) {
                            $dailyForecast[] = [
                                'date' => $time,
                                'max_temp' => $dailyData['temperature_2m_max'][$index] ?? null,
                                'min_temp' => $dailyData['temperature_2m_min'][$index] ?? null,
                                'condition' => $this->weatherCodeToText($dailyData['weather_code'][$index] ?? null),
                                'weather_code' => $dailyData['weather_code'][$index] ?? null,
                            ];
                        }
                    }

                    // Shape the API data for the Vue page
                    $weather = [
                        'city' => $cityName,
                        'country' => $countryName,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'timezone' => $timezone,
                        'temperature' => $forecastData['temperature_2m'] ?? null,
                        'relative_humidity' => $forecastData['relativehumidity_2m'] ?? null,
                        'dewpoint' => $forecastData['dewpoint_2m'] ?? null,
                        'rain' => $forecastData['rain'] ?? null,
                        'showers' => $forecastData['showers'] ?? null,
                        'vapour_pressure_deficit' => $forecastData['vapour_pressure_deficit'] ?? null,
                        'windspeed_180m' => $forecastData['windspeed_180m'] ?? null,
                        'condition' => $this->weatherCodeToText($forecastData['weather_code'] ?? null),
                        'weather_code' => $forecastData['weather_code'] ?? null,
                        'aqi' => $aqiData['us_aqi'] ?? null,
                        'daily' => $dailyForecast,
                    ];
                }
            } catch (Throwable $exception) {
                // Keep the page friendly if the upstream API fails.
                $error = 'Weather is temporarily unavailable. Please try again in a moment.';
            }
        }

        return Inertia::render('Weather', [
            'city' => $city,
            'weather' => $weather,
            'error' => $error,
        ]);
    }

    /**
     * Convert the weather code from the API into a readable label.
     */
    private function weatherCodeToText(?int $code): string
    {
        return match ($code) {
            0 => 'Clear sky',
            1, 2, 3 => 'Mostly clear',
            45, 48 => 'Foggy',
            51, 53, 55 => 'Drizzle',
            61, 63, 65 => 'Rain',
            71, 73, 75 => 'Snow',
            95, 96, 99 => 'Thunderstorm',
            default => 'Unknown',
        };
    }
}
