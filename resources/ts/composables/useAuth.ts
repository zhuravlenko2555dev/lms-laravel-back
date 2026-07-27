import { computed, ref } from 'vue'
import { $fetch } from 'ofetch'
import { ResourceResponse, User } from '@/types'

const user = ref<User | null>(null)
const resolved = ref<boolean>(false)

export function useAuth() {
    const isAuthenticated = computed<boolean>(() => user.value !== null)

    async function fetchUser(): Promise<User | null> {
        try {
            const res = await $fetch<ResourceResponse<User>>('/api/auth/me', {
                headers: { Accept: 'application/json' },
            })
            user.value = res.data
        } catch {
            user.value = null
        }

        resolved.value = true

        return user.value
    }

    async function ensureAuth(): Promise<boolean> {
        if (!resolved.value) {
            await fetchUser()
        }

        return isAuthenticated.value
    }

    function setUser(value: User | null): void {
        user.value = value
        resolved.value = true
    }

    return { user, isAuthenticated, fetchUser, ensureAuth, setUser }
}
