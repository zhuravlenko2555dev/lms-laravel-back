<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FromSQLSeeder extends Seeder
{
    public function run()
    {
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/authors.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/genres.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/languages.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/publishers.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/books.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/book_authors.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/book_genres.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/book_languages.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/book_publishers.sql'));
        DB::unprepared(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dump' . '/all_subjects_data.sql'));
    }
}
