<?php

namespace App\Http\Resources;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Book */
class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'olid' => $this->olid,
            'isbn' => $this->isbn,
            'name' => $this->name,
            'publish_year' => $this->publish_year,
            'description' => $this->description,

            'authors' => AuthorResource::collection($this->authors),
            'genres' => GenreResource::collection($this->genres),
            'publisher' => PublisherResource::make($this->publisher),
            'subjects' => SubjectResource::collection($this->subjects),

            'covers' => MediaResource::collection($this->covers),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
