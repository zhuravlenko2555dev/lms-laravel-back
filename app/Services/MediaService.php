<?php

namespace App\Services;

use App\Jobs\Media\ResizeMediaJob;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Storage;

class MediaService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::gd();
    }

    public function storeMedia(
        UploadedFile $uploadedFile,
        string $directory,
        string $filename,
        array $sizes = []
    ): Media {
        $uploadedImage = $this->manager->read($uploadedFile);

        $extension = 'webp';
        $path = "{$directory}/{$filename}.{$extension}";

        $webpImage = $uploadedImage->encode(new WebpEncoder(90, true));
        Storage::put($path, $webpImage);

        $media = Media::create([
            'disk' => 'public',
            'directory' => $directory,
            'name' => $filename,
            'path' => $path,
            'width' => $uploadedImage->width() ?? null,
            'height' => $uploadedImage->height() ?? null,
            'size' => filesize(Storage::path($path)),
            'type' => $webpImage->mimetype(),
            'ext' => $extension,
        ]);

        if (! empty($sizes)) {
            ResizeMediaJob::dispatch($media, $sizes);
        }

        return $media;
    }
}
