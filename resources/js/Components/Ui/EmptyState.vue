<script setup>
import PawIcon from '@/Components/Ui/PawIcon.vue';

defineProps({
    title: { type: String, default: '' },
    message: { type: String, default: '' },
});
</script>

<template>
    <div class="card-hard text-center py-14 px-6 flex flex-col items-center gap-3">
        <span class="w-14 h-14 rounded-2xl bg-mustard border-3 border-ink flex items-center justify-center text-ink">
            <PawIcon :size="26" />
        </span>
        <h3 v-if="title" class="font-display font-bold text-xl">{{ title }}</h3>
        <p v-if="message" class="text-moss">{{ message }}</p>
        <div v-if="$slots.default" class="mt-1">
            <slot />
        </div>
    </div>
</template>
