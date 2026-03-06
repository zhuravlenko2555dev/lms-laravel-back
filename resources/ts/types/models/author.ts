import { BaseModel } from '@/types'

interface AuthorCore {
    name: string
}

export interface Author extends BaseModel, AuthorCore {}

export type AuthorCreateDTO = AuthorCore
export type AuthorUpdateDTO = Partial<AuthorCore>
