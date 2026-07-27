<script setup lang="ts">
import { computed, onMounted, ref, toRefs, watch } from 'vue'
import { useDebounceFn, useScroll } from '@vueuse/core'
import { useApi } from '@/composables/useApi'
import MediaGallery from '@/views/components/MediaGallery.vue'
import MediaUpload from '@/views/components/MediaUpload.vue'
import { Media, QueryParams, PageReport, ResourceCollectionResponse } from '@/types'

const { exceptIds } = defineProps<{
    exceptIds: number[]
}>()
const emit = defineEmits<{
    'add-media': [Media[]]
    'close': []
    'maximize': [event: Event]
}>()

const loading = ref<boolean>(true)
const records = ref<Media[]>()
const pageReport = ref<PageReport>({
    first: 0,
    last: 0,
    totalRecords: 0,
})
const append = ref<boolean>(false)
const s = ref<string>('')
const page = ref<number>(1)
const perPage = ref<number>(25)

const api = useApi()

onMounted(() => {
    loadRecords()
})

const loadRecords = (): void => {
    loading.value = true

    let q = '/api/media?'

    const params: QueryParams = {}
    if (exceptIds) params['except-ids'] = exceptIds
    if (s.value) params['s'] = s.value
    if (page.value > 1) params.page = page.value
    if (perPage.value > 25) params['per-page'] = perPage.value

    for (const [k, v] of Object.entries(params)) {
        if (Array.isArray(v)) {
            q += v.map(_v => `${k}[]=${_v}`).join('&')
        } else {
            q += `&${k}=${v}`
        }
    }

    api(q)
        .then((res: ResourceCollectionResponse<Media>) => {
            if (append.value) {
                records.value.push(...res.data)
            } else {
                records.value = res.data
            }
            pageReport.value = {
                first: res.meta.from,
                last: res.meta.to,
                totalRecords: res.meta.total,
            }
            loading.value = false
            append.value = false

            remeasure()
        })
        .catch(() => {
            loading.value = false
        })
}

const onSearch = useDebounceFn((event) => {
    s.value = event.target.value
    page.value = 1

    loadRecords()
}, 500)
const loadMore = (): void => {
    page.value++
    append.value = true

    loadRecords()
}

const selectedMediaIds = ref<number[]>([])
const selectedMedia = ref<Media[]>([])

watch(selectedMediaIds, () => {
    const oldIds = selectedMedia.value.map(v => v.id)
    const idsToRemove = oldIds.filter(v => !selectedMediaIds.value.includes(v))
    const idsToAdd = selectedMediaIds.value.filter(v => !oldIds.includes(v))

    const media = selectedMedia.value.filter(v => !idsToRemove.includes(v.id))
    media.push(...records.value.filter(v => idsToAdd.includes(v.id)))
    selectedMedia.value = media
}, { deep: true })

const mediaGalleryWrapper = ref<InstanceType<typeof HTMLElement>>()
const { arrivedState, measure } = useScroll(mediaGalleryWrapper)
const { bottom } = toRefs(arrivedState)

const remeasure = (): void => {
    setTimeout(() => measure(), 50)
}
const loadMoreVisible = computed<boolean>(() => {
    if (!records.value?.length) return false
    if (pageReport.value.last === pageReport.value.totalRecords) return false

    return bottom.value
})

const onAdd = (): void => {
    emit('add-media', selectedMedia.value)
    emit('close')
}
const onMaximize = (event: Event): void => {
    emit('maximize', event)
    remeasure()
}

const mediaUploadVisible = ref<boolean>(false)
const onUploaded = (media: Media): void => {
    records.value.unshift(media)
    selectedMediaIds.value.push(media.id)
}
</script>

<template>
    <div class="card flex flex-col gap-4 overflow-auto relative">
        <Toolbar class="mb-4" :pt="{end: {class: 'gap-4'}}">
            <template #start>
                <Chip v-if="selectedMediaIds.length" :label="`Selected (${selectedMediaIds.length})`" />
            </template>

            <template #center>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="s" type="search" placeholder="Search..." @input="onSearch" />
                </IconField>
            </template>

            <template #end>
                <Button icon="pi pi-upload" rounded severity="secondary" @click="mediaUploadVisible = true" />
                <Button icon="pi pi-window-minimize" rounded severity="secondary" @click="onMaximize" />
            </template>
        </Toolbar>

        <div ref="mediaGalleryWrapper" class="flex flex-col gap-4 overflow-auto overflow-x-hidden">
            <MediaGallery
                v-model:selected-media-ids="selectedMediaIds"
                :media="records"
                :selectable="true"
            />

            <div class="absolute left-0 bottom-24 w-full flex justify-center">
                <Transition name="load-more">
                    <Button
                        v-if="loadMoreVisible"
                        label="Load more"
                        icon="pi pi-refresh"
                        :fluid="false"
                        :loading="loading"
                        rounded
                        @click="loadMore"
                    />
                </Transition>
            </div>
        </div>

        <div class="flex justify-between">
            <span class="text-muted-color">Showing {{ pageReport.last }} of {{ pageReport.totalRecords }} media</span>

            <div class="flex flex-row gap-2">
                <Button
                    label="Add"
                    icon="pi pi-check"
                    :fluid="false"
                    :disabled="!selectedMedia.length"
                    @click="onAdd"
                />
                <Button
                    label="Close"
                    icon="pi pi-times"
                    severity="secondary"
                    :fluid="false"
                    @click="emit('close')"
                />
            </div>
        </div>

        <Dialog
            v-model:visible="mediaUploadVisible"
            class="overflow-auto"
            :style="{ width: '40vw' }"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
            :block-scroll="true"
        >
            <template #container="{ closeCallback }">
                <MediaUpload
                    @uploaded="onUploaded"
                    @close="closeCallback"
                />
            </template>
        </Dialog>
    </div>
</template>

<style scoped lang="scss">
.load-more-move,
.load-more-enter-active,
.load-more-leave-active {
    transition: all 0.4s cubic-bezier(0.55, 0, 0.1, 1);
}

.load-more-enter-from,
.load-more-leave-to {
    opacity: 0;
    transform: scaleY(0.01) translate(0, 30px);
}
</style>
