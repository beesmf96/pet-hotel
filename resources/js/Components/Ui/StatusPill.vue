<script setup>
import { computed } from 'vue';

// The one map from a booking status to its label and colours.
const props = defineProps({
    status: { type: String, required: true },
});

const config = {
    pending: { label: 'Pending', classes: 'bg-mustard text-ink' },
    confirmed: { label: 'Confirmed', classes: 'bg-teal text-cream' },
    completed: { label: 'Completed', classes: 'bg-teal-light text-ink' },
    cancelled: { label: 'Cancelled', classes: 'bg-white text-moss' },
};

const entry = computed(() => config[props.status] ?? { label: props.status, classes: 'bg-white text-moss' });
</script>

<template>
    <span
        class="inline-block shrink-0 font-sans tracking-normal text-xs font-bold px-3 py-1 rounded-full border-2 border-ink"
        :class="entry.classes"
    >
        {{ entry.label }}
    </span>
</template>
