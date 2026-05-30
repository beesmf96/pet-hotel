<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    hotelSlug: {
        type: String,
        required: true,
    },
    selectable: {
        type: Boolean,
        default: false,
    },
    modelValue: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const today = new Date();
const todayKey = today.toISOString().slice(0, 10);

const currentYear = ref(today.getFullYear());
const currentMonth = ref(today.getMonth()); // 0-indexed

const loading = ref(false);
const error = ref(null);
const days = ref({});

const checkIn = computed(() => props.modelValue?.checkIn ?? '');
const checkOut = computed(() => props.modelValue?.checkOut ?? '');

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
    const isSelectedEndpoint = props.selectable && (cell.date === checkIn.value || cell.date === checkOut.value);
    const isInRange = props.selectable
        && checkIn.value && checkOut.value
        && cell.date > checkIn.value && cell.date < checkOut.value;
    const isPast = props.selectable && cell.date < todayKey;

    // Priority 1: selected endpoint wins over everything
    if (isSelectedEndpoint) {
        return 'bg-gray-900 text-white ring-2 ring-gray-900 cursor-pointer';
    }

    // Priority 2: unavailable states (blocked/full/past) — not clickable
    if (cell.status === 'blocked' || isPast) {
        return 'bg-gray-100 text-gray-300 cursor-not-allowed';
    }
    if (cell.status === 'full') {
        return 'bg-red-50 text-red-400 line-through cursor-not-allowed';
    }

    // Priority 3: in-range highlight
    if (isInRange) {
        return 'bg-gray-100 text-gray-800' + (props.selectable ? ' cursor-pointer' : '');
    }

    // Priority 4: availability status with today ring (today ring skipped when selected)
    const ring = isToday ? 'ring-2 ' : '';

    if (cell.available_spots !== null && cell.available_spots <= 3) {
        return ring + 'ring-orange-400 bg-orange-50 text-orange-700 font-medium' + (props.selectable ? ' cursor-pointer' : '');
    }
    return ring + 'ring-green-400 bg-green-50 text-green-700 font-medium' + (props.selectable ? ' cursor-pointer' : '');
}

function handleCellClick(cell) {
    if (!props.selectable) return;
    if (!cell) return;
    if (cell.status === 'blocked' || cell.status === 'full') return;
    if (cell.date < todayKey) return;

    const ci = checkIn.value;
    const co = checkOut.value;

    // Double-click on current checkIn: no-op
    if (cell.date === ci && !co) return;

    // Both set or no selection: start fresh with this date as checkIn
    if (!ci || (ci && co)) {
        emit('update:modelValue', { checkIn: cell.date, checkOut: '' });
        return;
    }

    // checkIn set, no checkOut
    if (cell.date > ci) {
        emit('update:modelValue', { checkIn: ci, checkOut: cell.date });
    } else {
        // Clicked same or earlier date: reset with new checkIn
        emit('update:modelValue', { checkIn: cell.date, checkOut: '' });
    }
}

async function fetchMonth() {
    loading.value = true;
    error.value = null;
    try {
        const res = await fetch(`/hotels/${props.hotelSlug}/availability?month=${monthKey.value}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        days.value = data.days ?? {};
    } catch {
        error.value = 'Could not load availability. Please try again.';
        days.value = {};
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
                @click="cell && handleCellClick(cell)"
            >
                <span v-if="cell">{{ cell.day }}</span>
            </div>
        </div>

        <!-- Error state -->
        <p v-if="error" class="mt-3 text-sm text-red-600 text-center">{{ error }}</p>

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
