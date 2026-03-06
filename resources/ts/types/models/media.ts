import { BaseModel, Timestamps } from '@/types/models/_shared'

export interface MediaCreateDTO {
    media: File
}

export interface MediaUpdateDTO {
    alt: string | null
    title: string | null
}

export interface Media extends BaseModel, Timestamps {
    disk: string
    directory: string
    name: string
    path: string
    width: number | null
    height: number | null
    size: number | null
    type: string
    ext: string
    alt: string | null
    title: string | null
    sizes: string[] | null

    url: string
    pretty_name: string
    size_for_humans: string
}
