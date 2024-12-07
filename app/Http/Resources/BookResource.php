<?php

namespace App\Http\Resources;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Storage;

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
            'publish_date' => $this->publish_date,
            'description' => $this->description,

            // temp solution
            'image_small' => Storage::url($this->image_small),
            'image_medium' => Storage::url($this->image_medium),
            'image_large' => Storage::url($this->image_large),

            'authors' => AuthorResource::collection($this->authors),
            'genres' => GenreResource::collection($this->genres),
            'publisher' => PublisherResource::make($this->publisher),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
