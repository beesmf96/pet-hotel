<script setup>
defineProps({
    as: { type: String, default: 'h2' },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const sizes = { sm: 'text-xl', md: 'text-2xl', lg: 'text-3xl sm:text-4xl' };
</script>

<template>
    <component :is="as" class="font-display font-bold tracking-tight" :class="sizes[size] ?? sizes.md">
        <slot />
    </component>
</template>
