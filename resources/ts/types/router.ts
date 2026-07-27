import { RouteComponent, RouteMeta, RouteRecordRaw } from 'vue-router'
import { Breadcrumb } from '@/types'

declare module 'vue-router' {
    interface RouteMeta {
        requiresAuth?: boolean
        breadcrumbs?: Breadcrumb[]
    }
}

export interface RouteRecord {
    name?: string
    path: string
    component?: RouteComponent | (() => Promise<RouteComponent>)
    redirect?: RouteRecordRaw['redirect']
    meta?: RouteMeta
    children?: RouteRecord[]
}
