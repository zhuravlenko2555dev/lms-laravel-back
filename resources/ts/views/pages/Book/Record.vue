<script setup lang="ts">
import { onMounted, ref} from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "@plugins/api";
import { useDebounceFn } from "@vueuse/core";
import MediaPicker from "@/components/MediaPicker.vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const isEditing = ref(false)
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const record = ref({
    id: null,
    olid: null,
    isbn: null,
    name: null,
    publish_year: 0,
    description: null,
    publisher_id: null,
    author_ids: [],
    authors: [],
    genre_ids: [],
    genres: [],
    subject_ids: [],
    subjects: {
        subject: [],
        place: [],
        people: [],
        time: [],
    },
    cover_ids: [],
    covers: [],
    created_at: null,
    updated_at: null,
})
const recordName = ref()
const errors = ref({})

const authors = ref([])
const authorsLoading = ref(false)
const genres = ref([])
const genresLoading = ref(false)
const publishers = ref([])
const publishersLoading = ref(false)
const subjects = ref({
    subject: null,
    place: null,
    people: null,
    time: null,
})
const subjectsLoading = ref({
    subject: false,
    place: false,
    people: false,
    time: false,
})

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

onMounted(() => {
    isEditing.value = !!route.params.id

    if (isEditing.value) loadRecord()
})

const loadRecord = () => {
    loading.value = true

    let q = `/api/books/${route.params.id}`

    api(q)
        .then(res => {
            let { subjects, ...data } = res.data

            if (data.publisher) {
                data.publisher_id = data.publisher.id
                publishers.value = [data.publisher]
                delete data.publisher
            }

            if (subjects) {
                data.subjects = {
                    subject: subjects.filter((v) => v.type === 1),
                    place: subjects.filter((v) => v.type === 2),
                    people: subjects.filter((v) => v.type === 3),
                    time: subjects.filter((v) => v.type === 4),
                }
            }

            record.value = data
            recordName.value = record.value.name

            loading.value = false
        })
        .catch(() => {
            loading.value = false
        })
}
const saveRecord = () => {
    saving.value = true
    errors.value = {}

    let q = `/api/books/${isEditing.value ? route.params.id : ''}`
    let method = isEditing.value ? 'put' : 'post'

    record.value.author_ids = record.value.authors.map(({ id }) => id)
    record.value.genre_ids = record.value.genres.map(({ id }) => id)
    record.value.cover_ids = record.value.covers.map(({ id }) => id) ?? null
    record.value.subject_ids = []
    Object.keys(record.value.subjects)
        .forEach((k) => {
            if (record.value.subjects[k].length) record.value.subject_ids.push(...record.value.subjects[k].map(({ id }) => id))
        })

    const { authors, genres, covers, subjects, ...data } = record.value

    api(q, { method: method, body: data })
        .then(res => {
            saving.value = false
            if (isEditing.value) {
                recordName.value = record.value.name
            } else {
                router.push({ name: 'books.edit', params: { id:  res.data.id} })
            }
            toast.add({ severity: 'success', summary: 'Success', detail: 'Book info saved!', life: 3000 })
        })
        .catch((err) => {
            errors.value = err.data.errors
            saving.value = false
            toast.add({ severity: 'error', summary: 'Error', detail: err.data.message, life: 3000 })
        })
}
const confirmDeletion = () => {
    confirm.require({
        message: 'Are you sure you want to delete this book?',
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
            deleteRecord()
        }
    })
}
const deleteRecord = () => {
    deleting.value = true

    let q = `/api/books/${route.params.id}`

    api(q, { method: 'delete' })
        .then(res => {
            deleting.value = false
            router.push({ name: 'books' })
            toast.add({ severity: 'success', summary: 'Success', detail: 'Book deleted!', life: 3000 })
        })
        .catch((err) => {
            deleting.value = false
            toast.add({ severity: 'error', summary: 'Error', detail: 'Something went wrong!', life: 3000 })
        })
}

const onPublishersSearch = useDebounceFn((event) => {
    publishersLoading.value = true

    let q = `/api/publishers?s=${event.value}`

    api(q)
        .then(res => {
            publishers.value = [
                ...publishers.value.filter((v) => record.value.publisher_id === v.id),
                ...res.data
                    .filter((v) => record.value.publisher_id !== v.id)
            ]
            publishersLoading.value = false
        })
        .catch(() => {
            publishersLoading.value = false
        })

}, 500)
const onAuthorsSearch = useDebounceFn((event) => {
    authorsLoading.value = true

    let q = `/api/authors?s=${event.query}`

    api(q)
        .then(res => {
            authors.value = res.data
            authorsLoading.value = false
        })
        .catch(() => {
            authorsLoading.value = false
        })

}, 500)
const onGenresSearch = useDebounceFn((event) => {
    genresLoading.value = true

    let q = `/api/genres?s=${event.query}`

    api(q)
        .then(res => {
            genres.value = res.data
            genresLoading.value = false
        })
        .catch(() => {
            genresLoading.value = false
        })

}, 500)
const onSubjectsSearch = useDebounceFn((event, type) => {
    subjectsLoading.value[type] = true

    let q = `/api/subjects?s=${event.query}&type=${type}`

    api(q)
        .then(res => {
            subjects.value[type] = res.data
            subjectsLoading.value[type] = false
        })
        .catch(() => {
            subjectsLoading.value[type] = false
        })

}, 500)
</script>

<template>
    <div class="font-semibold text-xl mb-4">{{ recordName }}</div>
    <form @submit.prevent="">
        <Fluid class="flex flex-col md:flex-row gap-8">
            <div class="md:w-10/12">
                <div class="card flex flex-col gap-4">
                    <div>
                        <label for="name" class="block font-bold mb-3 required">Name</label>
                        <InputText id="name" v-model="record.name" required="true" :invalid="errors?.name?.length" />
                        <Message v-if="errors?.name?.length" severity="error" variant="simple">{{ errors?.name[0] }}</Message>
                    </div>
                    <div>
                        <label for="description" class="block font-bold mb-3">Description</label>
                        <Textarea id="description" v-model="record.description" required="true" :invalid="errors?.description?.length" />
                        <Message v-if="errors?.description?.length" severity="error" variant="simple">{{ errors?.description[0] }}</Message>
                    </div>
                </div>

                <div class="card flex flex-col md:flex-row gap-4">
                    <div class="md:w-1/4">
                        <label for="olid" class="block font-bold mb-3">OLID</label>
                        <InputText id="olid" v-model="record.olid" required="true" :invalid="errors?.olid?.length" />
                        <Message v-if="errors?.olid?.length" severity="error" variant="simple">{{ errors?.olid[0] }}</Message>
                    </div>
                    <div class="md:w-1/4">
                        <label for="isbn" class="block font-bold mb-3">ISBN</label>
                        <InputText id="isbn" v-model="record.isbn" required="true" :invalid="errors?.isbn?.length" />
                        <Message v-if="errors?.isbn?.length" severity="error" variant="simple">{{ errors?.isbn[0] }}</Message>
                    </div>
                    <div class="md:w-1/4">
                        <label for="publish_year" class="block font-bold mb-3">Publish year</label>
                        <InputNumber id="publish_year" v-model="record.publish_year" :use-grouping="false" required="true" :invalid="errors?.publish_year?.length" />
                        <Message v-if="errors?.publish_year?.length" severity="error" variant="simple">{{ errors?.publish_year[0] }}</Message>
                    </div>
                    <div class="md:w-1/4">
                        <label for="publisher_id" class="block font-bold mb-3">Publisher</label>
                        <Select
                            id="publisher_id"
                            v-model="record.publisher_id"
                            :options="publishers"
                            option-value="id"
                            option-label="name"
                            :filter="true"
                            @filter="onPublishersSearch"
                            :auto-filter-focus="true"
                            :reset-filter-on-hide="true"
                            :loading="publishersLoading"
                            placeholder="Search..."
                        />
                        <Message v-if="errors?.publisher_id?.length" severity="error" variant="simple">{{ errors?.publisher_id[0] }}</Message>
                    </div>
                </div>

                <div class="card">
                    <Tabs value="authors" scrollable>
                        <TabList>
                            <Tab value="authors" class="flex items-center gap-2">
                                <i class="pi pi-users" />
                                <span class="required">Authors</span>
                            </Tab>
                            <Tab value="genres" class="flex items-center gap-2">
                                <i class="pi pi-hashtag" />
                                <span class="required">Genres</span>
                            </Tab>
                            <Tab value="subjects" class="flex items-center gap-2">
                                <i class="pi pi-hashtag" />
                                <span>Subjects</span>
                            </Tab>
                            <Tab value="covers" class="flex items-center gap-2">
                                <i class="pi pi-images" />
                                <span>Covers</span>
                            </Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="authors">
                                <AutoComplete
                                    v-model="record.authors"
                                    :suggestions="authors"
                                    data-key="id"
                                    option-label="name"
                                    @complete="onAuthorsSearch"
                                    :loading="authorsLoading"
                                    placeholder="Search..."
                                    multiple
                                />
                                <Message v-if="errors?.author_ids?.length" severity="error" variant="simple">{{ errors?.author_ids[0] }}</Message>
                            </TabPanel>
                            <TabPanel value="genres">
                                <AutoComplete
                                    v-model="record.genres"
                                    :suggestions="genres"
                                    data-key="id"
                                    option-label="name"
                                    @complete="onGenresSearch"
                                    :loading="genresLoading"
                                    placeholder="Search..."
                                    multiple
                                />
                                <Message v-if="errors?.genre_ids?.length" severity="error" variant="simple">{{ errors?.genre_ids[0] }}</Message>
                            </TabPanel>
                            <TabPanel value="subjects">
                                <Fieldset
                                    v-for="k in Object.keys(record.subjects)"
                                    :pt="{
                                        legend: {
                                            class: 'flex items-center gap-2'
                                        }
                                    }"
                                >
                                    <template #legend>
                                        <i class="pi pi-users"></i>
                                        <span class="font-bold capitalize">{{ k }}s</span>
                                    </template>
                                    <AutoComplete
                                        v-model="record.subjects[k]"
                                        :suggestions="subjects[k]"
                                        data-key="id"
                                        option-label="name"
                                        @complete="onSubjectsSearch($event, k)"
                                        :loading="subjectsLoading[k]"
                                        placeholder="Search..."
                                        multiple
                                    />
                                </Fieldset>
                                <Message v-if="errors?.subject_ids?.length" severity="error" variant="simple">{{ errors?.subject_ids[0] }}</Message>
                            </TabPanel>
                            <TabPanel value="covers">
                                <MediaPicker v-model="record.covers" />
                                <Message v-if="errors?.cover_ids?.length" severity="error" variant="simple">{{ errors?.cover_ids[0] }}</Message>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </div>
            </div>
            <div class="md:w-2/12">
                <div class="card flex flex-col gap-4">
                    <div v-if="isEditing" class="flex flex-col gap-4">
                        <div>
                            <div class="font-bold mb-1">Created at</div>
                            <span class="text-sm text-muted-color">{{ record.created_at }}</span>
                        </div>
                        <div>
                            <div class="font-bold mb-1">Updated at</div>
                            <span class="text-sm text-muted-color">{{ record.updated_at }}</span>
                        </div>
                        <Divider />
                    </div>

                    <div class="flex gap-4">
                        <Button :loading="saving" @click="saveRecord">Save</Button>
                        <Button v-if="isEditing" severity="danger" @click="confirmDeletion">Delete</Button>
                    </div>
                </div>
            </div>
        </Fluid>
    </form>
</template>
