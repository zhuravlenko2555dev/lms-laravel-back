<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useDebounceFn } from '@vueuse/core'
import api from '@/plugins/api'
import { DataTableMethods } from 'primevue'
import { DataTableFilterEvent, DataTableFilterMeta, DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable'
import { MultiSelectFilterEvent } from 'primevue/multiselect'
import Popover from 'primevue/popover'
import {
    BaseModel, Book, Author, Genre, Publisher,
    DatatableColumnFilter, DatatableColumnSort, DatatableFilterChip,
    QueryParams, PageReport, ResourceCollectionResponse,
} from '@/types'

const dt = ref<DataTableMethods>()
const loading = ref<boolean>(true)
const records = ref<Book[]>()
const dtPageReport = ref<PageReport>({
    first: 0,
    last: 0,
    totalRecords: 0,
})
const s = ref<string>('')
const page = ref<number>(1)
const perPage = ref<number>(10)
const sort = ref<DatatableColumnSort>({ field: null, order: null })

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

onMounted(() => {
    processRoute()
    loadRecords()
    preloadFilters()
})

watch(
    () => route.query,
    () => {
        processRoute()
        loadRecords()
        generateFilterChips()
    },
)

const loadRecords = (): void => {
    loading.value = true

    let q = '/api/books?'

    const params: QueryParams = {}
    if (s.value) params['s'] = s.value
    if (page.value > 1) params.page = page.value
    if (perPage.value > 10) params['per-page'] = perPage.value
    if (sort.value.field) {
        params.sort = sort.value.field
        params.by = sort.value.order === 1 ? 'asc' : 'desc'
    }

    for (const [k, v] of Object.entries(params)) {
        q += `&${k}=${v}`
    }

    for (const k of Object.keys(filters.value)) {
        if (k === 'publish_years') {
            if (filters.value[k].value[0] !== filters.value[k].range[0]) {
                q += `&filters[min-year]=${filters.value[k].value[0]}`
            }
            if (filters.value[k].value[1] !== filters.value[k].range[1]) {
                q += `&filters[max-year]=${filters.value[k].value[1]}`
            }
        } else {
            for (let i = 0; i < filters.value[k].value?.length || 0; i++) {
                q += `&filters[${k}][]=${filters.value[k].value[i]}`
            }
        }
    }

    api(q)
        .then((res: ResourceCollectionResponse<Book>) => {
            records.value = res.data
            dtPageReport.value = {
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
const confirmDeletion = (id: number): void => {
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
            deleteRecord(id)
        },
    })
}
const deleteRecord = (id: number): void => {
    const q = `/api/books/${id}`

    api(q, { method: 'delete' })
        .then(() => {
            loadRecords()
            toast.add({ severity: 'success', summary: 'Success', detail: 'Book deleted!', life: 3000 })
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Something went wrong!', life: 3000 })
        })
}

const onSearch = useDebounceFn((event: InputEvent) => {
    const target = event.target as HTMLInputElement
    s.value = target.value
    page.value = 1

    pushToHistory()
}, 500)
const onPage = (event: DataTablePageEvent): void => {
    page.value = event.page + 1

    pushToHistory()
}
const onSort = (event: DataTableSortEvent): void => {
    sort.value = {
        field: event.sortField as string,
        order: event.sortOrder,
    }
    page.value = 1

    pushToHistory()
}

type SearchableFilterKey = 'authors' | 'genres' | 'publishers'
type SearchableFilterModelMap = {
    authors: Author
    genres: Genre
    publishers: Publisher
}
interface Filters {
    publish_years: DatatableColumnFilter<{ min: number, max: number }>
    authors: DatatableColumnFilter<SearchableFilterModelMap['authors']>
    genres: DatatableColumnFilter<SearchableFilterModelMap['genres']>
    publishers: DatatableColumnFilter<SearchableFilterModelMap['publishers']>
}

const filters = ref<Filters>({
    publish_years: { value: [0, 0], range: [0, 0], loading: false, url: '/api/books-publish-years-range' },
    authors: { value: null, options: [], loading: false, url: '/api/authors?' },
    genres: { value: null, options: [], loading: false, url: '/api/genres?' },
    publishers: { value: null, options: [], loading: false, url: '/api/publishers?' },
})
const filterChips = ref<Record<string, DatatableFilterChip[]>>({})
const filterChipsPopover = ref<InstanceType<typeof Popover>>()

const preloadFilters = (): void => {
    Promise.all(Object.keys(filters.value).map((k) => {
        let q = filters.value[k].url

        if (k !== 'publish_years') {
            for (let i = 0; i < filters.value[k].value?.length || 0; i++) {
                q += `&ids[]=${filters.value[k].value[i]}`
            }
        }

        return api(q)
    }))
        .then(function (res) {
            Object.keys(filters.value).forEach((k, i) => {
                if (k === 'publish_years') {
                    const min = res[i]['min']
                    const max = res[i]['max']

                    filters.value[k].range = [min, max]
                    filters.value[k].value = [
                        filters.value[k].value[0] === 0 ? min : filters.value[k].value[0],
                        filters.value[k].value[1] === 0 ? max : filters.value[k].value[1],
                    ]
                } else {
                    filters.value[k].options = res[i].data
                    sortFilterOptions(k, filters.value[k].options)
                }
            })

            generateFilterChips()
        })
}
const onFilter = (event: DataTableFilterEvent): void => {
    for (const k of Object.keys(event.filters)) {
        const filter = event.filters[k] as unknown as DataTableFilterMeta

        if (k === 'publish_years') {
            if (!filter.value) {
                filters.value[k].value = [filters.value[k].range[0], filters.value[k].range[1]]
            }
        } else {
            filters.value[k].value = filter.value
            sortFilterOptions(k, filters.value[k].options)
        }
    }
    page.value = 1

    pushToHistory()
}
const onFilterSearch = useDebounceFn(<K extends SearchableFilterKey>(filter: K, s: string) => {
    filters.value[filter].loading = true

    const q = filters.value[filter].url + `s=${s}`

    api(q)
        .then((res: ResourceCollectionResponse<SearchableFilterModelMap[K]>) => {
            filters.value[filter].options = [
                ...filters.value[filter].options
                    .filter((v: BaseModel) => filters.value[filter].value?.includes(v.id)),
                ...res.data
                    .filter((v: BaseModel) => !filters.value[filter].value?.includes(v.id)),
            ]
            filters.value[filter].loading = false
        })
        .catch(() => {
            filters.value[filter].loading = false
        })
}, 500)
const onClearFilters = (): void => {
    for (const k of Object.keys(filters.value)) {
        if (k === 'publish_years') {
            const range = filters.value[k].range
            filters.value[k].value = [range[0], range[1]]
        } else {
            filters.value[k].value = null
        }
    }
    page.value = 1

    filterChipsPopover.value.hide()
    pushToHistory()
}
const onRemoveFilterChips = (filter: string, value: string | number): void => {
    if (filter === 'publish_years') {
        if (value === 'min') {
            filters.value[filter].value[0] = filters.value[filter].range[0]
        } else {
            filters.value[filter].value[1] = filters.value[filter].range[1]
        }
    } else {
        filters.value[filter].value.splice(filters.value[filter].value.indexOf(value), 1)
        sortFilterOptions(filter, filters.value[filter].options)
    }
    page.value = 1

    filterChipsPopover.value.hide()
    pushToHistory()
}
const sortFilterOptions = (filter: string, options: BaseModel[]): void => {
    options.sort((v1, v2) => {
        const s1 = filters.value[filter].value?.includes(v1.id)
        const s2 = filters.value[filter].value?.includes(v2.id)

        if (s1 && !s2) return -1
        if (s2 && !s1) return 1
        return 0
    })
}
const generateFilterChips = (): void => {
    for (const k of Object.keys(filters.value)) {
        delete filterChips.value[k]

        if (k === 'publish_years') {
            const rangeChips = []
            if (filters.value[k].value[0] !== filters.value[k].range[0]) {
                rangeChips.push({ id: 'min', name: `Min: ${filters.value[k].value[0]}` })
            }
            if (filters.value[k].value[1] !== filters.value[k].range[1]) {
                rangeChips.push({ id: 'max', name: `Max: ${filters.value[k].value[1]}` })
            }
            if (rangeChips.length) filterChips.value[k] = rangeChips
        } else {
            if (filters.value[k].value?.length) {
                filterChips.value[k] = filters.value[k].options.filter((v: BaseModel) => filters.value[k].value.includes(v.id))
            }
        }
    }
}

const searchAuthors = (event: MultiSelectFilterEvent): void => void onFilterSearch('authors', event.value)
const searchGenres = (event: MultiSelectFilterEvent): void => void onFilterSearch('genres', event.value)
const searchPublishers = (event: MultiSelectFilterEvent): void => void onFilterSearch('publishers', event.value)

const processRoute = (): void => {
    const params = route.query

    s.value = (params.s as string) ?? ''
    page.value = params.page ? +params.page : 1
    perPage.value = params['per-page'] ? +params['per-page'] : 10
    sort.value.field = (params.sort as string) ?? null
    sort.value.order = params.by === 'asc' ? 1 : -1

    if (Object.keys(filters.value).length) {
        for (const k of Object.keys(filters.value)) {
            if (k === 'publish_years') {
                filters.value.publish_years.value[0] = params['filters[min-year]'] ? +params['filters[min-year]'] : filters.value.publish_years.range[0]
                filters.value.publish_years.value[1] = params['filters[max-year]'] ? +params['filters[max-year]'] : filters.value.publish_years.range[1]
            } else {
                const filterParam = params[`filters[${k}]`]
                if (filterParam) {
                    if (Array.isArray(filterParam)) {
                        filters.value[k].value = filterParam.map(v => +v)
                    } else {
                        filters.value[k].value = [params[`filters[${k}]`]]
                    }
                } else {
                    filters.value[k].value = null
                }
            }
        }
    }
}
const pushToHistory = (): void => {
    const params: QueryParams = {}

    if (s.value) params['s'] = s.value
    if (page.value > 1) params.page = page.value
    if (perPage.value > 10) params['per-page'] = perPage.value
    if (sort.value.field) {
        params.sort = sort.value.field
        params.by = sort.value.order === 1 ? 'asc' : 'desc'
    }

    if (Object.keys(filters.value).length) {
        for (const k of Object.keys(filters.value)) {
            if (k === 'publish_years') {
                if (filters.value[k].value[0] !== filters.value[k].range[0]) {
                    params['filters[min-year]'] = filters.value[k].value[0]
                }
                if (filters.value[k].value[1] !== filters.value[k].range[1]) {
                    params['filters[max-year]'] = filters.value[k].value[1]
                }
            } else {
                if (filters.value[k].value?.length) params[`filters[${k}]`] = filters.value[k].value
            }
        }
    }

    router.push({ name: 'books', query: params })
}
</script>

<template>
    <div>
        <div class="card">
            <div class="font-semibold text-xl mb-4">Manage Books</div>
            <DataTable
                ref="dt"
                v-model:rows="perPage"
                :value="records"
                data-key="id"
                lazy
                :loading="loading"
                removable-sort
                paginator
                paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rows-per-page-options="[10, 25, 50, 100]"
                current-page-report-template="Showing {first} to {last} of {totalRecords} books"
                :first="dtPageReport.first"
                :last="dtPageReport.last"
                :total-records="dtPageReport.totalRecords"
                :sort-field="sort.field"
                :sort-order="sort.order"
                :filters="filters as any"
                filter-display="menu"
                @page="onPage"
                @sort="onSort"
                @filter="onFilter"
            >
                <template #header>
                    <div class="flex justify-between">
                        <div class="flex gap-2">
                            <RouterLink class="mr-2" :to="{ name: 'books.create' }">
                                <Button icon="pi pi-plus" outlined rounded />
                            </RouterLink>

                            <Button
                                v-if="Object.keys(filterChips).length"
                                type="button"
                                icon="pi pi-filter"
                                outlined
                                rounded
                                @click="(event) => filterChipsPopover.toggle(event)"
                            />
                            <Popover ref="filterChipsPopover">
                                <div class="flex flex-col gap-4 w-[25rem]">
                                    <div v-for="(v, k) in filterChips" :key="k">
                                        <span class="font-medium block mb-2 capitalize">{{ k }}</span>
                                        <div class="flex flex-wrap gap-2">
                                            <Chip
                                                v-for="option in v"
                                                :key="option.id"
                                                :label="option.name"
                                                removable
                                                @remove="onRemoveFilterChips(k, option.id)"
                                            />
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        icon="pi pi-filter-slash"
                                        label="Clear"
                                        outlined
                                        @click="onClearFilters"
                                    />
                                </div>
                            </Popover>
                        </div>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="s" type="search" placeholder="Search..." @input="onSearch" />
                        </IconField>
                    </div>
                </template>

                <Column selection-mode="multiple" :exportable="false" />
                <Column header="Image">
                    <template #body="slotProps">
                        <img
                            v-if="slotProps.data.covers?.length"
                            :src="slotProps.data.covers[0]?.url"
                            :alt="slotProps.data.covers[0]?.alt"
                            class="rounded"
                            style="width: 48px; height: 72px; object-fit: contain"
                        >
                        <svg v-else style="width: 48px; height: 72px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128">
                            <path fill="#D68B52" d="M106.02 121.68H36.18c-1.66 0-1.26-1.35-1.26-3.01V14.05c0-1.66 1.35-3.01 3.01-3.01h69.36c2.72 0 4.93 2.21 4.93 4.93v99.76c0 3.86-1.83 5.95-6.2 5.95" />
                            <path fill="#94C6D6" d="M18.53 115.14c0 1.94 3.07 3.57 5.01 3.57l80.39-.05c2.98 0 4.54-1.58 4.54-3.52l-.25-21.32H18.53z" />
                            <path fill="#F5F5F5" d="M101.47 105.88s-2.13 5.85.03 8.78c2.51 3.4 6.89 2.58 6.89.99V16.44c0-.66-.61-1.14-1.25-1c-1.39.3-3.89.31-7.21-1.89z" />
                            <path fill="#D68B52" d="M94.16 110.85H23.64V6.45h72.25c2.27 0 3.87.61 4.62 1.62c.98 1.31 1.5 3.3 1.5 5.48V103a7.85 7.85 0 0 1-7.85 7.85" />
                            <path fill="#ECB354" d="M92.01 107.78H25.54c-2.76 0-4.99-2.24-4.99-4.99V11.45c0-2.76 2.24-4.99 4.99-4.99h66.47c3.82 0 6.92 2.18 6.92 6.92v87.49c-.01 3.81-3.1 6.91-6.92 6.91" />
                            <path fill="#BA793E" d="M34.43 109.75L34.38 6.46h-11.2s-2.31-.4-3.85 0c-2.79.73-3.56 2.76-3.56 6.07v94.41c0 6.7.41 9.6 2.44 11.72c-.12-1.54.87-6.83 1.68-8.28c.72-1.28 14.54-.63 14.54-.63" />
                            <path fill="none" stroke="#6D4C41" stroke-miterlimit="10" stroke-width="2" d="M23.18 6.45v104.4" />
                            <path fill="none" stroke="#6D4C41" stroke-miterlimit="10" stroke-width="3" d="m34.38 109.34l-11.3.22c-3.77 0-5.06 4.04-4.39 6.71c.84 3.37 4.32 3.92 5.18 3.92h12.65" />
                            <path fill="#FFFDE7" d="M85.85 31.7c0-.86-.66-1.57-1.52-1.64c-3.78-.3-7.37-1.7-10.55-3.82a12.77 12.77 0 0 0-7.1-2.13c-2.63 0-5.07.79-7.1 2.13c-3.18 2.11-6.78 3.51-10.55 3.81c-.86.07-1.52.78-1.52 1.64v9.96c0 .86.66 1.57 1.52 1.64c3.78.3 7.37 1.7 10.55 3.81c2.03 1.35 4.47 2.13 7.1 2.13s5.07-.79 7.1-2.13c3.18-2.11 6.78-3.51 10.55-3.82c.86-.07 1.52-.78 1.52-1.64z" />
                            <path fill="#D68B52" d="M85.85 40.5c-.02.07-.54.72-3.31 1c-1.16.12-2.28.46-3.39.81c-2.29.73-4.47 1.81-6.47 3.14c-1.77 1.18-3.85 1.8-6 1.8s-4.18-.7-6-1.8c-5.2-3.15-8.06-3.74-10.08-3.98c-2.59-.31-3.11-1.01-3.11-1.01s.01.76.01 1.21c0 .86.66 1.57 1.52 1.64c3.78.3 7.37 1.7 10.55 3.81c2.03 1.35 4.47 2.13 7.1 2.13s5.07-.79 7.1-2.13c3.18-2.11 6.78-3.51 10.55-3.82c.86-.07 1.52-.78 1.52-1.64c.01-.33.01-1.16.01-1.16" />
                            <path fill="#FFECB3" d="M47.5 32.82s1.08-.71 3.31-.96c1.15-.13 2.28-.46 3.39-.81c2.29-.73 4.47-1.81 6.47-3.14c1.77-1.18 3.85-1.8 6-1.8s4.22.62 6 1.8c2.05 1.36 5.14 3.36 10.08 3.98c.46.06 2.33-.09 3.1 1.24c0 0-.01-.99-.01-1.43c0-.86-.66-1.57-1.52-1.64c-3.78-.3-7.37-1.7-10.55-3.81a12.77 12.77 0 0 0-7.1-2.13c-2.63 0-5.07.79-7.1 2.13c-3.18 2.11-6.78 3.51-10.55 3.82c-.86.07-1.52.78-1.52 1.64z" />
                        </svg>
                    </template>
                </Column>
                <Column field="id" header="ID" :sortable="true" />
                <Column field="olid" header="OLID" :sortable="true" />
                <Column field="isbn" header="ISBN" :sortable="true" />
                <Column field="name" header="Name" :sortable="true" />
                <Column
                    field="publish_year"
                    header="Publish year"
                    :sortable="true"
                    filter-field="publish_years"
                    :show-filter-match-modes="false"
                    :filter-menu-style="{ width: '16rem' }"
                >
                    <template #filter>
                        <div class="flex gap-2 justify-between">
                            <InputNumber
                                v-model="filters.publish_years.value[0] as number"
                                :min="filters.publish_years.range[0]"
                                :max="filters.publish_years.range[1]"
                                :use-grouping="false"
                                :show-buttons="true"
                                fluid
                            />
                            <span class="font-semibold text-xl content-center">-</span>
                            <InputNumber
                                v-model="filters.publish_years.value[1] as number"
                                :min="filters.publish_years.range[0]"
                                :max="filters.publish_years.range[1]"
                                :use-grouping="false"
                                :show-buttons="true"
                                fluid
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    header="Authors"
                    filter-field="authors"
                    :show-filter-match-modes="false"
                    :filter-menu-style="{ width: '16rem' }"
                >
                    <template #body="slotProps">
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="author in slotProps.data.authors" :key="author.id" :value="author.name" />
                        </div>
                    </template>
                    <template #filter>
                        <MultiSelect
                            v-model="filters.authors.value"
                            :options="filters.authors.options"
                            option-value="id"
                            option-label="name"
                            :filter="true"
                            :auto-filter-focus="true"
                            :loading="filters.authors.loading"
                            placeholder="Search..."
                            @filter="searchAuthors"
                        />
                    </template>
                </Column>
                <Column
                    header="Genres"
                    filter-field="genres"
                    :show-filter-match-modes="false"
                    :filter-menu-style="{ width: '16rem' }"
                    style="max-width: 16rem"
                >
                    <template #body="slotProps">
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="genre in slotProps.data.genres" :key="genre.id" :value="genre.name" />
                        </div>
                    </template>
                    <template #filter>
                        <MultiSelect
                            v-model="filters.genres.value"
                            :options="filters.genres.options"
                            option-value="id"
                            option-label="name"
                            :filter="true"
                            :auto-filter-focus="true"
                            :loading="filters.genres.loading"
                            placeholder="Search..."
                            @filter="searchGenres"
                        />
                    </template>
                </Column>
                <Column
                    field="publisher.name"
                    header="Publisher"
                    :sortable="true"
                    filter-field="publishers"
                    :show-filter-match-modes="false"
                    :filter-menu-style="{ width: '16rem' }"
                >
                    <template #body="slotProps">
                        <Tag v-if="slotProps.data.publisher" :value="slotProps.data.publisher.name" />
                    </template>
                    <template #filter>
                        <MultiSelect
                            v-model="filters.publishers.value"
                            :options="filters.publishers.options"
                            option-value="id"
                            option-label="name"
                            :filter="true"
                            :auto-filter-focus="true"
                            :loading="filters.publishers.loading"
                            placeholder="Search..."
                            @filter="searchPublishers"
                        />
                    </template>
                </Column>
                <Column style="min-width: 12rem">
                    <template #body="slotProps">
                        <RouterLink class="mr-2" :to="{ name: 'books.edit', params: { id: slotProps.data.id} }">
                            <Button icon="pi pi-pencil" outlined rounded />
                        </RouterLink>
                        <Button
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            @click="confirmDeletion(slotProps.data.id)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
