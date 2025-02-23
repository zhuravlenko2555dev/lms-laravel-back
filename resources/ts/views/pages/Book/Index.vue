<script setup lang="ts">
import { onMounted, ref } from 'vue';
import api from "@plugins/api";

const dt = ref();
const loading = ref(true)
const records = ref();
const dtPageReport = ref({
    first: 0,
    last: 0,
    totalRecords: 0,
})
const page = ref(1)
const perPage = ref(10)
const sort = ref({field: null, order: null})

onMounted(() => {
    loadRecords()
});

const loadRecords = () => {
    loading.value = true

    let q = '/api/books?'
    let params: any = {}
    params.page = page.value
    params['per-page'] = perPage.value
    if (sort.value.field) {
        params.sort = sort.value.field
        params.by = sort.value.order === 1 ? 'asc' : 'desc'
    }

    for (const [k, v] of Object.entries(params)) {
        q += `&${k}=${v}`
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
</script>

<template>
    <div>
        <div class="card">
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
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Manage Books</h4>
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
                <Column field="publish_date" header="Publish date" :sortable="true" />
                <Column header="Authors">
                    <template #body="slotProps">
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="author in slotProps.data.authors" :value="author.name" />
                        </div>
                    </template>
                </Column>
                <Column header="Genres" style="max-width: 16rem">
                    <template #body="slotProps">
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="genre in slotProps.data.genres" :value="genre.name" />
                        </div>
                    </template>
                </Column>
                <Column field="publisher.name" header="Publisher" :sortable="true">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.publisher.name" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
