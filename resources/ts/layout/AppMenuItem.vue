<script setup lang="ts">
import { onBeforeMount, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useLayout } from '@/layout/composables/layout'
import { MenuItem } from '@/types'

const route = useRoute()

const { layoutState, setActiveMenuItem, onMenuToggle } = useLayout()

const props = withDefaults(defineProps<{
    item: MenuItem
    index: number
    root?: boolean
    parentItemKey?: string
}>(), {
    root: true,
    parentItemKey: null,
})

const isActiveMenu = ref<boolean>(false)
const itemKey = ref<string>(null)

onBeforeMount(() => {
    itemKey.value = props.parentItemKey ? props.parentItemKey + '-' + props.index : String(props.index)

    const activeItem = layoutState.activeMenuItem

    isActiveMenu.value = activeItem === itemKey.value || activeItem ? activeItem.startsWith(itemKey.value + '-') : false
})

watch(
    () => layoutState.activeMenuItem,
    (newVal) => {
        isActiveMenu.value = newVal === itemKey.value || newVal.startsWith(itemKey.value + '-')
    },
)

function itemClick(event: MouseEvent, item: MenuItem): void {
    if (item.disabled) {
        event.preventDefault()
        return
    }

    if ((item.to || item.url) && (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive)) {
        onMenuToggle()
    }

    if (item.command) {
        item.command({ originalEvent: event, item: item })
    }

    const foundItemKey = item.items ? (isActiveMenu.value ? props.parentItemKey : itemKey.value) : itemKey.value

    setActiveMenuItem(foundItemKey)
}

function checkActiveRoute(item: MenuItem): boolean {
    return route.path === item.to
}
</script>

<template>
    <li :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu }">
        <div v-if="root && item.visible !== false" class="layout-menuitem-root-text">{{ item.label }}</div>
        <a v-if="(!item.to || item.items) && item.visible !== false" :href="item.url" :class="item.class" :target="item.target" tabindex="0" @click="itemClick($event, item)">
            <i :class="item.icon" class="layout-menuitem-icon" />
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i v-if="item.items" class="pi pi-fw pi-angle-down layout-submenu-toggler" />
        </a>
        <router-link v-if="item.to && !item.items && item.visible !== false" :class="[item.class, { 'active-route': checkActiveRoute(item) }]" tabindex="0" :to="item.to" @click="itemClick($event, item)">
            <i :class="item.icon" class="layout-menuitem-icon" />
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i v-if="item.items" class="pi pi-fw pi-angle-down layout-submenu-toggler" />
        </router-link>
        <Transition v-if="item.items && item.visible !== false" name="layout-submenu">
            <ul v-show="root ? true : isActiveMenu" class="layout-submenu">
                <app-menu-item v-for="(child, i) in item.items" :key="itemKey + '-' + i" :index="i" :item="child" :parent-item-key="itemKey" :root="false" />
            </ul>
        </Transition>
    </li>
</template>

<style lang="scss" scoped></style>
