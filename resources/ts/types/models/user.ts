import { BaseModel, Timestamps } from '@/types'

interface UserCore {
    first_name: string
    last_name: string
    middle_name: string | null
    email: string
    email_verified_at: string | null
    roles: string[]
    abilities: string[]
}

export interface User extends BaseModel, Timestamps, UserCore {}
