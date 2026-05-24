import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('leaflet', () => ({
    default: {
        map: vi.fn(() => ({ setView: vi.fn().mockReturnThis(), remove: vi.fn() })),
        tileLayer: vi.fn(() => ({ addTo: vi.fn() })),
        marker: vi.fn(() => ({
            addTo: vi.fn().mockReturnThis(),
            bindPopup: vi.fn().mockReturnThis(),
            openPopup: vi.fn(),
        })),
        Icon: { Default: { prototype: {}, mergeOptions: vi.fn() } },
    },
}))
vi.mock('leaflet/dist/leaflet.css', () => ({}))

import HotelMap from '@/Components/Hotels/HotelMap.vue'

describe('HotelMap', () => {
    it('mounts with valid props without errors', () => {
        const wrapper = mount(HotelMap, {
            props: { lat: 1.3521, lng: 103.8198, name: 'Pawsome Stay' },
            attachTo: document.body,
        })
        expect(wrapper.exists()).toBe(true)
    })
})
