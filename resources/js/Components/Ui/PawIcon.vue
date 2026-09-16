<script setup>
defineProps({
    size: { type: Number, default: 24 },
});
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <circle cx="5.5" cy="9" r="2" />
        <circle cx="9.5" cy="5" r="2" />
        <circle cx="14.5" cy="5" r="2" />
        <circle cx="18.5" cy="9" r="2" />
        <path d="M12 11c-3 0-6 3-6 6 0 1.7 1.3 3 3 3 1 0 2-.5 3-.5s2 .5 3 .5c1.7 0 3-1.3 3-3 0-3-3-6-6-6z" />
    </svg>
</template>
