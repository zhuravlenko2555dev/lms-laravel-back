<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'olid' => [
                'nullable',
                'string',
                Rule::unique('books')->ignore($this->get('id')),
            ],
            'isbn' => [
                'nullable',
                'string',
                Rule::unique('books')->ignore($this->get('id')),
            ],
            'name' => 'required|string',
            'publish_year' => 'nullable|integer',
            'description' => 'nullable|string',
            'publisher_id' => 'nullable|exists:publishers,id',

            'author_ids' => 'required|array',
            'author_ids.*' => 'integer|exists:authors,id',

            'genre_ids' => 'required|array',
            'genre_ids.*' => 'integer|exists:genres,id',

            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id',

            'cover_ids' => 'nullable|array',
            'cover_ids.*' => 'integer|exists:media,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
