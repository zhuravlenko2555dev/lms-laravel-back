<?php

namespace App\Jobs\Media;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Storage;

class ResizeMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ImageManager $manager;

    public function __construct(
        private readonly Media $media,
        private readonly array $sizes
    ) {
        $this->queue = 'media:resize';

        $this->manager = ImageManager::gd();
    }

    public function handle(): void
    {
        $generatedSizes = [];

        $originalImage = $this->manager->read(Storage::path($this->media->path));

        foreach ($this->sizes as $size) {
            [$w, $h] = explode('x', $size);

            if ($w > $this->media->width && $h > $this->media->height) {
                continue;
            }

            $resizedImage = clone $originalImage;
            $resizedImage->scale($w, $h);

            $extension = 'webp';
            $path = "{$this->media->directory}/$w/{$this->media->name}.$extension";
            Storage::put($path, $resizedImage->encode(new WebpEncoder(90, true)));

            $generatedSizes[] = $w;
        }

        $this->media->update([
            'sizes' => $generatedSizes,
        ]);
    }
}
