<?php

namespace App\Console\Commands;

use App\Enums\SubjectTypeEnum;
use DB;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class MoveBookSubjectsCommand extends Command
{
    protected $signature = 'move-book-subjects';

    protected $description = 'Move book subjects to one table';

    private ProgressBar $progressBar;

    public function handle(): void
    {
        $subjects = [
            [
                'table' => 'subject_places',
                'type' => SubjectTypeEnum::PLACE->value,
                'relation_table' => 'book_subject_place',
                'relation_table_key' => 'subject_place_id',
            ],
            [
                'table' => 'subject_people',
                'type' => SubjectTypeEnum::PEOPLE->value,
                'relation_table' => 'book_subject_people',
                'relation_table_key' => 'subject_people_id',
            ],
            [
                'table' => 'subject_times',
                'type' => SubjectTypeEnum::TIME->value,
                'relation_table' => 'book_subject_time',
                'relation_table_key' => 'subject_time_id',
            ],
        ];

        $count = 0;
        foreach ($subjects as $subject) {
            $count += DB::table($subject['table'])->count();
        }

        $this->progressBar = $this->output->createProgressBar($count);
        $this->progressBar->start();

        foreach ($subjects as $subject) {
            DB::table($subject['table'])
                ->chunkById(100, function ($sItems) use ($subject) {
                    foreach ($sItems as $sItem) {
                        $sId = DB::table('subjects')
                            ->insertGetId([
                                'type' => $subject['type'],
                                'name' => $sItem->name,
                            ]);

                        DB::table($subject['relation_table'])
                            ->where($subject['relation_table_key'], $sItem->id)
                            ->chunkById(100, function ($rtItems) use ($sId) {
                                $rtDataToInsert = [];
                                foreach ($rtItems as $rtItem) {
                                    $rtDataToInsert[] = [
                                        'book_id' => $rtItem->book_id,
                                        'subject_id' => $sId,
                                    ];
                                }

                                DB::table('book_subject')->insert($rtDataToInsert);
                            }, 'book_id');
                    }

                    $this->progressBar->advance($sItems->count());
                });
        }

        $this->progressBar->finish();
    }
}
