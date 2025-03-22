<script setup lang="ts">
import { computed, ref } from "vue";

const {
    media,
    menuOnIndex,
    mediaMenuOptions = [],
    selectedMediaIds = [],
} = defineProps([
    'media',
    'menuOnIndex',
    'mediaMenuOptions',
    'selectedMediaIds',
])
const emit = defineEmits([
    'toggle-on-index',
    'update:menu-on-index',
    'update:selected-media-ids',
])

const visible = ref(false)
const activeIndex = ref(0)

const mediaMenu = ref()
const mediaMenuVisible = computed(() => {
    return !selectedMediaIds.length && mediaMenuOptions.length
})

const itemClick = (index) => {
    activeIndex.value = index
    visible.value = true
}

const onMediaMenuToggle = (event, index) => {
    emit('update:menu-on-index', index)
    mediaMenu.value.toggle(event)
}
const onMediaSelect = (event) => {
    let value = +event.target.value
    let ids

    if (event.target.checked) {
        ids = [...selectedMediaIds, value]
    } else {
        ids = selectedMediaIds.filter((v) => v !== value)
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
            containerStyle="max-width: 500px"
            :circular="true"
            :full-screen="true"
            :show-item-navigators="true"
            :show-thumbnails="false"
        >
            <template #item="slotProps">
                <img :src="slotProps.item.url" :alt="slotProps.item.alt" style="width: 100%; display: block" />
            </template>
        </Galleria>

        <TransitionGroup name="media-container" tag="div" :class="`grid grid-cols-[repeat(auto-fill,_minmax(10rem,_1fr))] gap-4`">
            <div
                class="media-item col-span-1 aspect-square relative"
                v-for="(media, index) of media"
                :key="media.id"
            >
                <img
                    class="max-h-full w-full h-full object-cover rounded-3xl"
                    style="cursor: pointer"
                    :src="media.url"
                    :alt="media.alt"
                    @click="itemClick(index)"
                />

                <div class="absolute left-0 top-0 w-full flex items-center justify-between">
                    <Checkbox
                        class="ml-3 items-center"
                        style="height: 2.5rem"
                        :model-value="selectedMediaIds"
                        @change="onMediaSelect"
                        :value="media.id"
                    />

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

            <Menu
                ref="mediaMenu"
                id="mediaOverlayMenu"
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
        </TransitionGroup>
    </div>
    <div v-else class="flex items-center justify-center">
        <div class="flex-col">
            <i class="pi pi-images block text-center" style="font-size: 7.5rem; color: var(--p-primary-color)" />
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
