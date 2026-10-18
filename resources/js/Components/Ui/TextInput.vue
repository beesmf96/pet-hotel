<script setup>
import { computed, inject } from 'vue';
import { formFieldKey } from './formField.js';

// v-model on a dynamic <component> compiles as a component binding, so each
// native element is written out explicitly to keep the value in sync.
const model = defineModel({ type: [String, Number], default: '' });

defineProps({
    as: { type: String, default: 'input' }, // input | textarea | select
    full: { type: Boolean, default: true },
});

// Inside a FormField: the label's id, and the error when there is one. An id
// passed in directly still wins, as attributes fall through after these.
const field = inject(formFieldKey, null);
const fieldAttrs = computed(() =>
    field
        ? {
              id: field.value.id,
              'aria-describedby': field.value.errorId,
              'aria-invalid': field.value.errorId ? 'true' : undefined,
          }
        : {},
);
</script>

<template>
    <textarea
        v-if="as === 'textarea'"
        v-model="model"
        v-bind="fieldAttrs"
        class="field-hard resize-none"
        :class="{ 'w-full': full }"
    />
    <select
        v-else-if="as === 'select'"
        v-model="model"
        v-bind="fieldAttrs"
        class="field-hard"
        :class="{ 'w-full': full }"
    >
        <slot />
    </select>
    <input v-else v-model="model" v-bind="fieldAttrs" class="field-hard" :class="{ 'w-full': full }" />
</template>
