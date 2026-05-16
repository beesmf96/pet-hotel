<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 py-12 px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <a href="/" class="text-2xl font-bold text-gray-900">Pet Hotel</a>
                <p class="mt-2 text-sm text-gray-600">
                    <slot name="subtitle" />
                </p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
