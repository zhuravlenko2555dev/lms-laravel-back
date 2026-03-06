import { $fetch, type FetchContext, type FetchOptions } from 'ofetch'
import { useCookies } from '@vueuse/integrations/useCookies'

const CSRF_COOKIE: string = 'XSRF-TOKEN'
const CSRF_HEADER: string = 'X-XSRF-TOKEN'
const ACCEPT_HEADER: string = 'Accept'

const { get: getCookie } = useCookies([CSRF_COOKIE])

const options: FetchOptions = {}

options.headers = {
    [ACCEPT_HEADER]: 'application/json',
}

const api = $fetch.create({
    async onRequest(context: FetchContext): Promise<void> {
        const headers: Record<string, string> = {}

        let token = getCookie(CSRF_COOKIE)
        if (
            !token
            && ['post', 'put', 'patch', 'delete'].includes(
                context.options?.method?.toLowerCase() ?? '',
            )
        ) {
            await $fetch('/sanctum/csrf-cookie')
            token = getCookie(CSRF_COOKIE)
        }

        if (token) {
            headers[CSRF_HEADER] = token
        }

        context.options.headers = {
            ...context.options.headers,
            ...headers,
        }
    },
    ...options,
})

export default api
