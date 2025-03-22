import { $fetch, type FetchContext, type FetchOptions } from "ofetch"
import { useCookies } from '@vueuse/integrations/useCookies'

const CSRF_COOKIE = 'XSRF-TOKEN'
const CSRF_HEADER = 'X-XSRF-TOKEN'
const ACCEPT_HEADER = 'Accept'

const { get: getCookie } = useCookies([CSRF_COOKIE])

let options: FetchOptions = {}
let headers: any = {}

headers = {
    [ACCEPT_HEADER]: 'application/json'
}

options.headers = headers

const api = $fetch.create({
    async onRequest(context: FetchContext): Promise<void> {
        let headers: any = {}

        let token = getCookie(CSRF_COOKIE)
        if (
            !token
            && ["post", "put", "patch", "delete"].includes(
                context.options?.method?.toLowerCase() ?? ""
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
            ...headers
        }
    },
    ...options
})

export default api
