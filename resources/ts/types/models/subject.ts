import { BaseModel } from '@/types'

interface SubjectCore {
    type: number
    name: string
}

export interface Subject extends BaseModel, SubjectCore {}

export type SubjectCreateDTO = SubjectCore
export type SubjectUpdateDTO = Partial<SubjectCore>
