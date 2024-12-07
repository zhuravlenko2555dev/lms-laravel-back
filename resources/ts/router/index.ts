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
                            component: () => import('@/views/pages/Book/Index.vue')
                        }
                    ]
                }
            ]
        }
    ]
});

export default router;
