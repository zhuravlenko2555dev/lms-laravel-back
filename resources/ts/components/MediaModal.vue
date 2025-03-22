<script setup lang="ts">
import { computed, onMounted, ref, toRefs, watch } from "vue";
import api from "@plugins/api";
import { useDebounceFn, useScroll } from "@vueuse/core";
import MediaGallery from "@/components/MediaGallery.vue";
import MediaUpload from "@/components/MediaUpload.vue";

const { exceptIds } = defineProps(['exceptIds'])
const emit = defineEmits(['add-media', 'close', 'maximize'])

const loading = ref(true)
const records = ref()
const pageReport = ref({
    first: 0,
    last: 0,
    totalRecords: 0,
})
const append = ref(false)
const s = ref('')
const page = ref(1)
const perPage = ref(25)

onMounted(() => {
    loadRecords()
})

const loadRecords = () => {
    loading.value = true

    let q = '/api/media?'

    let params: any = {}
    if (exceptIds) params['except-ids'] = exceptIds
    if (s.value) params['s'] = s.value
    if (page.value > 1) params.page = page.value
    if (perPage.value > 25) params['per-page'] = perPage.value

    for (const [k, v] of Object.entries(params)) {
        if (Array.isArray(v)) {
            q += v.map((_v) => `${k}[]=${_v}`).join('&')
        } else {
            q += `&${k}=${v}`
        }
    }

    api(q)
        .then(res => {
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
const loadMore = () => {
    page.value++
    append.value = true

    loadRecords()
}

const selectedMediaIds = ref([])
const selectedMedia = ref([])

watch(selectedMediaIds, () => {
    const oldIds = selectedMedia.value.map((v) => v.id)
    const idsToRemove = oldIds.filter((v) => !selectedMediaIds.value.includes(v))
    const idsToAdd = selectedMediaIds.value.filter((v) => !oldIds.includes(v))

    let media = selectedMedia.value.filter((v) => !idsToRemove.includes(v.id))
    media.push(...records.value.filter((v) => idsToAdd.includes(v.id)))
    selectedMedia.value = media
}, { deep: true })

const mediaGalleryWrapper = ref()
const { arrivedState, measure } = useScroll(mediaGalleryWrapper)
const { bottom } = toRefs(arrivedState)

const remeasure = () => {
    setTimeout(() => measure(), 50)
}
const loadMoreVisible = computed(() => {
    if (!records.value?.length) return false
    if (pageReport.value.last === pageReport.value.totalRecords) return false

    return bottom.value;
})

const onAdd = () => {
    emit('add-media', selectedMedia.value)
    emit('close')
}
const onMaximize = () => {
    emit('maximize')
    remeasure()
}

const mediaUploadVisible = ref(false)
const onUploaded = (media) => {
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
                    <InputText type="search" v-model="s" @input="onSearch" placeholder="Search..." />
                </IconField>
            </template>

            <template #end>
                <Button icon="pi pi-upload" rounded severity="secondary" @click="mediaUploadVisible = true" />
                <Button icon="pi pi-window-minimize" rounded severity="secondary" @click="onMaximize" />
            </template>
        </Toolbar>

        <div ref="mediaGalleryWrapper" class="flex flex-col gap-4 overflow-auto overflow-x-hidden">
            <MediaGallery
                :media="records"
                v-model:selected-media-ids="selectedMediaIds"
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
            class="overflow-auto"
            v-model:visible="mediaUploadVisible"
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
