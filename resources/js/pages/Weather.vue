<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
// import { weather } from '@/routes';

// These props come from the Laravel controller.
const props = defineProps<{
    city: string;
    weather: {
        city: string;
        country: string;
        temperature: number | null;
        relative_humidity: number | null;
        dewpoint: number | null;
        rain: number | null;
        showers: number | null;
        vapour_pressure_deficit: number | null;
        windspeed_180m: number | null;
        condition: string;
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
</script>

<template>
    <Head title="Weather" />

    <div class="min-h-screen bg-slate-950 px-6 py-12 text-slate-100">
        <div class="mx-auto max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl">
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
            <form class="mb-8 flex flex-col gap-3 md:flex-row" @submit.prevent="searchCity">
                <input
                    name="city"
                    :value="city"
                    placeholder="Enter a city"
                    class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 text-white outline-none ring-0 focus:border-sky-500"
                />
                <button
                    type="submit"
                    class="rounded-lg bg-sky-500 px-4 py-3 font-medium text-white hover:bg-sky-400"
                >
                    Search
                </button>
            </form>

            <!-- If the controller returned an error, show it here. -->
            <div v-if="error" class="rounded-lg border border-rose-500/40 bg-rose-500/10 p-4 text-rose-300">
                {{ error }}
            </div>

            <!-- If weather data exists, display it. -->
            <div v-else-if="weather" class="rounded-xl border border-slate-800 bg-slate-950/70 p-6">
                <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Current weather</p>
                <h2 class="mt-2 text-2xl font-semibold">
                    {{ weather.city }}, {{ weather.country }}
                </h2>
                <p class="mt-4 text-5xl font-bold">{{ weather.temperature }}°C</p>
                <p class="mt-2 text-lg text-slate-300">{{ weather.condition }}</p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Humidity</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.relative_humidity }}%</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Dewpoint</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.dewpoint }}°C</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Rain</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.rain }} mm</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Showers</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.showers }} mm</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500">VPD</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.vapour_pressure_deficit }} hPa</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Wind (180m)</p>
                            <p class="mt-2 text-xl font-semibold">{{ weather.windspeed_180m }} km/h</p>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</template>
