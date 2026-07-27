<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Storage;
use Str;
use Symfony\Component\Console\Helper\ProgressBar;

class FixBookCoversCommand extends Command
{
    protected $signature = 'fix:book-covers';

    protected $description = 'Moving covers to media table & resizing covers';

    private MediaService $mediaService;

    private string $oldDirectory = 'covers_old';

    private ProgressBar $progressBar;

    public function handle(): void
    {
        $this->mediaService = new MediaService();

        $count = Book::query()
            ->whereNotNull('image_large')
            ->count();

        $this->progressBar = $this->output->createProgressBar($count);
        $this->progressBar->start();

        Book::query()
            ->with('authors')
            ->whereNotNull('image_large')
            ->chunkById(100, function ($books) {
                foreach ($books as $book) {
                    $filename = last(explode('/', $book->image_large));
                    $path = $this->oldDirectory."/l/{$filename}";

                    if (!Storage::exists($path)) {
                        continue;
                    }

                    $file = new UploadedFile(
                        Storage::path($path),
                        $filename
                    );

                    $media = $this->mediaService->storeMedia(
                        $file,
                        config('covers.directory'),
                        Str::uuid(),
                        config('covers.sizes')
                    );
                    $book->covers()->sync($media);

                    $bookName = $book->name;
                    $authorNames = $book->authors->pluck('name')->toArray();

                    if (Str::length($bookName) <= 80) {
                        $seoText = $this->seoText($bookName, $authorNames);
                        $media->update([
                            'alt' => $seoText,
                            'title' => $seoText,
                        ]);
                    }
                }

                $this->progressBar->advance($books->count());
            });

        $this->progressBar->finish();
    }

    private function seoText(string $bookName, array $authorNames): string
    {
        $authors = count($authorNames) === 1 ? 'author ' : 'authors ';
        $authors .= implode(', ', $authorNames);

        return sprintf('Book "%s", %s', $bookName, $authors);
    }
}
