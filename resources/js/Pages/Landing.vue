<script setup>
import { router } from '@inertiajs/vue3';
import SearchBar from '@/Components/Hotels/SearchBar.vue';
import HotelCard from '@/Components/Hotels/HotelCard.vue';

const props = defineProps({
    featuredHotels: Array,
});

function handleSearch(params) {
    router.get('/hotels', params);
}
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Nav -->
        <nav class="absolute top-0 left-0 right-0 z-10 flex items-center justify-between px-6 py-5 sm:px-10">
            <span class="text-xl font-bold text-stone-900">🐾 PetHotel</span>
            <div class="flex items-center gap-3">
                <a href="/login" class="text-sm font-medium text-stone-700 hover:text-stone-900 transition">Sign in</a>
                <a href="/register" class="text-sm font-medium bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-full transition">Register</a>
            </div>
        </nav>

        <!-- Hero -->
        <section class="bg-gradient-to-br from-amber-50 to-orange-100 pt-36 pb-20 px-6 sm:px-10">
            <div class="max-w-4xl mx-auto text-center">
                <p class="text-sm font-semibold text-amber-600 uppercase tracking-widest mb-4">Trusted pet boarding, nationwide</p>
                <h1
                    class="font-bold text-stone-900 mb-4 leading-tight"
                    style="font-size: clamp(2.5rem, 5vw, 4rem);"
                >
                    Your pet deserves<br>a great stay.
                </h1>
                <p class="text-lg text-stone-600 mb-10">Find hotels that care as much as you do.</p>

                <div class="bg-white rounded-2xl shadow-lg p-4 sm:p-6 max-w-3xl mx-auto">
                    <SearchBar :filters="{}" @search="handleSearch" />
                </div>
            </div>
        </section>

        <!-- Featured Hotels -->
        <section class="py-16 px-6 sm:px-10">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-stone-900">Featured Hotels</h2>
                        <p class="text-stone-500 mt-1">Highly rated stays near you</p>
                    </div>
                    <a href="/hotels" class="text-sm font-medium text-amber-600 hover:text-amber-700 transition">
                        Browse all hotels →
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <HotelCard
                        v-for="hotel in featuredHotels"
                        :key="hotel.id"
                        :hotel="hotel"
                    />
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="bg-stone-50 py-16 px-6 sm:px-10">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-2xl font-bold text-stone-900 mb-2">How It Works</h2>
                <p class="text-stone-500 mb-12">Book a stay in three easy steps</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-10">
                    <div>
                        <div class="text-4xl mb-4">🔍</div>
                        <h3 class="font-semibold text-stone-900 mb-2">1. Find</h3>
                        <p class="text-sm text-stone-500">Search by city, dates, and pet type to find the perfect boarding option.</p>
                    </div>
                    <div>
                        <div class="text-4xl mb-4">📅</div>
                        <h3 class="font-semibold text-stone-900 mb-2">2. Book</h3>
                        <p class="text-sm text-stone-500">Reserve your dates instantly. No phone calls, no waiting for confirmation.</p>
                    </div>
                    <div>
                        <div class="text-4xl mb-4">😌</div>
                        <h3 class="font-semibold text-stone-900 mb-2">3. Relax</h3>
                        <p class="text-sm text-stone-500">Drop off your pet and enjoy your trip knowing they're in great hands.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Band -->
        <section class="bg-amber-500 py-16 px-6 sm:px-10 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4">
                Ready to find the perfect stay for your pet?
            </h2>
            <p class="text-amber-100 mb-8">Join thousands of pet owners who trust PetHotel.</p>
            <a
                href="/hotels"
                class="inline-block bg-white text-amber-600 font-semibold px-8 py-3 rounded-full hover:bg-amber-50 transition"
            >
                Find hotels now
            </a>
        </section>

        <!-- Footer -->
        <footer class="bg-stone-900 text-stone-400 py-8 px-6 sm:px-10">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-white font-bold">🐾 PetHotel</span>
                <span class="text-sm">© 2026 PetHotel. All rights reserved.</span>
                <div class="flex gap-6 text-sm">
                    <a href="/hotels" class="hover:text-white transition">Browse Hotels</a>
                    <a href="/login" class="hover:text-white transition">Sign In</a>
                    <a href="/register" class="hover:text-white transition">Register</a>
                </div>
            </div>
        </footer>
    </div>
</template>
