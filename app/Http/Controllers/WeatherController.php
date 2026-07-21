<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WeatherController extends Controller
{
    /**
     * Show the weather page and optionally search for a city.
     */
    public function show(Request $request): Response
    {
        // The city comes from the query string, such as ?city=Gulu.
        $city = trim((string) $request->query('city', ''));
        $weather = null;
        $error = null;

        if ($city !== '') {
            try {
                // First request: find the city's coordinates.
                $geoResponse = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
                    'name' => $city,
                    'count' => 1,
                    'language' => 'en',
                    'format' => 'json',
                ]);

                $geoResponse->throw();
                $geoData = $geoResponse->json('results.0');

                if ($geoData) {
                    // Second request: fetch the weather based on the coordinates.
                    $forecastResponse = Http::get('https://api.open-meteo.com/v1/forecast', [
                        'latitude' => $geoData['latitude'],
                        'longitude' => $geoData['longitude'],
                        'current' => 'temperature_2m,weather_code',
                        'timezone' => 'auto',
                    ]);

                    $forecastResponse->throw();
                    $forecastData = $forecastResponse->json('current');

                    // Shape the API data into something simple for the Vue page.
                    $weather = [
                        'city' => $geoData['name'],
                        'country' => $geoData['country'],
                        'temperature' => $forecastData['temperature_2m'] ?? null,
                        'condition' => $this->weatherCodeToText($forecastData['weather_code'] ?? null),
                    ];
                } else {
                    $error = 'We could not find that city. Please try another name.';
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
