<script setup lang="ts">
import { onMounted, ref } from "vue";
import api from "@plugins/api";
import { useDebounceFn } from "@vueuse/core";

const dt = ref();
const loading = ref(true)
const records = ref();
const dtPageReport = ref({
    first: 0,
    last: 0,
    totalRecords: 0,
})
const s = ref('')
const page = ref(1)
const perPage = ref(10)
const sort = ref({ field: null, order: null })

onMounted(() => {
    loadRecords()
    preloadFilters()
});

const loadRecords = () => {
    loading.value = true

    let q = '/api/books?'

    let params: any = {}
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
            for (let i = 0; i < filters.value[k].value?.length ?? 0; i++) {
                q += `&filters[${k}][]=${filters.value[k].value[i]}`
            }
        }
    }

    api(q)
        .then(res => {
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

const onSearch = useDebounceFn((event) => {
    s.value = event.target.value
    page.value = 1

    loadRecords()
}, 500)
const onPage = (event) => {
    page.value = event.page + 1

    loadRecords()
}
const onSort = (event) => {
    sort.value = {
        field: event.sortField,
        order: event.sortOrder,
    }
    page.value = 1

    loadRecords()
}

const filters = ref({
    publish_years: { value: [0, 0], range: [0, 0], loading: false, url: '/api/books-publish-years-range' },
    authors: { value: null, options: [], loading: false, url: '/api/authors?s=' },
    genres: { value: null, options: [], loading: false, url: '/api/genres?s=' },
    publishers: { value: null, options: [], loading: false, url: '/api/publishers?s=' }
})
const filterChips = ref({})
const filterChipsPopover = ref()

const preloadFilters = () => {
    Promise.all(Object.keys(filters.value).map((k) => api(filters.value[k].url)))
        .then(function (res) {
            Object.keys(filters.value).forEach((k, i) => {
                if (k === 'publish_years') {
                    const min = res[i]['min']
                    const max = res[i]['max']

                    filters.value[k].range = [min, max]
                    filters.value[k].value = [min, max]
                } else {
                    filters.value[k].options = res[i].data
                }
            })
        })
}
const onFilter = (event) => {
    for (const k of Object.keys(event.filters)) {
        if (k === 'publish_years') {
            if (!event.filters[k].value) {
                filters.value[k].value = [filters.value[k].range[0], filters.value[k].range[1]]
            }
        } else {
            filters.value[k].value = event.filters[k].value
            sortFilterOptions(k, filters.value[k].options)
        }
    }
    page.value = 1

    loadRecords()
    generateFilterChips()
}
const onFilterSearch = useDebounceFn((filter, s) => {
    filters.value[filter].loading = true

    let q = filters.value[filter].url + s

    api(q)
        .then(res => {
            let options = [
                ...filters.value[filter].options
                    .filter((v) => filters.value[filter].value?.includes(v.id)),
                ...res.data
                    .filter((v) => !filters.value[filter].value?.includes(v.id))
            ]
            sortFilterOptions(filter, options)

            filters.value[filter].options = options
            filters.value[filter].loading = false
        })
        .catch(() => {
            filters.value[filter].loading = false
        })
}, 500)
const onClearFilters = () => {
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
    loadRecords()
    generateFilterChips()
}
const onRemoveFilterChips = (filter, value) => {
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
    loadRecords()
    generateFilterChips()
}
const sortFilterOptions = (filter, options) => {
    options.sort((v1, v2) => {
        const s1 = filters.value[filter].value?.includes(v1.id)
        const s2 = filters.value[filter].value?.includes(v2.id)

        if (s1 && !s2) return -1
        if (s2 && !s1) return 1
        return 0
    })
}
const generateFilterChips = () => {
    for (const k of Object.keys(filters.value)) {
        delete filterChips.value[k]

        if (k === 'publish_years') {
            let rangeChips = []
            if (filters.value[k].value[0] !== filters.value[k].range[0]) {
                rangeChips.push({ id: 'min', name: `Min: ${filters.value[k].value[0]}` })
            }
            if (filters.value[k].value[1] !== filters.value[k].range[1]) {
                rangeChips.push({ id: 'max', name: `Max: ${filters.value[k].value[1]}` })
            }
            if (rangeChips.length) filterChips.value[k] = rangeChips
        } else {
            if (filters.value[k].value?.length) {
                filterChips.value[k] = filters.value[k].options.filter((v) => filters.value[k].value.includes(v.id))
            }
        }
    }
}

const searchAuthors = (event) => onFilterSearch('authors', event.value)
const searchGenres = (event) => onFilterSearch('genres', event.value)
const searchPublishers = (event) => onFilterSearch('publishers', event.value)
</script>

<template>
    <div>
        <div class="card">
            <div class="font-semibold text-xl mb-4">Manage Books</div>
            <DataTable
                ref="dt"
                :value="records"
                dataKey="id"
                lazy
                :loading="loading"
                removable-sort
                paginator
                :rows="perPage"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[10, 25, 50, 100]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} books"
                :first="dtPageReport.first"
                :last="dtPageReport.last"
                :totalRecords="dtPageReport.totalRecords"
                @page="onPage"
                @sort="onSort"
                :filters="filters"
                @filter="onFilter"
                filter-display="menu"
            >
                <template #header>
                    <div class="flex justify-between">
                        <div class="flex gap-2">
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
                            <InputText type="search" v-model="s" @input="onSearch" placeholder="Search..." />
                        </IconField>
                    </div>
                </template>

                <Column selectionMode="multiple" :exportable="false" />
                <Column header="Image">
                    <template #body="slotProps">
                        <img
                            :src="slotProps.data.covers[0]?.url"
                            :alt="slotProps.data.covers[0]?.alt"
                            class="rounded"
                            style="width: 48px; height: 72px; object-fit: contain"
                        />
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
                    <template #filter="{ filterModel }">
                        <div class="flex gap-2 justify-between">
                            <InputNumber
                                v-model="filterModel.value[0]"
                                :min="filterModel.range[0]"
                                :max="filterModel.range[1]"
                                :use-grouping="false"
                                :show-buttons="true"
                                fluid
                            />
                            <span class="font-semibold text-xl content-center">-</span>
                            <InputNumber
                                v-model="filterModel.value[1]"
                                :min="filterModel.range[0]"
                                :max="filterModel.range[1]"
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
                            <Tag v-for="author in slotProps.data.authors" :value="author.name" />
                        </div>
                    </template>
                    <template #filter="{ filterModel }">
                        <MultiSelect
                            v-model="filterModel.value"
                            :options="filterModel.options"
                            option-value="id"
                            option-label="name"
                            :filter="true"
                            @filter="searchAuthors"
                            :auto-filter-focus="true"
                            :loading="filterModel.loading"
                            placeholder="Search..."
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
                            <Badge v-for="genre in slotProps.data.genres" :value="genre.name" />
                        </div>
                    </template>
                    <template #filter="{ filterModel }">
                        <MultiSelect
                            v-model="filterModel.value"
                            :options="filterModel.options"
                            option-value="id"
                            option-label="name"
                            :filter="true"
                            @filter="searchGenres"
                            :auto-filter-focus="true"
                            :loading="filterModel.loading"
                            placeholder="Search..."
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
                        <Tag :value="slotProps.data.publisher.name" />
                    </template>
                    <template #filter="{ filterModel }">
                        <MultiSelect
                            v-model="filterModel.value"
                            :options="filterModel.options"
                            option-value="id"
                            option-label="name"
                            :filter="true"
                            @filter="searchPublishers"
                            :auto-filter-focus="true"
                            :loading="filterModel.loading"
                            placeholder="Search..."
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
