<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuthorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Author::query()
            ->when($request->has('ids'), function (Builder $q) use ($request) {
                $q->whereIn('id', $request->get('ids'));
            })
            ->when($request->has('s'), function (Builder $q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('s').'%');
            })
            ->orderBy($request->get('sort', 'id'), $request->get('by', 'desc'));

        $records = match (true) {
            $request->has('ids') => $query->get(),
            default => $query->paginate($request->get('per-page', 10)),
        };

        return AuthorResource::collection($records);
    }

    public function store(Request $request)
    {
    }

    public function show($id)
    {
    }

    public function update(Request $request, $id)
    {
    }

    public function destroy($id)
    {
    }
}
