<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Book::query()
            ->with([
                'authors',
                'genres',
                'publisher',
            ])
            ->orderBy($request->get('sort', 'id'), $request->get('by', 'desc'));

        $records = $query->paginate($request->get('per-page', 10));

        return BookResource::collection($records);
    }

    public function store(Request $request)
    {
    }

    public function show(Book $book)
    {
    }

    public function update(Request $request, Book $book)
    {
    }

    public function destroy(Book $book)
    {
    }
}
