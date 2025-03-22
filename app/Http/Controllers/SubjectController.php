<?php

namespace App\Http\Controllers;

use App\Enums\SubjectTypeEnum;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Subject::query()
            ->when($request->has('ids'), function (Builder $q) use ($request) {
                $q->whereIn('id', $request->get('ids'));
            })
            ->when($request->has('s'), function (Builder $q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('s').'%');
            })
            ->when($request->has('type'), function (Builder $q) use ($request) {
                $q->where('type', match ($request->get('type')) {
                    'place' => SubjectTypeEnum::PLACE->value,
                    'people' => SubjectTypeEnum::PEOPLE->value,
                    'time' => SubjectTypeEnum::TIME->value,
                    default => SubjectTypeEnum::SUBJECT->value,
                });
            })
            ->orderBy($request->get('sort', 'id'), $request->get('by', 'desc'));

        $records = match (true) {
            $request->has('ids') => $query->get(),
            default => $query->paginate($request->get('per-page', 100)),
        };

        return SubjectResource::collection($records);
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
