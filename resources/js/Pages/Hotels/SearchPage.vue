<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HotelCard from '@/Components/Hotels/HotelCard.vue';
import SearchBar from '@/Components/Hotels/SearchBar.vue';
import FilterSidebar from '@/Components/Hotels/FilterSidebar.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import EmptyState from '@/Components/Ui/EmptyState.vue';

const props = defineProps({
    hotels: Object,
    filters: Object,
});

// With no query string the filters prop is an empty array, whose `.sort` is
// the Array method rather than a string. Only accept a real string value.
const sort = ref(typeof props.filters.sort === 'string' && props.filters.sort ? props.filters.sort : 'latest');

const SORT_OPTIONS = [
    { value: 'latest', label: 'Newest first' },
    { value: 'price_asc', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
];

function handleSearch(searchParams) {
    router.get(
        '/hotels',
        { ...props.filters, ...searchParams, sort: sort.value },
        {
            preserveState: false,
        },
    );
}

function applyFilters(newFilters) {
    router.get(
        '/hotels',
        { ...newFilters, sort: sort.value },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

watch(sort, (value) => {
    router.get(
        '/hotels',
        { ...props.filters, sort: value },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
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
            <h1 class="text-3xl sm:text-4xl">Find a pet hotel</h1>
        </template>

        <div class="space-y-6">
            <div class="card-hard-lg p-4 sm:p-5">
                <SearchBar :filters="filters" @search="handleSearch" />
            </div>

            <div class="flex flex-col md:flex-row gap-6 items-start">
                <aside class="w-full md:w-60 shrink-0">
                    <FilterSidebar :filters="filters" @apply="applyFilters" />
                </aside>

                <div class="flex-1 min-w-0 w-full">
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <p class="text-sm font-semibold text-moss">
                            {{ hotels.total }} hotel{{ hotels.total !== 1 ? 's' : '' }} found
                        </p>
                        <TextInput v-model="sort" :full="false" as="select">
                            <option v-for="opt in SORT_OPTIONS" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </TextInput>
                    </div>

                    <div
                        v-if="hotels.data.length > 0"
                        class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 pr-2 pb-2"
                    >
                        <HotelCard v-for="hotel in hotels.data" :key="hotel.id" :hotel="hotel" />
                    </div>

                    <EmptyState v-else title="No hotels found" message="Try adjusting your search or filters." />

                    <div v-if="hotels.last_page > 1" class="mt-8 flex items-center justify-center flex-wrap gap-2">
                        <button
                            v-for="link in hotels.links"
                            :key="link.label"
                            :disabled="!link.url"
                            class="px-3 py-1.5 text-sm font-semibold rounded-xl border-2 transition"
                            :class="[
                                link.active
                                    ? 'bg-ink text-cream border-ink'
                                    : link.url
                                      ? 'bg-white text-ink border-ink hover:bg-mustard cursor-pointer'
                                      : 'bg-transparent text-moss/50 border-transparent cursor-not-allowed',
                            ]"
                            @click="visitPage(link.url)"
                        >
                            {{ decodeLabel(link.label) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
