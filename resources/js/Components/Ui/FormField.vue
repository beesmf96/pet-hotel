<script setup>
// Label + control + error in one shape, so pages stop repeating the trio.
defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
});
</script>

<template>
    <div>
        <div v-if="label || $slots.aside" class="flex justify-between items-baseline mb-1.5">
            <label class="block text-sm font-bold">{{ label }}<slot name="label-suffix" /></label>
            <slot name="aside" />
        </div>
        <slot />
        <p v-if="error" class="mt-1.5 text-sm font-semibold text-coral">{{ error }}</p>
    </div>
</template>
