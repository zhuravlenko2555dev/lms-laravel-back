<script setup lang="ts">
import { computed, ref } from 'vue'
import Menu from 'primevue/menu'
import { MenuItem } from 'primevue/menuitem'
import { Media } from '@/types'

const {
    media,
    mediaMenuOptions = [],
    selectable = false,
    selectedMediaIds = [],
} = defineProps<{
    media: Media[]
    mediaMenuOptions?: MenuItem[]
    selectable?: boolean
    selectedMediaIds?: number[]
}>()
const emit = defineEmits<{
    'update:menu-on-index': [number]
    'update:selected-media-ids': [number[]]
}>()

const visible = ref<boolean>(false)
const activeIndex = ref<number>(0)

const mediaMenu = ref<InstanceType<typeof Menu>>()
const mediaMenuVisible = computed<boolean>(() => {
    return selectedMediaIds.length === 0 && mediaMenuOptions.length > 0
})

const itemClick = (index: number): void => {
    activeIndex.value = index
    visible.value = true
}

const onMediaMenuToggle = (event: MouseEvent, index: number): void => {
    emit('update:menu-on-index', index)
    mediaMenu.value.toggle(event)
}
const onMediaSelect = (event: Event): void => {
    const target = event.target as HTMLInputElement
    const value = Number(target.value)
    let ids: number[]

    if (target.checked) {
        ids = [...selectedMediaIds, value]
    } else {
        ids = selectedMediaIds.filter(v => v !== value)
    }

    emit('update:selected-media-ids', ids)
}
</script>

<template>
    <div v-if="media?.length">
        <Galleria
            v-model:active-index="activeIndex"
            v-model:visible="visible"
            :value="media"
            container-style="max-width: 500px"
            :circular="true"
            :full-screen="true"
            :show-item-navigators="true"
            :show-thumbnails="false"
        >
            <template #item="slotProps">
                <img :src="slotProps.item.url" :alt="slotProps.item.alt" style="width: 100%; display: block">
            </template>
        </Galleria>

        <TransitionGroup name="media-container" tag="div" class="grid grid-cols-[repeat(auto-fill,_minmax(10rem,_1fr))] gap-4">
            <div
                v-for="(item, index) of media"
                :key="`media-item-${item.id}`"
                class="media-item col-span-1 aspect-square relative"
            >
                <img
                    class="max-h-full w-full h-full object-cover rounded-3xl"
                    style="cursor: pointer"
                    :src="item.url"
                    :alt="item.alt"
                    @click="itemClick(index)"
                >

                <div class="absolute left-0 top-0 w-full flex items-center justify-between">
                    <div>
                        <Checkbox
                            v-show="selectable"
                            class="ml-3 items-center"
                            style="height: 2.5rem"
                            :model-value="selectedMediaIds"
                            :value="item.id"
                            @change="onMediaSelect"
                        />
                    </div>

                    <div>
                        <Button
                            v-show="mediaMenuVisible"
                            icon="pi pi-ellipsis-v"
                            aria-haspopup="true"
                            aria-controls="mediaOverlayMenu"
                            rounded
                            severity="secondary"
                            @click="onMediaMenuToggle($event, index)"
                        />
                    </div>
                </div>
            </div>
        </TransitionGroup>

        <Menu
            id="mediaOverlayMenu"
            ref="mediaMenu"
            :model="mediaMenuOptions"
            :popup="true"
            :dt="{
                item: {
                    color: 'none',
                    focusColor: 'none',
                    icon: {
                        color: 'none',
                        focusColor: 'none'
                    }
                }
            }"
        />
    </div>
    <div v-else class="flex items-center justify-center">
        <div class="flex-col">
            <i class="pi pi-images block text-center text-primary" style="font-size: 7.5rem" />
            <span class="text-3xl">No media yet...</span>
        </div>
    </div>
</template>

<style scoped lang="scss">
.media-container-move,
.media-container-enter-active,
.media-container-leave-active {
    transition: all 0.4s cubic-bezier(0.55, 0, 0.1, 1);
}

.media-container-enter-from,
.media-container-leave-to {
    opacity: 0;
    transform: scaleY(0.01) translate(30px, 0);
}
</style>
