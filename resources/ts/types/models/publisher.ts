import { BaseModel } from '@/types'

interface PublisherCore {
    name: string
}

export interface Publisher extends BaseModel, PublisherCore {}

export type PublisherCreateDTO = PublisherCore
export type PublisherUpdateDTO = Partial<PublisherCore>
