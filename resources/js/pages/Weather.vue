<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
// import { weather } from '@/routes';

// These props come from the Laravel controller.
const props = defineProps<{
    city: string;
    weather: {
        city: string;
        country: string;
        latitude: number;
        longitude: number;
        timezone: string;
        temperature: number | null;
        relative_humidity: number | null;
        dewpoint: number | null;
        rain: number | null;
        showers: number | null;
        vapour_pressure_deficit: number | null;
        windspeed_180m: number | null;
        condition: string;
        weather_code: number | null;
        aqi: number | null;
        daily: {
            date: string;
            max_temp: number | null;
            min_temp: number | null;
            condition: string;
            weather_code: number | null;
        }[];
    } | null;
    error: string | null;
}>();

// This keeps the form simple: the query string is sent back to Laravel.
const searchCity = (event: Event) => {
    const form = event.target as HTMLFormElement;
    const city = new FormData(form).get('city')?.toString().trim();

    if (city) {
        window.location.href = `/weather?city=${encodeURIComponent(city)}`;
    }
};
const recentSearches = ref<string[]>([]);

onMounted(() => {
    const saved = localStorage.getItem('recent_searches');
    if (saved) {
        recentSearches.value = JSON.parse(saved);
    }

    if (props.weather?.city && props.weather?.city !== 'Current Location') {
        const searches = new Set([props.weather.city, ...recentSearches.value]);
        recentSearches.value = Array.from(searches).slice(0, 5); // Keep top 5
        localStorage.setItem('recent_searches', JSON.stringify(recentSearches.value));
    }
});

const useMyLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            window.location.href = `/weather?lat=${lat}&lon=${lon}`;
        }, () => {
            alert('Unable to retrieve your location');
        });
    } else {
        alert('Geolocation is not supported by your browser');
    }
};

const localTime = computed(() => {
    if (!props.weather?.timezone) return '';
    return new Date().toLocaleTimeString('en-US', { timeZone: props.weather.timezone, hour: '2-digit', minute: '2-digit' });
});

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

const getBackgroundClass = () => {
    if (!props.weather?.weather_code && props.weather?.weather_code !== 0) return 'bg-slate-50 dark:bg-slate-950';
    const code = props.weather.weather_code;
    
    if (code === 0) return 'bg-sky-100 dark:bg-sky-950'; // Clear
    if (code >= 1 && code <= 3) return 'bg-gray-100 dark:bg-gray-900'; // Cloudy
    if (code === 45 || code === 48) return 'bg-slate-200 dark:bg-slate-800'; // Fog
    if ((code >= 51 && code <= 67) || (code >= 80 && code <= 82)) return 'bg-blue-100 dark:bg-blue-950'; // Rain/Drizzle
    if ((code >= 71 && code <= 77) || code === 85 || code === 86) return 'bg-indigo-50 dark:bg-indigo-950'; // Snow
    if (code >= 95 && code <= 99) return 'bg-purple-100 dark:bg-purple-950'; // Thunderstorm
    
    return 'bg-slate-50 dark:bg-slate-950';
};
</script>

<template>
    <Head title="Weather" />

    <div :class="['min-h-screen px-6 py-12 text-slate-900 transition-colors dark:text-slate-100', getBackgroundClass()]">
        <div class="mx-auto mb-6 flex max-w-3xl justify-end">
            <AppearanceTabs />
        </div>
        <div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-8 shadow-2xl transition-colors dark:border-slate-800 dark:bg-slate-900">
            <!-- <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="mb-2 text-sm uppercase tracking-[0.3em] text-sky-400">Weather app</p>
                    <h1 class="text-3xl font-semibold">Search for a city</h1>
                </div>
                <Link :href="weather()" class="text-sm text-slate-400 hover:text-white">
                    Back home
                </Link>
            </div> -->

            <!-- This form sends the typed city to the Laravel route as a query param. -->
            <form class="mb-4 flex flex-col gap-3 md:flex-row" @submit.prevent="searchCity">
                <input
                    name="city"
                    :value="city"
                    placeholder="Enter a city"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none ring-0 transition-colors focus:border-sky-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
                <button
                    type="submit"
                    class="rounded-lg bg-sky-500 px-4 py-3 font-medium text-white transition-colors hover:bg-sky-400"
                >
                    Search
                </button>
                <button
                    type="button"
                    @click="useMyLocation"
                    class="rounded-lg bg-slate-200 px-4 py-3 font-medium text-slate-800 transition-colors hover:bg-slate-300 dark:bg-slate-700 dark:text-white dark:hover:bg-slate-600"
                >
                    📍 Use My Location
                </button>
            </form>

            <!-- Recent Searches -->
            <div v-if="recentSearches.length > 0" class="mb-8 flex flex-wrap items-center gap-2">
                <span class="text-sm text-slate-500 dark:text-slate-400">Recent:</span>
                <a 
                    v-for="search in recentSearches" 
                    :key="search"
                    :href="`/weather?city=${encodeURIComponent(search)}`"
                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    {{ search }}
                </a>
            </div>

            <!-- If the controller returned an error, show it here. -->
            <div v-if="error" class="rounded-lg border border-rose-500/40 bg-rose-500/10 p-4 text-rose-300">
                {{ error }}
            </div>

            <!-- If weather data exists, display it. -->
            <div v-else-if="weather" class="rounded-xl border border-slate-200 bg-slate-100/50 p-6 transition-colors dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">Current weather</p>
                    <p v-if="localTime" class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400 sm:mt-0">Local Time: {{ localTime }}</p>
                </div>
                <h2 class="mt-2 text-2xl font-semibold">
                    {{ weather.city }}, {{ weather.country }}
                </h2>
                <p class="mt-4 text-5xl font-bold">{{ weather.temperature }}°C</p>
                <p class="mt-2 text-lg text-slate-600 transition-colors dark:text-slate-300">{{ weather.condition }}</p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-colors dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">AQI (US)</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.aqi ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-colors dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">Humidity</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.relative_humidity }}%</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-colors dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">Dewpoint</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.dewpoint }}°C</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-colors dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">Rain</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.rain }} mm</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-colors dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">Showers</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.showers }} mm</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-colors dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">VPD</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.vapour_pressure_deficit }} hPa</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-colors dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">Wind (180m)</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.windspeed_180m }} km/h</p>
                        </div>
                    </div>

                    <!-- 7-Day Forecast -->
                    <div class="mt-8">
                        <p class="mb-4 text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">7-Day Forecast</p>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
                            <div v-for="day in weather.daily" :key="day.date" class="flex flex-col items-center rounded-2xl border border-slate-200 bg-white p-4 text-center transition-colors dark:border-slate-800 dark:bg-slate-900">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ formatDate(day.date) }}</span>
                                <span class="mt-2 text-2xl" :title="day.condition">
                                    {{ day.weather_code === 0 ? '☀️' : (day.weather_code >= 1 && day.weather_code <= 3 ? '⛅' : (day.weather_code >= 71 ? '❄️' : ((day.weather_code >= 51 && day.weather_code <= 67) || (day.weather_code >= 80 && day.weather_code <= 82) ? '🌧️' : (day.weather_code >= 95 ? '⛈️' : '☁️')))) }}
                                </span>
                                <div class="mt-2 flex gap-2 text-sm">
                                    <span class="font-bold">{{ day.max_temp }}°</span>
                                    <span class="text-slate-500">{{ day.min_temp }}°</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Radar Map -->
                    <div class="mt-8">
                        <p class="mb-4 text-sm uppercase tracking-[0.3em] text-slate-500 transition-colors dark:text-slate-400">Precipitation Radar</p>
                        <div class="overflow-hidden rounded-2xl border border-slate-200 transition-colors dark:border-slate-800">
                            <iframe 
                                width="100%" 
                                height="400" 
                                :src="`https://embed.windy.com/embed.html?type=map&location=coordinates&metricRain=mm&metricTemp=°C&metricWind=km/h&zoom=5&overlay=rain&product=ecmwf&level=surface&lat=${weather.latitude}&lon=${weather.longitude}`" 
                                frameborder="0">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</template>
