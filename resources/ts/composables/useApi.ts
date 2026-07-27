import { $fetch, type FetchContext, type FetchOptions } from 'ofetch'
import { useCookies } from '@vueuse/integrations/useCookies'
import { useToast } from 'primevue/usetoast'

const CSRF_COOKIE: string = 'XSRF-TOKEN'
const CSRF_HEADER: string = 'X-XSRF-TOKEN'
const ACCEPT_HEADER: string = 'Accept'

const HTTP_FORBIDDEN: number = 403

const options: FetchOptions = {
    headers: {
        [ACCEPT_HEADER]: 'application/json',
    },
}

export function useApi() {
    const { get: getCookie } = useCookies([CSRF_COOKIE])
    const toast = useToast()

    return $fetch.create({
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
        async onResponseError(context: FetchContext): Promise<void> {
            const status = context?.response?.status ?? null

            // TODO: take detail message from response
            if (status === HTTP_FORBIDDEN) {
                toast.add({
                    severity: 'error',
                    summary: 'Forbidden',
                    detail: 'You do not have permission to perform this action.',
                    life: 3000,
                })
            }
        },
        ...options,
    })
}
