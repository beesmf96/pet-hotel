<script setup>
// One label/value row on a detail card (booking detail, confirmation). Use
// inside a <dl>. The label never wraps; a long value wraps and stays right-aligned.
defineProps({
    label: { type: String, required: true },
});
</script>

<template>
    <div class="flex justify-between gap-4">
        <dt class="font-semibold text-moss whitespace-nowrap">{{ label }}</dt>
        <dd class="font-bold text-ink text-right"><slot /></dd>
    </div>
</template>
