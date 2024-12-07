import {$fetch, type FetchContext, type FetchOptions} from "ofetch"

const BASE_URL = '/api'
const CSRF_COOKIE = 'XSRF-TOKEN'
const CSRF_HEADER = 'X-XSRF-TOKEN'
const ACCEPT_HEADER = 'Accept'

let options: FetchOptions = {}
let headers: any = {}

headers = {
    [ACCEPT_HEADER]: 'application/json'
}

options.headers = headers

const api = $fetch.create({
    async onRequest(context: FetchContext): Promise<void> {
        let headers: any = {}

        //TODO token for post request

        context.options.headers = {
            ...context.options.headers,
            ...headers
        }
    },
    ...options
})

export default api
