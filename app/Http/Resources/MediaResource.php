<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Media */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'disk' => $this->disk,
            'directory' => $this->directory,
            'name' => $this->name,
            'path' => $this->path,
            'width' => $this->width,
            'height' => $this->height,
            'size' => $this->size,
            'type' => $this->type,
            'ext' => $this->ext,
            'alt' => $this->alt,
            'title' => $this->title,
            'sizes' => $this->sizes,
            'url' => $this->url,
            'pretty_name' => $this->pretty_name,
            'size_for_humans' => $this->size_for_humans,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
