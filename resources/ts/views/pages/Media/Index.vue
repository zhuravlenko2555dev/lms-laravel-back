<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "@plugins/api";
import { useDebounceFn } from "@vueuse/core";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import MediaGallery from "@/components/MediaGallery.vue";
import MediaUpload from "@/components/MediaUpload.vue";
import Record from "@/views/pages/Media/Record.vue";

const loading = ref(true)
const records = ref()
const pageReport = ref({
    first: 0,
    last: 0,
    totalRecords: 0,
})
const s = ref('')
const page = ref(1)
const perPage = ref(25)

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

onMounted(() => {
    processRoute()
    loadRecords()
})

watch(
    () => route.query,
    () => {
        processRoute()
        loadRecords()
    }
)

const loadRecords = () => {
    loading.value = true

    let q = '/api/media?'

    let params: any = {}
    if (s.value) params['s'] = s.value
    if (page.value > 1) params.page = page.value
    if (perPage.value !== 25) params['per-page'] = perPage.value

    for (const [k, v] of Object.entries(params)) {
        q += `&${k}=${v}`
    }

    api(q)
        .then(res => {
            records.value = res.data
            pageReport.value = {
                first: res.meta.from - 1,
                last: res.meta.to - 1,
                totalRecords: res.meta.total,
            }
            loading.value = false
        })
        .catch(() => {
            loading.value = false
        })
}
const confirmDeletion = (id) => {
    confirm.require({
        message: 'Are you sure you want to delete this media?',
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Delete',
            severity: 'danger'
        },
        accept: () => {
            deleteRecord(id)
        }
    })
}
const deleteRecord = (id) => {
    let q = `/api/media/${id}`

    api(q, { method: 'delete' })
        .then(res => {
            loadRecords()
            toast.add({ severity: 'success', summary: 'Success', detail: 'Media deleted!', life: 3000 })
        })
        .catch((err) => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Something went wrong!', life: 3000 })
        })
}

const onSearch = useDebounceFn((event) => {
    s.value = event.target.value
    page.value = 1

    pushToHistory()
}, 500)
const onPage = (event) => {
    page.value = event.page + 1

    pushToHistory()
}

const recordModalVisible = ref(false)

const menuOnIndex = ref(0)
const mediaMenuOptions = ref([
    {
        key: 'media-edit',
        label: 'Edit',
        icon: 'pi pi-pencil',
        command: () => {
            recordModalVisible.value = true
        }
    },
    {
        key: 'media-delete',
        label: 'Delete',
        icon: 'pi pi-trash',
        style: 'color: var(--p-red-500)',
        command: () => {
            confirmDeletion(records.value[menuOnIndex.value].id)
        }
    }
])

const mediaUploadVisible = ref(false)
const onUploaded = (media) => {
    records.value.unshift(media)
}

const processRoute = () => {
    const params = route.query

    s.value = params.s ?? ''
    page.value = params.page ? +params.page : 1
    perPage.value = params['per-page'] ? +params['per-page'] : 25
}
const pushToHistory = () => {
    let params: any = {}

    if (s.value) params['s'] = s.value
    if (page.value > 1) params.page = page.value
    if (perPage.value !== 25) params['per-page'] = perPage.value

    router.push({ name: 'media', query: params })
}
</script>

<template>
    <div class="card flex flex-col gap-4 overflow-auto relative">
        <Toolbar class="mb-4" :pt="{start: {class: 'gap-4'}, end: {class: 'gap-4'}}">
            <template #center>
                <Paginator
                    :first="pageReport.first"
                    :last="pageReport.last"
                    v-model:rows="perPage"
                    :total-records="pageReport.totalRecords"
                    :rows-per-page-options="[10, 25, 50, 100]"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    current-page-report-template="Showing {first} to {last} of {totalRecords} media"
                    @page="onPage"
                />
            </template>

            <template #end>
                <Button icon="pi pi-upload" rounded severity="secondary" @click="mediaUploadVisible = true" />
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText type="search" v-model="s" @input="onSearch" placeholder="Search..." />
                </IconField>
            </template>
        </Toolbar>

        <MediaGallery
            :media="records"
            v-model:menu-on-index="menuOnIndex"
            :media-menu-options="mediaMenuOptions"
        />

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

        <Dialog
            v-if="records?.length"
            v-model:visible="recordModalVisible"
            :style="{ width: '40vw' }"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
            :block-scroll="true"
            :header="`Media #${records[menuOnIndex].id}`"
        >
            <Record :id="records[menuOnIndex].id" />
        </Dialog>
    </div>
</template>
