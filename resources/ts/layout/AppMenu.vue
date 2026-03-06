<script setup lang="ts">
import { ref } from 'vue'
import AppMenuItem from './AppMenuItem.vue'
import { MenuItem } from '@/types'

const model = ref<Array<MenuItem>>([
    {
        label: 'Home',
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/admin' }],
    },
    {
        label: 'Resources',
        items: [
            { label: 'Books', icon: 'pi pi-fw pi-book', to: '/admin/books' },
            { label: 'Media', icon: 'pi pi-fw pi-image', to: '/admin/media' },
        ],
    },
])
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="i">
            <app-menu-item v-if="!item.separator" :item="item" :index="i" />
            <li v-if="item.separator" class="menu-separator" />
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
