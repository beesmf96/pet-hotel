<script setup>
// A short inline message: a flash after an action, or a status from the server.
defineProps({
    tone: { type: String, default: 'info' }, // info | warning
});

const tones = {
    info: 'bg-teal-light',
    warning: 'bg-mustard',
};
</script>

<template>
    <div
        class="text-sm font-semibold text-ink border-2 border-ink rounded-xl px-4 py-3"
        :class="tones[tone] ?? tones.info"
    >
        <slot />
    </div>
</template>
