<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import NotificationsDropdown from './NotificationsDropdown.vue';

const props = defineProps({
    unreadCount: { type: Number, default: 0 },
});

const open = ref(false);
const bellRef = ref(null);

function toggle() {
    open.value = !open.value;
}

function handleOutsideClick(e) {
    if (bellRef.value && !bellRef.value.contains(e.target)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', handleOutsideClick));
</script>

<template>
    <div ref="bellRef" class="relative">
        <button
            type="button"
            class="relative p-1.5 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
            aria-label="Notifications"
            @click.stop="toggle"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] flex items-center justify-center rounded-full bg-indigo-600 text-white text-[10px] font-bold leading-none px-1"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <NotificationsDropdown
            v-if="open"
            :unread-count="unreadCount"
            @closed="open = false"
        />
    </div>
</template>
