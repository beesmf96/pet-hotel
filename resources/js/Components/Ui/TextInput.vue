<script setup>
// v-model on a dynamic <component> compiles as a component binding, so each
// native element is written out explicitly to keep the value in sync.
const model = defineModel({ type: [String, Number], default: '' });

defineProps({
    as: { type: String, default: 'input' }, // input | textarea | select
    full: { type: Boolean, default: true },
});
</script>

<template>
    <textarea v-if="as === 'textarea'" v-model="model" class="field-hard resize-none" :class="{ 'w-full': full }" />
    <select v-else-if="as === 'select'" v-model="model" class="field-hard" :class="{ 'w-full': full }">
        <slot />
    </select>
    <input v-else v-model="model" class="field-hard" :class="{ 'w-full': full }" />
</template>
