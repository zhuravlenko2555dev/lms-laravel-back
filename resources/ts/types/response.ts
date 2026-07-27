import { JSONValue } from '@/types'

export interface JsonResponse {
    [key: string]: JSONValue
}

export interface ResourceResponse<T> {
    data: T
}

export interface ResourceCollectionResponse<T> {
    data: T[]
    links: PaginationLinks
    meta: PaginationMeta
}

export interface PaginationLinks {
    first: string
    last: string
    prev: string | null
    next: string | null
}

export interface PaginationMeta {
    current_page: number
    from: number
    last_page: number
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
    path: string
    per_page: number
    to: number
    total: number
}

export interface ValidationErrorsResponse {
    message: string
    errors: Record<string, string[]>
}
