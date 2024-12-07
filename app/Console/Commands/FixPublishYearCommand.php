<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class FixPublishYearCommand extends Command
{
    protected $signature = 'fix-publish-year';

    protected $description = 'Leave only year in publish year column';

    public function handle(): void
    {
        DB::table('books')
            ->select(['id', 'publish_date'])
            ->chunkById(100, function ($books) {
                foreach ($books as $book) {
                    if (preg_match("/\d{4}/i", $book->publish_date, $matches)) {
                        if ($year = $matches[0] ?? null) {
                            DB::table('books')
                                ->where('id', $book->id)
                                ->update([
                                    'publish_year' => (int) $year,
                                ]);
                        }
                    }
                }
            });
    }
}
