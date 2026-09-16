<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

// One place for the "bold" button: outlined, hard shadow, presses in on hover.
const props = defineProps({
    variant: { type: String, default: 'teal' }, // teal | mustard | white | cream | coral
    size: { type: String, default: 'md' }, // sm | md | lg
    as: { type: String, default: 'button' }, // button | a | link
    block: { type: Boolean, default: false },
});

const variants = {
    teal: 'bg-teal text-cream',
    mustard: 'bg-mustard text-ink',
    white: 'bg-white text-ink',
    cream: 'bg-cream text-ink',
    coral: 'bg-coral text-ink',
};

const sizes = {
    sm: 'px-4 py-2 text-sm',
    md: 'px-5 py-2.5 text-sm',
    lg: 'px-6 py-3 text-base',
};

const tag = computed(() => (props.as === 'link' ? Link : props.as));

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 font-sans font-bold tracking-normal text-center',
    'border-3 border-ink rounded-xl shadow-hard transition',
    'hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    variants[props.variant] ?? variants.teal,
    sizes[props.size] ?? sizes.md,
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <component :is="tag" :class="classes">
        <slot />
    </component>
</template>
