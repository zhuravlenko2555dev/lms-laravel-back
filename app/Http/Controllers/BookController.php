<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
                'covers',
            ])
            ->when($request->has('s'), function (Builder $q) use ($request) {
                $q->where('olid', 'like', $request->get('s').'%');
                $q->orWhere('isbn', 'like', $request->get('s').'%');
                $q->orWhere('name', 'like', '%'.$request->get('s').'%');
            })
            ->when($request->has('filters'), function (Builder $q) use ($request) {
                $filters = $request->get('filters');

                if ($value = $filters['min-year'] ?? null) {
                    $q->where('publish_year', '>=', $value);
                }

                if ($value = $filters['max-year'] ?? null) {
                    $q->where('publish_year', '<=', $value);
                }

                if ($value = $filters['authors'] ?? null) {
                    $q->whereHas('authors', function (Builder $rq) use ($value) {
                        $rq->whereIn('id', $value);
                    });
                }

                if ($value = $filters['genres'] ?? null) {
                    $q->whereHas('genres', function (Builder $rq) use ($value) {
                        $rq->whereIn('id', $value);
                    });
                }

                if ($value = $filters['publishers'] ?? null) {
                    $q->whereHas('publisher', function (Builder $rq) use ($value) {
                        $rq->whereIn('id', $value);
                    });
                }
            })
            ->orderBy($request->get('sort', 'id'), $request->get('by', 'desc'));

        $records = $query->paginate($request->get('per-page', 10));

        return BookResource::collection($records);
    }

    public function publishYearsRange(): JsonResponse
    {
        $min = Book::query()
            ->whereNotNull('publish_year')
            ->orderBy('publish_year')
            ->select('publish_year')
            ->limit(1)
            ->first()->publish_year;

        $max = Book::query()
            ->whereNotNull('publish_year')
            ->orderBy('publish_year', 'desc')
            ->select('publish_year')
            ->limit(1)
            ->first()->publish_year;

        return response()->json([
            'min' => $min,
            'max' => $max,
        ]);
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
