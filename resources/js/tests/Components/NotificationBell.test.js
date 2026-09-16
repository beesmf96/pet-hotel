import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/Components/NotificationsDropdown.vue', () => ({
    default: {
        props: ['unreadCount'],
        emits: ['closed'],
        template: '<div data-testid="dropdown" :data-count="unreadCount" />',
    },
}))

import NotificationBell from '@/Components/NotificationBell.vue'

const mountBell = (props = {}) => mount(NotificationBell, { props, attachTo: document.body })

describe('NotificationBell — badge', () => {
    it('hides the badge at zero', () => {
        const w = mountBell({ unreadCount: 0 })
        expect(w.find('button span').exists()).toBe(false)
        w.unmount()
    })

    it('shows the count, capped at 99+', () => {
        const w = mountBell({ unreadCount: 7 })
        expect(w.find('button span').text()).toBe('7')
        w.unmount()
        const big = mountBell({ unreadCount: 250 })
        expect(big.find('button span').text()).toBe('99+')
        big.unmount()
    })
})

describe('NotificationBell — dropdown', () => {
    it('toggles the dropdown on click and passes the count through', async () => {
        const w = mountBell({ unreadCount: 3 })
        expect(w.find('[data-testid="dropdown"]').exists()).toBe(false)
        await w.find('button').trigger('click')
        expect(w.find('[data-testid="dropdown"]').attributes('data-count')).toBe('3')
        await w.find('button').trigger('click')
        expect(w.find('[data-testid="dropdown"]').exists()).toBe(false)
        w.unmount()
    })

    it('closes when the dropdown emits closed', async () => {
        const w = mountBell()
        await w.find('button').trigger('click')
        await w.findComponent('[data-testid="dropdown"]').vm.$emit('closed')
        expect(w.find('[data-testid="dropdown"]').exists()).toBe(false)
        w.unmount()
    })

    it('closes on a click outside, stays open on a click inside', async () => {
        const w = mountBell()
        await w.find('button').trigger('click')
        w.element.dispatchEvent(new MouseEvent('click', { bubbles: true }))
        await w.vm.$nextTick()
        expect(w.find('[data-testid="dropdown"]').exists()).toBe(true)
        document.body.dispatchEvent(new MouseEvent('click', { bubbles: true }))
        await w.vm.$nextTick()
        expect(w.find('[data-testid="dropdown"]').exists()).toBe(false)
        w.unmount()
    })

    it('removes its document listener on unmount', () => {
        const remove = vi.spyOn(document, 'removeEventListener')
        const w = mountBell()
        w.unmount()
        expect(remove).toHaveBeenCalledWith('click', expect.any(Function))
        remove.mockRestore()
    })
})
