<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Vite default marker icon path bug
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
})

const props = defineProps({
    lat: { type: Number, required: true },
    lng: { type: Number, required: true },
    name: { type: String, required: true },
})

// Postgres decimal(10,7) can serialize as string via PHP json_encode
const latFloat = computed(() => Number(props.lat))
const lngFloat = computed(() => Number(props.lng))

const mapRef = ref(null)
let map = null

onMounted(() => {
    map = L.map(mapRef.value).setView([latFloat.value, lngFloat.value], 15)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
    }).addTo(map)
    L.marker([latFloat.value, lngFloat.value]).addTo(map).bindPopup(props.name).openPopup()
})

onUnmounted(() => {
    map?.remove()
})
</script>

<template>
    <div ref="mapRef" class="w-full h-48 rounded-lg z-0" />
</template>
