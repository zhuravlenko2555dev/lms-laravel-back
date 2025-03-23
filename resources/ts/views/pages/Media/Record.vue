<script setup lang="ts">
import { onMounted, ref } from "vue";
import api from "@plugins/api";
import { useToast } from "primevue/usetoast";

const { id } = defineProps(['id'])

const loading = ref(true)
const saving = ref(false)
const record = ref({
    disk: null,
    directory: null,
    name: null,
    path: null,
    width: null,
    height: null,
    size: null,
    type: null,
    ext: null,
    alt: null,
    title: null,
    sizes: null,
    url: null,
    size_for_humans: null,
    pretty_name: null,
})
const errors = ref({})

const toast = useToast()

onMounted(() => {
    loadRecord()
})

const loadRecord = () => {
    loading.value = true

    let q = `/api/media/${id}`

    api(q)
        .then(res => {
            record.value = res.data
            loading.value = false
        })
        .catch(() => {
            loading.value = false
        })
}
const saveRecord = () => {
    saving.value = true
    errors.value = {}

    let q = `/api/media/${record.value.id}`

    api(q, { method: 'put', body: record.value })
        .then(res => {
            saving.value = false
            toast.add({ severity: 'success', summary: 'Success', detail: 'Media info saved!', life: 3000 })
        })
        .catch((err) => {
            errors.value = err.data.errors
            saving.value = false
            toast.add({ severity: 'error', summary: 'Error', detail: err.data.message, life: 3000 })
        })
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col md:flex-row gap-8">
            <div class="md:w-1/3">
                <Image
                    class="h-full"
                    :src="record.url"
                    :alt="record.alt"
                    preview
                    :pt="{
                    image: {
                        class: 'object-contain'
                    }
                }"
                />
            </div>
            <div class="md:w-2/3">
                <Tabs value="info" scrollable>
                    <TabList>
                        <Tab value="info" class="flex items-center gap-2">
                            <i class="pi pi-info-circle" />
                            <span>File info</span>
                        </Tab>
                        <Tab value="seo" class="flex items-center gap-2">
                            <i class="pi pi-globe" />
                            <span>SEO</span>
                        </Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="info">
                            <div class="flex flex-col gap-4">
                                <div>
                                    <div class="font-bold mb-1">Name</div>
                                    <span class="text-muted-color">{{ record.pretty_name }}</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <div class="font-bold mb-1">Disk</div>
                                        <span class="text-muted-color">{{ record.disk }}</span>
                                    </div>
                                    <div>
                                        <div class="font-bold mb-1">Directory</div>
                                        <span class="text-muted-color">{{ record.directory }}</span>
                                    </div>
                                    <div>
                                        <div class="font-bold mb-1">Dimensions</div>
                                        <span class="text-muted-color">{{ record.width }} x {{ record.height }}</span>
                                    </div>
                                    <div>
                                        <div class="font-bold mb-1">Size</div>
                                        <span class="text-muted-color">{{ record.size_for_humans }}</span>
                                    </div>
                                    <div>
                                        <div class="font-bold mb-1">Type</div>
                                        <span class="text-muted-color">{{ record.type }}</span>
                                    </div>
                                    <div v-if="record.sizes?.length">
                                        <div class="font-bold mb-1">Generated sizes</div>
                                        <span class="text-muted-color">
                                        {{ record.sizes?.map((v) => `${v}w`).join(', ') }}
                                    </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="font-bold mb-1">URL</div>
                                    <a class="text-muted-color" :href="record.url" target="_blank">
                                        {{ record.url }}
                                    </a>
                                </div>
                            </div>
                        </TabPanel>
                        <TabPanel value="seo">
                            <Fluid class="flex flex-col gap-4">
                                <div>
                                    <label for="alt" class="block font-bold mb-3">Alt</label>
                                    <Textarea id="alt" v-model="record.alt" maxlength="125" :invalid="errors?.alt?.length" />
                                    <Message v-if="errors?.alt?.length" severity="error" variant="simple">{{ errors?.alt[0] }}</Message>
                                </div>
                                <div>
                                    <label for="title" class="block font-bold mb-3">Title</label>
                                    <Textarea id="title" v-model="record.title" maxlength="125" :invalid="errors?.title?.length" />
                                    <Message v-if="errors?.title?.length" severity="error" variant="simple">{{ errors?.title[0] }}</Message>
                                </div>
                            </Fluid>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>

        <div class="flex justify-end">
            <Button label="Save" :fluid="false" @click="saveRecord" />
        </div>
    </div>
</template>
