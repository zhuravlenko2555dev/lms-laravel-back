<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteUnusedCoversCommand extends Command
{
    protected $signature = 'delete-unused-covers';

    protected $description = '';

    public function handle(): void
    {
        $count = Book::query()
            ->whereNotNull('image_small')
            ->count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        Book::query()
            ->whereNotNull('image_small')
            ->chunkById(100, function ($books) use ($progressBar) {
                /** @var Book $book */
                foreach ($books as $book) {
                    $smallName = last(explode('/', $book->image_small));
                    if (Storage::exists('books_covers/s_selected/' . $smallName)) {
                        Storage::move('books_covers/s_selected/' . $smallName, 'covers/s/' . $smallName);
                    }

                    $mediumName = last(explode('/', $book->image_medium));
                    if (Storage::exists('books_covers/m_selected/' . $mediumName)) {
                        Storage::move('books_covers/m_selected/' . $mediumName, 'covers/m/' . $mediumName);
                    }

                    $largeName = last(explode('/', $book->image_large));
                    if (Storage::exists('books_covers/l_selected/' . $largeName)) {
                        Storage::move('books_covers/l_selected/' . $largeName, 'covers/l/' . $largeName);
                    }
                }

                $progressBar->advance($books->count());
            });

        $progressBar->finish();
    }
}
