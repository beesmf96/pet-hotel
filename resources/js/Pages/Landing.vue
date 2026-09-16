<script setup>
import { computed } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import SearchBar from '@/Components/Hotels/SearchBar.vue';
import HotelCard from '@/Components/Hotels/HotelCard.vue';

const props = defineProps({
    featuredHotels: Array,
});

defineOptions({ layout: null });

const page = usePage();
const user = computed(() => page.props.auth?.user);
const logoutForm = useForm({});

const hasFeatured = computed(() => props.featuredHotels?.length > 0);

const steps = [
    {
        title: '1. Find',
        body: 'Search by city, dates and pet type. Only hotels with free spots on your dates show up.',
        tint: 'bg-mustard',
        icon: 'search',
    },
    {
        title: '2. Book',
        body: "Send a request with your pet's profile. The hotel confirms and we notify you right away.",
        tint: 'bg-coral',
        icon: 'calendar',
    },
    {
        title: '3. Relax',
        body: "Drop off, enjoy your trip, and leave a review when you're home.",
        tint: 'bg-cream',
        icon: 'check',
    },
];

function handleSearch(params) {
    router.get('/hotels', params);
}
</script>

<template>
    <div class="min-h-screen bg-cream text-ink font-sans">
        <!-- Nav -->
        <nav class="flex items-center justify-between gap-4 px-5 py-4 sm:px-10 border-b-3 border-ink">
            <a href="/" class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-teal flex items-center justify-center text-cream">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="5.5" cy="9" r="2" />
                        <circle cx="9.5" cy="5" r="2" />
                        <circle cx="14.5" cy="5" r="2" />
                        <circle cx="18.5" cy="9" r="2" />
                        <path d="M12 11c-3 0-6 3-6 6 0 1.7 1.3 3 3 3 1 0 2-.5 3-.5s2 .5 3 .5c1.7 0 3-1.3 3-3 0-3-3-6-6-6z" />
                    </svg>
                </span>
                <span class="font-display font-extrabold text-2xl">PetHotel</span>
            </a>
            <div class="flex items-center gap-3 sm:gap-6 text-sm font-semibold">
                <a href="/hotels" class="hidden sm:inline hover:text-teal transition">Browse hotels</a>
                <template v-if="user">
                    <span class="hidden sm:inline text-moss">{{ user.name }}</span>
                    <a href="/bookings" class="hover:text-teal transition">My Bookings</a>
                    <button
                        class="bg-mustard border-3 border-ink px-4 py-2 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
                        :disabled="logoutForm.processing"
                        @click="logoutForm.post('/logout')"
                    >Sign out</button>
                </template>
                <template v-else>
                    <a href="/login" class="hover:text-teal transition">Sign in</a>
                    <a
                        href="/register"
                        class="bg-mustard border-3 border-ink px-4 py-2 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
                    >Register</a>
                </template>
            </div>
        </nav>

        <!-- Hero -->
        <section class="relative overflow-hidden px-5 pt-14 pb-10 sm:px-10 sm:pt-20">
            <div class="hidden lg:block absolute left-24 top-24 w-28 h-28 rounded-full bg-coral" aria-hidden="true"></div>
            <div class="hidden lg:block absolute right-28 top-14 w-20 h-20 rounded-3xl bg-teal rotate-12" aria-hidden="true"></div>
            <div class="hidden lg:block absolute right-64 top-72 w-14 h-14 rounded-full bg-mustard border-3 border-ink" aria-hidden="true"></div>

            <div class="relative max-w-5xl mx-auto flex flex-col items-center text-center gap-6">
                <span class="bg-coral border-3 border-ink rounded-full px-4 py-2 text-sm font-bold shadow-hard">
                    Dogs, cats, rabbits and birds welcome
                </span>
                <h1
                    class="font-display font-extrabold leading-[0.95] tracking-tight"
                    style="font-size: clamp(2.75rem, 6.5vw, 5.5rem);"
                >
                    Holidays for humans.<br>
                    <span class="text-teal">Sleepovers</span> for pets.
                </h1>
                <p class="text-lg sm:text-xl text-moss max-w-xl" style="text-wrap: pretty;">
                    Find a boarding hotel your pet will actually enjoy. Real photos, real reviews, instant booking requests.
                </p>
            </div>

            <div class="relative max-w-5xl mx-auto mt-10 bg-white border-3 border-ink rounded-2xl shadow-hard-lg p-4 sm:p-5">
                <SearchBar :filters="{}" variant="bold" @search="handleSearch" />
            </div>
        </section>

        <!-- Featured Hotels -->
        <section v-if="hasFeatured" class="px-5 py-16 sm:px-10 sm:py-20">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-end justify-between gap-4 mb-8">
                    <h2 class="font-display font-extrabold text-3xl sm:text-4xl tracking-tight">Crowd favourites</h2>
                    <a
                        href="/hotels"
                        class="text-sm font-bold bg-white border-3 border-ink px-4 py-2 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition whitespace-nowrap"
                    >All hotels</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                    <HotelCard
                        v-for="(hotel, index) in featuredHotels"
                        :key="hotel.id"
                        :hotel="hotel"
                        :class="index % 2 === 0 ? 'lg:-rotate-1' : 'lg:rotate-1'"
                    />
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="px-5 pb-16 sm:px-10 sm:pb-20" :class="{ 'pt-16 sm:pt-20': !hasFeatured }">
            <div
                class="max-w-7xl mx-auto bg-teal text-cream border-3 border-ink rounded-3xl shadow-hard-lg px-6 py-10 sm:px-14 sm:py-14 grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-10"
            >
                <div v-for="step in steps" :key="step.title" class="flex flex-col gap-3.5">
                    <span
                        class="w-13 h-13 rounded-2xl border-3 border-ink flex items-center justify-center text-ink"
                        :class="step.tint"
                    >
                        <svg
                            v-if="step.icon === 'search'"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"
                        >
                            <circle cx="11" cy="11" r="7" />
                            <path d="M20 20l-3.5-3.5" />
                        </svg>
                        <svg
                            v-else-if="step.icon === 'calendar'"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                        >
                            <rect x="3" y="5" width="18" height="16" rx="3" />
                            <path d="M3 10h18M8 3v4M16 3v4" />
                        </svg>
                        <svg
                            v-else
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                        >
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                    </span>
                    <h3 class="font-display font-bold text-2xl">{{ step.title }}</h3>
                    <p class="text-[15px] leading-relaxed text-teal-light">{{ step.body }}</p>
                </div>
            </div>
        </section>

        <!-- Owner CTA -->
        <section class="px-5 pb-20 sm:px-10 sm:pb-24">
            <div class="max-w-2xl mx-auto flex flex-col items-center text-center gap-5">
                <h2 class="font-display font-extrabold text-4xl sm:text-5xl tracking-tight">Own a pet hotel?</h2>
                <p class="text-moss text-lg" style="text-wrap: pretty;">
                    List your rooms, set a price per pet type, and manage bookings from one place.
                </p>
                <a
                    :href="user ? '/owner' : '/register'"
                    class="bg-mustard border-3 border-ink px-6 py-3.5 rounded-xl font-bold shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
                >List your hotel</a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-ink text-cream/70 py-8 px-5 sm:px-10 border-t-3 border-ink">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="font-display font-extrabold text-cream">PetHotel</span>
                <span class="text-sm">© 2026 PetHotel. All rights reserved.</span>
                <div class="flex gap-6 text-sm">
                    <a href="/hotels" class="hover:text-cream transition">Browse Hotels</a>
                    <template v-if="user">
                        <a href="/bookings" class="hover:text-cream transition">My Bookings</a>
                        <button class="hover:text-cream transition" :disabled="logoutForm.processing" @click="logoutForm.post('/logout')">Sign Out</button>
                    </template>
                    <template v-else>
                        <a href="/login" class="hover:text-cream transition">Sign In</a>
                        <a href="/register" class="hover:text-cream transition">Register</a>
                    </template>
                </div>
            </div>
        </footer>
    </div>
</template>
