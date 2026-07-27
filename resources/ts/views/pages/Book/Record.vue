<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useDebounceFn } from '@vueuse/core'
import { useApi } from '@/composables/useApi'
import MediaPicker from '@/views/components/MediaPicker.vue'
import { SelectFilterEvent } from 'primevue/select'
import { AutoCompleteCompleteEvent } from 'primevue/autocomplete'
import { FetchError } from 'ofetch'
import {
    BaseModel, Book, BookDTO, Author, Genre, Publisher, Subject,
    ResourceCollectionResponse, ResourceResponse, ValidationErrorsResponse,
} from '@/types'

type FormRecord = Omit<Book, 'subjects' | 'publisher'> & BookDTO & { subjects: Record<string, Subject[]> }

const isEditing = ref<boolean>(false)
const loading = ref<boolean>(true)
const saving = ref<boolean>(false)
const deleting = ref<boolean>(false)
const record = ref<FormRecord>({
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
const recordName = ref<string>()
const errors = ref<ValidationErrorsResponse['errors']>({})

const authors = ref<Author[]>([])
const authorsLoading = ref<boolean>(false)
const genres = ref<Genre[]>([])
const genresLoading = ref<boolean>(false)
const publishers = ref<Publisher[]>([])
const publishersLoading = ref<boolean>(false)
const subjects = ref<Record<string, Subject[]>>({
    subject: [],
    place: [],
    people: [],
    time: [],
})
const subjectsLoading = ref<Record<string, boolean>>({
    subject: false,
    place: false,
    people: false,
    time: false,
})

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const api = useApi()

onMounted(() => {
    isEditing.value = !!route.params.id

    if (isEditing.value) loadRecord()
})

watch(
    () => route.query,
    () => {
        isEditing.value = !!route.params.id

        if (isEditing.value) loadRecord()
    },
)

const loadRecord = (): void => {
    loading.value = true

    const q = `/api/books/${route.params.id}`

    api(q)
        .then((res: ResourceResponse<Book>) => {
            const { publisher, subjects, ...data } = res.data

            if (publisher) {
                publishers.value = [publisher]
            }

            record.value = {
                ...data,
                publisher_id: publisher?.id,
                author_ids: [],
                genre_ids: [],
                subject_ids: [],
                cover_ids: [],
                subjects: {
                    subject: subjects?.filter(v => v.type === 1) || [],
                    place: subjects?.filter(v => v.type === 2) || [],
                    people: subjects?.filter(v => v.type === 3) || [],
                    time: subjects?.filter(v => v.type === 4) || [],
                },
            }
            recordName.value = record.value.name

            loading.value = false
        })
        .catch(() => {
            loading.value = false
        })
}
const saveRecord = (): void => {
    saving.value = true
    errors.value = {}

    const q = `/api/books/${isEditing.value ? route.params.id : ''}`
    const method = isEditing.value ? 'put' : 'post'

    record.value.author_ids = record.value.authors.map(({ id }) => id)
    record.value.genre_ids = record.value.genres.map(({ id }) => id)
    record.value.cover_ids = record.value.covers.map(({ id }) => id) ?? null
    record.value.subject_ids = []
    Object.keys(record.value.subjects)
        .forEach((k) => {
            if (record.value.subjects[k].length) record.value.subject_ids.push(...record.value.subjects[k].map(({ id }) => id))
        })

    const data = { ...record.value }
    delete data.authors
    delete data.genres
    delete data.covers
    delete data.subjects

    api(q, { method: method, body: data })
        .then((res: ResourceResponse<Book>) => {
            saving.value = false
            if (isEditing.value) {
                recordName.value = record.value.name
            } else {
                router.push({ name: 'admin.books.edit', params: { id: res.data.id } })
            }
            toast.add({ severity: 'success', summary: 'Success', detail: 'Book info saved!', life: 3000 })
        })
        .catch((err: FetchError) => {
            const r = err.data as ValidationErrorsResponse

            errors.value = r.errors
            saving.value = false
            toast.add({ severity: 'error', summary: 'Error', detail: r.message, life: 3000 })
        })
}
const confirmDeletion = (): void => {
    confirm.require({
        message: 'Are you sure you want to delete this book?',
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true,
        },
        acceptProps: {
            label: 'Delete',
            severity: 'danger',
        },
        accept: () => {
            deleteRecord()
        },
    })
}
const deleteRecord = (): void => {
    deleting.value = true

    const q = `/api/books/${route.params.id}`

    api(q, { method: 'delete' })
        .then(() => {
            deleting.value = false
            router.push({ name: 'admin.books.index' })
            toast.add({ severity: 'success', summary: 'Success', detail: 'Book deleted!', life: 3000 })
        })
        .catch(() => {
            deleting.value = false
            toast.add({ severity: 'error', summary: 'Error', detail: 'Something went wrong!', life: 3000 })
        })
}

const onPublishersSearch = useDebounceFn((event: SelectFilterEvent) => {
    publishersLoading.value = true

    const q = `/api/publishers?s=${event.value}`

    api(q)
        .then((res: ResourceCollectionResponse<Publisher>) => {
            publishers.value = [
                ...publishers.value.filter(v => record.value.publisher_id === v.id),
                ...res.data
                    .filter((v: BaseModel) => record.value.publisher_id !== v.id),
            ]
            publishersLoading.value = false
        })
        .catch(() => {
            publishersLoading.value = false
        })

}, 500)
const onAuthorsSearch = useDebounceFn((event: AutoCompleteCompleteEvent) => {
    authorsLoading.value = true

    const q = `/api/authors?s=${event.query}`

    api(q)
        .then((res: ResourceCollectionResponse<Author>) => {
            authors.value = res.data
            authorsLoading.value = false
        })
        .catch(() => {
            authorsLoading.value = false
        })

}, 500)
const onGenresSearch = useDebounceFn((event: AutoCompleteCompleteEvent) => {
    genresLoading.value = true

    const q = `/api/genres?s=${event.query}`

    api(q)
        .then((res: ResourceCollectionResponse<Genre>) => {
            genres.value = res.data
            genresLoading.value = false
        })
        .catch(() => {
            genresLoading.value = false
        })

}, 500)
const onSubjectsSearch = useDebounceFn((event: AutoCompleteCompleteEvent, type: string) => {
    subjectsLoading.value[type] = true

    const q = `/api/subjects?s=${event.query}&type=${type}`

    api(q)
        .then((res: ResourceCollectionResponse<Subject>) => {
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
                        <InputText id="name" v-model="record.name" required="true" :invalid="!!errors?.name?.length" />
                        <Message v-if="errors?.name?.length" severity="error" variant="simple">{{ errors?.name[0] }}</Message>
                    </div>
                    <div>
                        <label for="description" class="block font-bold mb-3">Description</label>
                        <Textarea id="description" v-model="record.description" :invalid="!!errors?.description?.length" />
                        <Message v-if="errors?.description?.length" severity="error" variant="simple">{{ errors?.description[0] }}</Message>
                    </div>
                </div>

                <div class="card flex flex-col md:flex-row gap-4">
                    <div class="md:w-1/4">
                        <label for="olid" class="block font-bold mb-3">OLID</label>
                        <InputText id="olid" v-model="record.olid" required="true" :invalid="!!errors?.olid?.length" />
                        <Message v-if="errors?.olid?.length" severity="error" variant="simple">{{ errors?.olid[0] }}</Message>
                    </div>
                    <div class="md:w-1/4">
                        <label for="isbn" class="block font-bold mb-3">ISBN</label>
                        <InputText id="isbn" v-model="record.isbn" required="true" :invalid="!!errors?.isbn?.length" />
                        <Message v-if="errors?.isbn?.length" severity="error" variant="simple">{{ errors?.isbn[0] }}</Message>
                    </div>
                    <div class="md:w-1/4">
                        <label for="publish_year" class="block font-bold mb-3">Publish year</label>
                        <InputNumber id="publish_year" v-model="record.publish_year" :use-grouping="false" required="true" :invalid="!!errors?.publish_year?.length" />
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
                            :auto-filter-focus="true"
                            :reset-filter-on-hide="true"
                            :loading="publishersLoading"
                            placeholder="Search..."
                            @filter="onPublishersSearch"
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
                                    :loading="authorsLoading"
                                    placeholder="Search..."
                                    multiple
                                    @complete="onAuthorsSearch"
                                />
                                <Message v-if="errors?.author_ids?.length" severity="error" variant="simple">{{ errors?.author_ids[0] }}</Message>
                            </TabPanel>
                            <TabPanel value="genres">
                                <AutoComplete
                                    v-model="record.genres"
                                    :suggestions="genres"
                                    data-key="id"
                                    option-label="name"
                                    :loading="genresLoading"
                                    placeholder="Search..."
                                    multiple
                                    @complete="onGenresSearch"
                                />
                                <Message v-if="errors?.genre_ids?.length" severity="error" variant="simple">{{ errors?.genre_ids[0] }}</Message>
                            </TabPanel>
                            <TabPanel value="subjects">
                                <Fieldset
                                    v-for="k in Object.keys(record.subjects)"
                                    :key="k"
                                    :pt="{
                                        legend: {
                                            class: 'flex items-center gap-2'
                                        }
                                    }"
                                >
                                    <template #legend>
                                        <i class="pi pi-users" />
                                        <span class="font-bold capitalize">{{ k }}s</span>
                                    </template>
                                    <AutoComplete
                                        v-model="record.subjects[k]"
                                        :suggestions="subjects[k]"
                                        data-key="id"
                                        option-label="name"
                                        :loading="subjectsLoading[k]"
                                        placeholder="Search..."
                                        multiple
                                        @complete="onSubjectsSearch($event, k)"
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
