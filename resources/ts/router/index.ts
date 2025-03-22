import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/admin',
            component: AppLayout,
            children: [
                {
                    path: 'books',
                    children: [
                        {
                            name: 'books',
                            path: '',
                            component: () => import('@/views/pages/Book/Index.vue'),
                            meta: {
                                breadcrumbs: [
                                    { label: 'Books' },
                                ]
                            }
                        },
                        {
                            name: 'books.create',
                            path: 'create',
                            component: () => import('@/views/pages/Book/Record.vue'),
                            meta: {
                                breadcrumbs: [
                                    { label: 'Books', to: '/admin/books' },
                                    { label: 'Create' },
                                ]
                            }
                        },
                        {
                            name: 'books.edit',
                            path: ':id',
                            component: () => import('@/views/pages/Book/Record.vue'),
                            meta: {
                                breadcrumbs: [
                                    { label: 'Books', to: '/admin/books' },
                                    { label: 'Edit' },
                                ]
                            }
                        }
                    ]
                }
            ]
        }
    ]
});

export default router;
