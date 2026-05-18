<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    unreadCount: { type: Number, default: 0 },
});

const emit = defineEmits(['closed']);

const notifications = ref([]);
const loading = ref(true);

onMounted(async () => {
    const res = await fetch('/notifications', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });
    if (res.ok) {
        notifications.value = await res.json();
    }
    loading.value = false;
});

async function markRead(id, url) {
    await fetch(`/notifications/${id}/read`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    });

    const n = notifications.value.find(n => n.id === id);
    if (n) n.read_at = new Date().toISOString();

    if (url) {
        emit('closed');
        router.visit(url);
    }
}

async function markAllRead() {
    await fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    });
    notifications.value.forEach(n => { n.read_at = new Date().toISOString(); });
}

const typeIcon = {
    booking_requested: '📋',
    booking_confirmed: '✅',
    booking_cancelled: '❌',
};
</script>

<template>
    <div class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <span class="text-sm font-semibold text-gray-900">Notifications</span>
            <button
                v-if="notifications.some(n => !n.read_at)"
                class="text-xs text-indigo-600 hover:text-indigo-800"
                @click="markAllRead"
            >
                Mark all read
            </button>
        </div>

        <div v-if="loading" class="px-4 py-6 text-center text-sm text-gray-400">Loading…</div>

        <div v-else-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
            No notifications yet.
        </div>

        <ul v-else class="max-h-96 overflow-y-auto divide-y divide-gray-50">
            <li
                v-for="n in notifications"
                :key="n.id"
                class="flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                :class="{ 'bg-indigo-50': !n.read_at }"
                @click="markRead(n.id, n.url)"
            >
                <span class="text-lg leading-none mt-0.5">{{ typeIcon[n.type] ?? '🔔' }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800 leading-snug">{{ n.message }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ new Date(n.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                    </p>
                </div>
                <span v-if="!n.read_at" class="mt-1.5 h-2 w-2 rounded-full bg-indigo-500 shrink-0" />
            </li>
        </ul>
    </div>
</template>
