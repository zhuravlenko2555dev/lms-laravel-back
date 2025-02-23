<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class FillBookTimestampsCommand extends Command
{
    protected $signature = 'fill-book-timestamps';

    protected $description = 'Fill book timestamps';

    private ProgressBar $progressBar;

    public function handle(): void
    {
        $librariansCount = 5;
        $bookCount = Book::query()->count();
        $bookCountPerLibrarian = round($bookCount / $librariansCount);
        $timestampsPerLibrarian = [];

        for ($i = 0; $i < $librariansCount; $i++) {
            $timestampsPerLibrarian[$i] = [];

            $carbon = now();
            $carbon->subDay()->hour(18)->startOfHour();

            for ($y = 0; $y < $bookCountPerLibrarian; $y++) {
                $subSeconds = 8 * 60;
                $subSeconds += fake()->numberBetween(0, 120);

                $carbon->subSeconds($subSeconds);
                if ($carbon->hour === 13) {
                    $carbon->hour(13)->startOfHour();
                }
                if ($carbon->hour < 9) {
                    $carbon->subDay()->hour(18)->startOfHour();
                }

                $timestampsPerLibrarian[$i][] = $carbon->getTimestamp();
            }
        }

        $timestamps = array_merge(...$timestampsPerLibrarian);
        sort($timestamps);

        $this->progressBar = $this->output->createProgressBar($bookCount);
        $this->progressBar->start();

        $cnt = 0;
        Book::query()
            ->chunkById(100, function ($books) use ($timestamps, &$cnt) {
                foreach ($books as $book) {
                    $timestamp = now()->setTimestamp($timestamps[$cnt]);

                    $book->created_at = $timestamp;
                    $book->updated_at = $timestamp;
                    $book->save();

                    $cnt++;
                }

                $this->progressBar->advance($books->count());
            });
    }
}
