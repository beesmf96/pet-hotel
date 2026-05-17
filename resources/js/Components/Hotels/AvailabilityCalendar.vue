<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    hotelSlug: {
        type: String,
        required: true,
    },
});

const today = new Date();
const todayKey = today.toISOString().slice(0, 10);

const currentYear = ref(today.getFullYear());
const currentMonth = ref(today.getMonth()); // 0-indexed

const loading = ref(false);
const days = ref({});

const monthLabel = computed(() =>
    new Date(currentYear.value, currentMonth.value, 1).toLocaleString('default', {
        month: 'long',
        year: 'numeric',
    }),
);

const monthKey = computed(() => {
    const m = String(currentMonth.value + 1).padStart(2, '0');
    return `${currentYear.value}-${m}`;
});

const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const cells = [];
    for (let i = 0; i < firstDay; i++) cells.push(null);
    for (let d = 1; d <= daysInMonth; d++) {
        const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        cells.push({ date: key, day: d, ...(days.value[key] ?? { status: 'available', available_spots: null }) });
    }
    return cells;
});

function cellClass(cell) {
    const isToday = cell.date === todayKey;
    const ring = isToday ? 'ring-2 ' : '';

    if (cell.status === 'blocked') {
        return 'bg-gray-100 text-gray-300 cursor-not-allowed';
    }
    if (cell.status === 'full') {
        return 'bg-red-50 text-red-400 line-through cursor-not-allowed';
    }
    if (cell.available_spots !== null && cell.available_spots <= 3) {
        return ring + 'ring-orange-400 bg-orange-50 text-orange-700 font-medium';
    }
    return ring + 'ring-green-400 bg-green-50 text-green-700 font-medium';
}

async function fetchMonth() {
    loading.value = true;
    try {
        const res = await fetch(`/hotels/${props.hotelSlug}/availability?month=${monthKey.value}`);
        const data = await res.json();
        days.value = data.days ?? {};
    } finally {
        loading.value = false;
    }
}

function prevMonth() {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
}

function nextMonth() {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
}

const isPrevDisabled = computed(
    () =>
        currentYear.value < today.getFullYear() ||
        (currentYear.value === today.getFullYear() && currentMonth.value <= today.getMonth()),
);

watch(monthKey, fetchMonth);
onMounted(fetchMonth);
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Availability</h2>

        <!-- Month navigation -->
        <div class="flex items-center justify-between mb-4">
            <button
                class="w-8 h-8 rounded-full flex items-center justify-center text-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed"
                :disabled="isPrevDisabled"
                @click="prevMonth"
            >
                ‹
            </button>
            <span class="text-sm font-medium text-gray-900">{{ monthLabel }}</span>
            <button
                class="w-8 h-8 rounded-full flex items-center justify-center text-lg text-gray-500 hover:bg-gray-100"
                @click="nextMonth"
            >
                ›
            </button>
        </div>

        <!-- Day-of-week headers -->
        <div class="grid grid-cols-7 mb-1">
            <span
                v-for="d in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']"
                :key="d"
                class="text-center text-xs text-gray-400 font-medium py-1"
            >{{ d }}</span>
        </div>

        <!-- Calendar grid -->
        <div v-if="loading" class="grid grid-cols-7 gap-1">
            <div v-for="i in 35" :key="i" class="h-9 rounded-lg bg-gray-100 animate-pulse" />
        </div>
        <div v-else class="grid grid-cols-7 gap-1">
            <div
                v-for="(cell, i) in calendarDays"
                :key="i"
                class="h-9 rounded-lg flex items-center justify-center text-sm"
                :class="cell ? cellClass(cell) : ''"
            >
                <span v-if="cell">{{ cell.day }}</span>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap gap-4 mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span class="w-3 h-3 rounded-sm bg-green-50 border border-green-300 inline-block" />
                Available
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span class="w-3 h-3 rounded-sm bg-orange-50 border border-orange-300 inline-block" />
                Limited
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span class="w-3 h-3 rounded-sm bg-red-50 border border-red-200 inline-block" />
                Full
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-200 inline-block" />
                Unavailable
            </div>
        </div>
    </div>
</template>
