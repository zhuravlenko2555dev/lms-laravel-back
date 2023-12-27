<?php

namespace Database\Seeders;

use App\Enums\UserAbilityEnum;
use App\Enums\UserRoleEnum;
use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Publisher;
use App\Models\Subject;
use App\Models\SubjectPeople;
use App\Models\SubjectPlace;
use App\Models\SubjectTime;
use Bouncer;
use Illuminate\Database\Seeder;

class BouncerSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            Author::class,
            Genre::class,
            Language::class,
            Publisher::class,

            Book::class,

            Subject::class,
            SubjectPlace::class,
            SubjectPeople::class,
            SubjectTime::class,
        ];

        Bouncer::allow(UserRoleEnum::ADMIN->value)->everything();

        foreach ($classes as $class) {
            Bouncer::allow(UserRoleEnum::LIBRARIAN->value)->to([
                UserAbilityEnum::LIST->value,
                UserAbilityEnum::VIEW->value,
            ], $class);
            Bouncer::allow(UserRoleEnum::READER->value)->to([
                UserAbilityEnum::LIST->value,
                UserAbilityEnum::VIEW->value,
            ], $class);
        }
    }
}
