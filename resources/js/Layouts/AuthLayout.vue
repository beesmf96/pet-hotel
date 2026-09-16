<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-cream text-ink font-sans py-12 px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <a href="/" class="inline-flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-teal flex items-center justify-center text-cream"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5.5" cy="9" r="2" /><circle cx="9.5" cy="5" r="2" /><circle cx="14.5" cy="5" r="2" /><circle cx="18.5" cy="9" r="2" /><path d="M12 11c-3 0-6 3-6 6 0 1.7 1.3 3 3 3 1 0 2-.5 3-.5s2 .5 3 .5c1.7 0 3-1.3 3-3 0-3-3-6-6-6z" /></svg></span>
                    <span class="font-display font-extrabold text-2xl">PetHotel</span>
                </a>
                <p class="mt-3 text-base font-semibold text-moss">
                    <slot name="subtitle" />
                </p>
            </div>
            <div class="bg-white border-3 border-ink rounded-2xl shadow-hard-lg p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
