import { BaseModel, Timestamps } from '@/types/models/_shared'
import { Author } from '@/types/models/author'
import { Genre } from '@/types/models/genre'
import { Publisher } from '@/types/models/publisher'
import { Subject } from '@/types/models/subject'
import { Media } from '@/types/models/media'

interface BookCore {
    olid: string | null
    isbn: string | null
    name: string
    publish_year: number | null
    description: string | null
}

export interface BookDTO extends BookCore {
    publisher_id: number | null
    author_ids: number[]
    genre_ids: number[]
    subject_ids: number[]
    cover_ids: number[]
}

export interface Book extends BaseModel, BookCore, Timestamps {
    authors: Author[]
    genres: Genre[]
    publisher: Publisher | null
    subjects: Subject[]
    covers: Media[]
}
