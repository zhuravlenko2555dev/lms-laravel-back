<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useLayout } from '@/layout/composables/layout'
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'
import type { Breadcrumb } from '@/types/ui'

const { layoutConfig, layoutState, isSidebarActive, resetMenu } = useLayout()
const route = useRoute()

let outsideClickListener: ((event: MouseEvent) => void) | null = null

watch(isSidebarActive, (newVal) => {
    if (newVal) {
        bindOutsideClickListener()
    } else {
        unbindOutsideClickListener()
    }
})

const containerClass = computed<Record<string, boolean>>(() => {
    return {
        'layout-overlay': layoutConfig.menuMode === 'overlay',
        'layout-static': layoutConfig.menuMode === 'static',
        'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
        'layout-overlay-active': !!layoutState.overlayMenuActive,
        'layout-mobile-active': !!layoutState.staticMenuMobileActive,
    }
})

const breadcrumbs = computed<Breadcrumb[]>(() => {
    return (route.meta?.breadcrumbs as Breadcrumb[]) ?? []
})

function bindOutsideClickListener(): void {
    if (!outsideClickListener) {
        outsideClickListener = (event: MouseEvent) => {
            if (isOutsideClicked(event)) {
                resetMenu()
            }
        }
        document.addEventListener('click', outsideClickListener)
    }
}

function unbindOutsideClickListener(): void {
    if (outsideClickListener) {
        document.removeEventListener('click', outsideClickListener)
        outsideClickListener = null
    }
}

function isOutsideClicked(event: Event): boolean {
    const sidebarEl = document.querySelector('.layout-sidebar')
    const topbarEl = document.querySelector('.layout-menu-button')

    const isTarget = (el: Element | null) => el && (el.isSameNode(event.target as Node) || el.contains(event.target as Node))

    return !(isTarget(sidebarEl) || isTarget(topbarEl))
}
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <app-topbar />
        <app-sidebar />
        <div class="layout-main-container">
            <div class="layout-main">
                <Breadcrumb v-if="breadcrumbs.length" :home="{ icon: 'pi pi-home', to: '/admin' }" :model="breadcrumbs">
                    <template #item="{ item, props }">
                        <router-link v-if="item.to" v-slot="{ href, navigate }" :to="item.to" custom>
                            <a :href="href" v-bind="props.action" @click="navigate">
                                <span :class="[item.icon, 'text-color']" />
                                <span class="text-primary font-semibold">{{ item.label }}</span>
                            </a>
                        </router-link>
                        <a v-else :target="item.target" v-bind="props.action">
                            <span class="text-surface-700 dark:text-surface-0">{{ item.label }}</span>
                        </a>
                    </template>
                </Breadcrumb>
                <router-view />
            </div>
        </div>
        <div class="layout-mask animate-fadein" />
    </div>
    <Toast />
    <ConfirmDialog />
</template>
