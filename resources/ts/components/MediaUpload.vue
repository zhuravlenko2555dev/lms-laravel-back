<script setup lang="ts">
import { ref } from "vue";
import api from "@plugins/api";
import { usePrimeVue } from "primevue/config";

const emit = defineEmits(['uploaded', 'close'])

const files = ref([])
const uploadState = ref({})
const totalSize = ref(0)

const { config } = usePrimeVue()

const onFilesChange = (event = null) => {
    files.value = event?.files ?? []
    totalSize.value = 0
    files.value.forEach((file) => {
        uploadState.value[`${file.name}${file.type}${file.size}`] = 'pending'
        totalSize.value += parseInt(formatSize(file.size))
    });
}
const onUploader = () => {
    let q = '/api/media'

    files.value.forEach((file) => {
        let formData = new FormData()
        formData.append('media', file)

        api(q, { method: 'post', body: formData })
            .then(res => {
                uploadState.value[`${file.name}${file.type}${file.size}`] = 'uploaded'
                emit('uploaded', res.data)
            })
            .catch(() => {
                uploadState.value[`${file.name}${file.type}${file.size}`] = 'failed'
            })
    })
}

const formatSize = (bytes) => {
    const k = 1024
    const dm = 3
    const sizes = config.locale.fileSizeTypes

    if (bytes === 0) {
        return `0 ${sizes[0]}`
    }

    const i = Math.floor(Math.log(bytes) / Math.log(k))
    const formattedSize = parseFloat((bytes / Math.pow(k, i)).toFixed(dm))

    return `${formattedSize} ${sizes[i]}`
}
const getStateBadgeSeverity = (state) => {
    switch (state) {
        case 'pending':
            return 'warn'
        case 'uploaded':
            return 'success'
        case 'failed':
            return 'danger'
        default:
            return null
    }
}
</script>

<template>
    <FileUpload
        custom-upload
        multiple
        accept="image/*"
        @select="onFilesChange"
        @remove="onFilesChange"
        @clear="onFilesChange"
        @uploader="onUploader"
    >
        <template #header="{ chooseCallback, uploadCallback, clearCallback, files }">
            <div class="flex flex-wrap justify-between items-center flex-1 gap-4">
                <div class="flex gap-2">
                    <Button icon="pi pi-images" rounded outlined severity="secondary" @click="chooseCallback()" />
                    <Button
                        icon="pi pi-cloud-upload"
                        rounded
                        outlined
                        severity="success"
                        :disabled="!files || files.length === 0"
                        @click="uploadCallback"
                    />
                    <Button
                        icon="pi pi-times"
                        rounded
                        outlined
                        severity="danger"
                        :disabled="!files || files.length === 0"
                        @click="clearCallback()"
                    />
                </div>

                <Button
                    icon="pi pi-times"
                    rounded
                    outlined
                    severity="secondary"
                    @click="emit('close')"
                />
            </div>
        </template>
        <template #content="{ removeFileCallback }">
            <TransitionGroup
                v-if="files.length > 0"
                name="file-container"
                tag="div"
                class="grid grid-cols-[repeat(auto-fill,_minmax(8rem,_1fr))] gap-4 mt-4"
            >
                <div
                    class="flex flex-col justify-between items-center gap-4 border rounded-3xl border-surface pb-2"
                    v-for="(file, index) of files"
                    :key="file.name + file.type + file.size"
                >
                    <img
                        class="max-h-[10rem] w-full object-contain rounded-3xl"
                        :alt="file.name"
                        :src="file.objectURL"
                    />
                    <div class="flex flex-col items-center gap-4 max-w-full">
                        <span class="font-semibold text-ellipsis whitespace-nowrap max-w-full overflow-hidden">
                            {{ file.name }}
                        </span>
                        <span>{{ formatSize(file.size) }}</span>
                        <Badge
                            :value="uploadState[file.name + file.type + file.size]"
                            :severity="getStateBadgeSeverity(uploadState[file.name + file.type + file.size])"
                        />
                        <Button
                            v-if="uploadState[file.name + file.type + file.size] !== 'uploaded'"
                            icon="pi pi-times"
                            outlined
                            rounded
                            severity="danger"
                            @click="removeFileCallback(index)"
                        />
                    </div>
                </div>
            </TransitionGroup>
            <div v-else class="flex items-center justify-center flex-col">
                <i class="pi pi-cloud-upload !border-2 !rounded-full !p-8 !text-4xl !text-muted-color" />
                <p class="mt-6 mb-0">Drag and drop files here to upload</p>
            </div>
        </template>
    </FileUpload>
</template>

<style scoped lang="scss">
.file-container-move,
.file-container-enter-active,
.file-container-leave-active {
    transition: all 0.4s cubic-bezier(0.55, 0, 0.1, 1);
}

.file-container-enter-from,
.file-container-leave-to {
    opacity: 0;
    transform: scaleY(0.01) translate(30px, 0);
}
</style>
