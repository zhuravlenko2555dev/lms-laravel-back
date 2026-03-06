import 'vue-router'

export interface MenuItem {
    label?: string
    icon?: string
    to?: string
    url?: string
    class?: string
    target?: string
    items?: MenuItem[]
    separator?: boolean
    command?: (...args: unknown[]) => void
    visible?: boolean
    disabled?: boolean
}

export interface Breadcrumb {
    label: string
    to?: string
}

declare module 'vue-router' {
    interface RouteMeta {
        breadcrumbs?: Breadcrumb[]
    }
}

export interface PageReport {
    first: number
    last: number
    totalRecords: number
}

export interface DatatableColumnFilter<T> {
    value: Array<string | number> | null
    options?: T[]
    range?: [number, number]
    loading: boolean
    url: string
}

export interface DatatableColumnSort {
    field: string | null
    order: number | null
}

export interface DatatableFilterChip {
    id: string | number
    name: string
}
