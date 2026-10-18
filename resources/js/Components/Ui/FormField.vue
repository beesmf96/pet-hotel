<script setup>
// Label + control + error in one shape, so pages stop repeating the trio.
// The label is tied to the control: TextInput takes the id by itself; any
// other control uses the `id` (or, for a group, `labelId`) slot prop.
import { computed, provide, useId } from 'vue';
import { formFieldKey } from './formField.js';

const props = defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
});

const id = useId();
const labelId = `${id}-label`;
const errorId = `${id}-error`;

provide(
    formFieldKey,
    computed(() => ({ id, errorId: props.error ? errorId : undefined })),
);
</script>

<template>
    <div>
        <div v-if="label || $slots.aside" class="flex justify-between items-baseline mb-1.5">
            <label :id="labelId" :for="id" class="block text-sm font-bold"
                >{{ label }}<slot name="label-suffix"
            /></label>
            <slot name="aside" />
        </div>
        <slot :id="id" :label-id="labelId" />
        <p v-if="error" :id="errorId" class="mt-1.5 text-sm font-semibold text-coral">{{ error }}</p>
    </div>
</template>
