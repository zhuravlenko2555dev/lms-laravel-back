<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class ClearFromBadWordsCommand extends Command
{
    protected $signature = 'clear-from-bad-words';

    protected $description = 'Clear DB from bad words';

    public function handle(): void
    {
        $words = config('bad-word.en');

        $tables = [
            'subjects',
            'subject_places',
            'subject_people',
            'subject_times',

            'authors',
            'genres',
            'publishers',
        ];
        $idsToDelete = [
            'subjects' => [],
            'subject_places' => [],
            'subject_people' => [],
            'subject_times' => [],

            'authors' => [],
            'genres' => [],
            'publishers' => [],
        ];
        $relationQueries = [
            'subjects' => function ($ids) {
                return DB::table('books')
                    ->join('book_subject', 'books.id', '=', 'book_id')
                    ->join('subjects', 'subject_id', '=', 'subjects.id')
                    ->whereIn('subjects.id', $ids)
                    ->select('books.id');
            },
            'subject_places' => function ($ids) {
                return DB::table('books')
                    ->join('book_subject_place', 'books.id', '=', 'book_id')
                    ->join('subject_places', 'subject_place_id', '=', 'subject_places.id')
                    ->whereIn('subject_places.id', $ids)
                    ->select('books.id');
            },
            'subject_people' => function ($ids) {
                return DB::table('books')
                    ->join('book_subject_people', 'books.id', '=', 'book_id')
                    ->join('subject_people', 'subject_people_id', '=', 'subject_people.id')
                    ->whereIn('subject_people.id', $ids)
                    ->select('books.id');
            },
            'subject_times' => function ($ids) {
                return DB::table('books')
                    ->join('book_subject_time', 'books.id', '=', 'book_id')
                    ->join('subject_times', 'subject_time_id', '=', 'subject_times.id')
                    ->whereIn('subject_times.id', $ids)
                    ->select('books.id');
            },

            'authors' => function ($ids) {
                return DB::table('books')
                    ->join('author_book', 'books.id', '=', 'book_id')
                    ->join('authors', 'author_id', '=', 'authors.id')
                    ->whereIn('authors.id', $ids)
                    ->select('books.id');
            },
            'genres' => function ($ids) {
                return DB::table('books')
                    ->join('book_genre', 'books.id', '=', 'book_id')
                    ->join('genres', 'genre_id', '=', 'genres.id')
                    ->whereIn('genres.id', $ids)
                    ->select('books.id');
            },
            'publishers' => function ($ids) {
                return DB::table('books')
                    ->join('book_publisher', 'books.id', '=', 'book_id')
                    ->join('publishers', 'publisher_id', '=', 'publishers.id')
                    ->whereIn('publishers.id', $ids)
                    ->select('books.id');
            },
        ];

        $bookIdsToDelete = [];

        foreach ($tables as $table) {
            DB::table($table)
                ->select(['id', 'name'])
                ->chunkById(100, function ($items) use (
                    $table, $words, &$idsToDelete
                ) {
                    foreach ($items as $item) {
                        foreach ($words as $word) {
                            if (preg_match("/\b$word\b/i", $item->name)) {
                                $idsToDelete[$table][] = $item->id;
                            }
                        }
                    }
                });
        }

        foreach ($tables as $table) {
            $this->output->writeln($table . ' --- ' . count($idsToDelete[$table]));
        }

        foreach ($tables as $table) {
            foreach (collect($idsToDelete[$table])->chunk(20) as $chunk) {
                $booksData = $relationQueries[$table]($chunk->toArray())->get();
                $ids = collect($booksData)->pluck('id')->toArray();
                foreach ($ids as $id) {
                    $bookIdsToDelete[$id] = true;
                }
            }
        }
        $this->output->writeln(count($bookIdsToDelete));

        DB::table('books')
            ->select(['id', 'name', 'description'])
            ->chunkById(100, function ($books) use ($words, &$bookIdsToDelete) {
                foreach ($books as $book) {
                    foreach ($words as $word) {
                        if (preg_match("/\b$word\b/i", $book->name) || preg_match("/\b$word\b/i", $book->description)) {
                            $bookIdsToDelete[$book->id] = true;
                        }
                    }
                }
            });
        $this->output->writeln(count($bookIdsToDelete));

        foreach (collect(array_keys($bookIdsToDelete))->chunk(20) as $chunk) {
            DB::table('books')
                ->whereIn('id', $chunk->toArray())
                ->delete();
        }
        $this->output->writeln('books cleared!');

        foreach ($tables as $table) {
            foreach (collect($idsToDelete[$table])->chunk(20) as $chunk) {
                DB::table($table)
                    ->whereIn('id', $chunk->toArray())
                    ->delete();
            }
            $this->output->writeln("$table cleared!");
        }
    }
}
