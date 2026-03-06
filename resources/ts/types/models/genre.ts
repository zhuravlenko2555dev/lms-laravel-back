import { BaseModel } from '@/types'

interface GenreCore {
    name: string
}

export interface Genre extends BaseModel, GenreCore {}

export type GenreCreateDTO = GenreCore
export type GenreUpdateDTO = Partial<GenreCore>
