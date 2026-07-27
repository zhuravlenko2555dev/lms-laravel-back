<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Str;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class MediaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Media::query()
            ->when($request->has('except-ids'), function (Builder $q) use ($request) {
                $q->whereNotIn('id', $request->get('except-ids'));
            })
            ->when($request->has('s'), function (Builder $q) use ($request) {
                $q->where('alt', 'like', '%'.$request->get('s').'%');
                $q->orWhere('title', 'like', '%'.$request->get('s').'%');
            })
            ->orderBy($request->get('sort', 'id'), $request->get('by', 'desc'));

        $records = $query->paginate($request->get('per-page', 25));

        return MediaResource::collection($records);
    }

    public function store(Request $request, MediaService $mediaService): JsonResource
    {
        $media = $mediaService->storeMedia(
            $request->file('media'),
            config('covers.directory'),
            Str::uuid(),
            config('covers.sizes')
        );

        return MediaResource::make($media);
    }

    public function show(Media $media): JsonResource
    {
        return MediaResource::make($media);
    }

    public function update(Request $request, Media $media): JsonResource
    {
        $request->validate([
            'alt' => 'nullable|string|max:125',
            'title' => 'nullable|string|max:125',
        ]);

        $media->update($request->only(['alt', 'title']));

        return MediaResource::make($media);
    }

    public function destroy(Media $media, MediaService $mediaService): JsonResponse
    {
        $mediaService->deleteMedia($media);

        return response()->json(status: ResponseAlias::HTTP_NO_CONTENT);
    }
}
