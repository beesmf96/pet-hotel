<script setup>
import PawIcon from '@/Components/Ui/PawIcon.vue';

defineProps({
    size: { type: String, default: 'md' }, // sm | md | lg
});

const box = { sm: 'w-8 h-8 rounded-lg', md: 'w-9 h-9 rounded-xl', lg: 'w-9 h-9 rounded-xl' };
const icon = { sm: 18, md: 22, lg: 22 };
const text = { sm: 'text-xl', md: 'text-2xl', lg: 'text-2xl' };
</script>

<template>
    <a href="/" class="inline-flex items-center gap-2.5">
        <span class="bg-teal flex items-center justify-center text-cream" :class="box[size]">
            <PawIcon :size="icon[size]" />
        </span>
        <span class="font-display font-extrabold" :class="text[size]">PetHotel</span>
    </a>
</template>
