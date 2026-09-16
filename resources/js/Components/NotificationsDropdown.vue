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
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    });

    const n = notifications.value.find((n) => n.id === id);
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
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    });
    notifications.value.forEach((n) => {
        n.read_at = new Date().toISOString();
    });
}

// Tint and glyph per notification type; the bell is the fallback.
const typeStyle = {
    booking_requested: { tint: 'bg-mustard', glyph: 'clock' },
    booking_confirmed: { tint: 'bg-teal text-cream', glyph: 'check' },
    booking_cancelled: { tint: 'bg-coral', glyph: 'cross' },
};
const styleFor = (type) => typeStyle[type] ?? { tint: 'bg-teal-light', glyph: 'bell' };
</script>

<template>
    <div class="absolute right-0 mt-2 w-80 card-hard-lg z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-ink/10">
            <span class="font-display font-bold text-xl">Notifications</span>
            <button
                v-if="notifications.some((n) => !n.read_at)"
                class="text-xs font-bold text-teal hover:underline"
                @click="markAllRead"
            >
                Mark all read
            </button>
        </div>

        <div v-if="loading" class="px-4 py-6 text-center text-sm text-moss">Loading…</div>

        <div v-else-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-moss">
            No notifications yet.
        </div>

        <ul v-else class="max-h-96 overflow-y-auto divide-y divide-ink/10">
            <li
                v-for="n in notifications"
                :key="n.id"
                class="flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-cream transition-colors"
                :class="{ 'bg-mustard/30': !n.read_at }"
                @click="markRead(n.id, n.url)"
            >
                <span
                    class="mt-0.5 w-7 h-7 shrink-0 rounded-lg border-2 border-ink flex items-center justify-center text-ink"
                    :class="styleFor(n.type).tint"
                    :data-glyph="styleFor(n.type).glyph"
                >
                    <svg
                        v-if="styleFor(n.type).glyph === 'check'"
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    <svg
                        v-else-if="styleFor(n.type).glyph === 'cross'"
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        aria-hidden="true"
                    >
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                    <svg
                        v-else-if="styleFor(n.type).glyph === 'clock'"
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                    <svg
                        v-else
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0" />
                    </svg>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-ink leading-snug">{{ n.message }}</p>
                    <p class="text-xs text-moss mt-0.5">
                        {{
                            new Date(n.created_at).toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                            })
                        }}
                    </p>
                </div>
                <span v-if="!n.read_at" class="unread-dot mt-1.5 h-2 w-2 rounded-full bg-coral shrink-0" />
            </li>
        </ul>
    </div>
</template>
