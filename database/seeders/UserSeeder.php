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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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

        $admin = User::factory(1)->create([
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password12345678'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ]);
        $admin->first()->assign(UserRoleEnum::ADMIN->value);

        $librarians = User::factory(5)->create();
        $librarians->each(function (User $librarian, int $i) use ($classes){
            $librarian->assign(UserRoleEnum::LIBRARIAN->value);

            switch ($i) {
                case 0:
                    $librarian->allow()->toManage($classes);
                    $librarian->allow(UserAbilityEnum::ISSUE_BOOKS->value);

                    break;
                case 1:
                    $librarian->allow()->toManage($classes);

                    break;
                case 2:
                    $librarian->allow(UserAbilityEnum::ISSUE_BOOKS->value);

                    break;
            }
        });

        $readers = User::factory(20)->create();
        $readers->each(function (User $reader) {
            $reader->assign(UserRoleEnum::READER->value);
        });
    }
}
