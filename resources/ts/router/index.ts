import { RouteRecordRaw, Router, createRouter, createWebHistory } from 'vue-router'
import { RouteRecord } from '@/types'
import AppLayout from '@/layout/AppLayout.vue'
import { useAuth } from '@/composables/useAuth'

const prefixRoutes = (parentName: string, routes: RouteRecord[]): RouteRecord[] => {
    return routes.map((route) => {
        const currentName = route.name
            ? (parentName ? `${parentName}.${route.name}` : route.name)
            : parentName

        const mappedRoute = { ...route }

        if (route.name) {
            mappedRoute.name = currentName
        }

        if (route.children && route.children.length > 0) {
            mappedRoute.children = prefixRoutes(currentName, route.children)
        }

        return mappedRoute
    })
}

const router: Router = createRouter({
    history: createWebHistory(),
    routes: prefixRoutes('', [
        {
            name: 'login',
            path: '/login',
            component: () => import('@/views/pages/Login.vue'),
            meta: {
                requiresAuth: false,
            },
        },
        {
            name: 'admin',
            path: '/admin',
            component: AppLayout,
            meta: {
                requiresAuth: true,
            },
            children: [
                {
                    name: 'books',
                    path: 'books',
                    children: [
                        {
                            name: 'index',
                            path: '',
                            component: () => import('@/views/pages/Book/Index.vue'),
                            meta: {
                                breadcrumbs: [
                                    { label: 'Books' },
                                ],
                            },
                        },
                        {
                            name: 'create',
                            path: 'create',
                            component: () => import('@/views/pages/Book/Record.vue'),
                            meta: {
                                breadcrumbs: [
                                    { label: 'Books', to: '/admin/books' },
                                    { label: 'Create' },
                                ],
                            },
                        },
                        {
                            name: 'edit',
                            path: ':id',
                            component: () => import('@/views/pages/Book/Record.vue'),
                            meta: {
                                breadcrumbs: [
                                    { label: 'Books', to: '/admin/books' },
                                    { label: 'Edit' },
                                ],
                            },
                        },
                    ],
                },
                {
                    name: 'media',
                    path: 'media',
                    children: [
                        {
                            name: 'index',
                            path: '',
                            component: () => import('@/views/pages/Media/Index.vue'),
                            meta: {
                                breadcrumbs: [
                                    { label: 'Media' },
                                ],
                            },
                        },
                    ],
                },
            ],
        },
    ]) as RouteRecordRaw[],
})

router.beforeEach(async (to) => {
    const { ensureAuth } = useAuth()

    const requiresAuth = to.meta.requiresAuth ?? true

    const authenticated = await ensureAuth()

    if (requiresAuth && !authenticated) {
        return {
            name: 'login',
            query: { redirect: to.fullPath },
        }
    }

    if (to.name === 'login' && authenticated) {
        return { name: 'admin' }
    }

    return true
})

export default router
