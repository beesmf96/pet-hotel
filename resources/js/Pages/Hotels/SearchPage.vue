<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HotelCard from '@/Components/Hotels/HotelCard.vue';
import SearchBar from '@/Components/Hotels/SearchBar.vue';
import FilterSidebar from '@/Components/Hotels/FilterSidebar.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    hotels: Object,
    filters: Object,
});

const sort = ref(props.filters.sort || 'latest');

const SORT_OPTIONS = [
    { value: 'latest', label: 'Newest first' },
    { value: 'price_asc', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
];

function handleSearch(searchParams) {
    router.get('/hotels', { ...props.filters, ...searchParams, sort: sort.value }, {
        preserveState: false,
    });
}

function applyFilters(newFilters) {
    router.get('/hotels', { ...newFilters, sort: sort.value }, {
        preserveState: true,
        preserveScroll: true,
    });
}

watch(sort, (value) => {
    router.get('/hotels', { ...props.filters, sort: value }, {
        preserveState: true,
        preserveScroll: true,
    });
});

function visitPage(url) {
    if (url) router.visit(url);
}

function decodeLabel(label) {
    return label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">Find a Pet Hotel</h1>
        </template>

        <div class="space-y-6">
            <SearchBar :filters="filters" @search="handleSearch" />

            <div class="flex gap-6 items-start">
                <aside class="w-56 flex-shrink-0">
                    <FilterSidebar :filters="filters" @apply="applyFilters" />
                </aside>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm text-gray-500">
                            {{ hotels.total }} hotel{{ hotels.total !== 1 ? 's' : '' }} found
                        </p>
                        <select
                            v-model="sort"
                            class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-gray-900"
                        >
                            <option v-for="opt in SORT_OPTIONS" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div
                        v-if="hotels.data.length > 0"
                        class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4"
                    >
                        <HotelCard v-for="hotel in hotels.data" :key="hotel.id" :hotel="hotel" />
                    </div>

                    <div v-else class="text-center py-20">
                        <div class="text-6xl mb-4">🐾</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">No hotels found</h3>
                        <p class="text-sm text-gray-500">Try adjusting your search or filters.</p>
                    </div>

                    <div
                        v-if="hotels.last_page > 1"
                        class="mt-6 flex items-center justify-center flex-wrap gap-1"
                    >
                        <button
                            v-for="link in hotels.links"
                            :key="link.label"
                            :disabled="!link.url"
                            class="px-3 py-1.5 text-sm rounded-lg"
                            :class="[
                                link.active
                                    ? 'bg-gray-900 text-white'
                                    : link.url
                                    ? 'text-gray-600 hover:bg-gray-100 cursor-pointer'
                                    : 'text-gray-300 cursor-not-allowed',
                            ]"
                            @click="visitPage(link.url)"
                        >{{ decodeLabel(link.label) }}</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
