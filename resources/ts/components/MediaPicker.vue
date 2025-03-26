<script setup lang="ts">
import { computed, ref } from "vue";
import MediaGallery from "@/components/MediaGallery.vue";
import MediaModal from "@/components/MediaModal.vue";
import Record from "@/views/pages/Media/Record.vue";

const { modelValue } = defineProps(['modelValue'])
const emit = defineEmits(['update:modelValue'])

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
        key: 'media-remove',
        label: 'Remove',
        icon: 'pi pi-trash',
        style: 'color: var(--p-red-500)',
        command: () => {
            const index = menuOnIndex.value
            menuOnIndex.value = 0
            let newModelValue = modelValue
            newModelValue.splice(index, 1)
            emit('update:modelValue', newModelValue)
        }
    }
])

const selectedMediaIds = ref([])
const allMediaSelected = computed(() => {
    return selectedMediaIds.value.length === modelValue?.length
})
const partialMediaSelected = computed(() => {
    return !!(selectedMediaIds.value.length && selectedMediaIds.value.length < modelValue?.length)
})
const onSelectAllMedia = () => {
    selectedMediaIds.value = allMediaSelected.value ? [] : [...modelValue.map((v) => v.id)]
}

const bulkMenu = ref()
const bulkMenuOptions = ref([
    {
        key: 'bulk-remove',
        label: 'Remove',
        icon: 'pi pi-trash',
        style: 'color: var(--p-red-500)',
        command: () => {
            let newModelValue = modelValue
            newModelValue = newModelValue.filter((v) => !selectedMediaIds.value.includes(v.id))
            selectedMediaIds.value = []
            emit('update:modelValue', newModelValue)
        }
    }
])

const mediaModalVisible = ref(false)
const addMedia = (media) => {
    let newModelValue = modelValue
    newModelValue.push(...media)
    emit('update:modelValue', newModelValue)
}
</script>

<template>
    <Toolbar class="mb-4">
        <template #start>
            <Checkbox
                class="mr-3"
                v-if="modelValue?.length"
                :model-value="allMediaSelected"
                :binary="true"
                :indeterminate="partialMediaSelected"
                @change="onSelectAllMedia"
            />

            <Button
                class="mr-3 text-nowrap"
                v-if="selectedMediaIds.length"
                :label="`Selected (${selectedMediaIds.length})`"
                icon="pi pi-ellipsis-v"
                aria-haspopup="true"
                aria-controls="bulkOverlayMenu"
                severity="secondary"
                @click="bulkMenu.toggle($event)"
            />
            <Menu
                ref="bulkMenu"
                id="bulkOverlayMenu"
                :model="bulkMenuOptions"
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
        </template>
        <template #end>
            <Button
                icon="pi pi-plus"
                severity="secondary"
                @click="mediaModalVisible = true"
            />
        </template>
    </Toolbar>

    <MediaGallery
        :media="modelValue"
        v-model:menu-on-index="menuOnIndex"
        :selectable="true"
        v-model:selected-media-ids="selectedMediaIds"
        :media-menu-options="mediaMenuOptions"
    />

    <Dialog
        v-model:visible="mediaModalVisible"
        maximizable
        :style="{ width: '50vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        :block-scroll="true"
    >
        <template #container="{ closeCallback, maximizeCallback }">
            <MediaModal
                :except-ids="modelValue.map((v) => v.id)"
                @add-media="addMedia"
                @close="closeCallback"
                @maximize="maximizeCallback"
            />
        </template>
    </Dialog>

    <Dialog
        v-if="modelValue?.length"
        v-model:visible="recordModalVisible"
        :style="{ width: '40vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        :block-scroll="true"
        :header="`Media #${modelValue[menuOnIndex].id}`"
    >
        <Record :id="modelValue[menuOnIndex].id" />
    </Dialog>
</template>
