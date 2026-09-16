<script setup>
import BrandMark from '@/Components/Ui/BrandMark.vue';
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-cream text-ink font-sans py-12 px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <BrandMark />
                <p class="mt-3 text-base font-semibold text-moss">
                    <slot name="subtitle" />
                </p>
            </div>
            <div class="card-hard-lg p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
