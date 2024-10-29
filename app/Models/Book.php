<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Book extends Model
{
    protected $fillable = [
        'olid',
        'isbn',
        'name',
        'publish_date',
        'description',
        'image_small',
        'image_medium',
        'image_large',
    ];

    public function authors(): HasMany
    {
        return $this->hasMany(Author::class);
    }

    public function genres(): HasMany
    {
        return $this->hasMany(Genre::class);
    }

    public function language(): HasOne
    {
        return $this->hasOne(Language::class);
    }

    public function publisher(): HasOne
    {
        return $this->hasOne(Publisher::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function subjectPlaces(): HasMany
    {
        return $this->hasMany(SubjectPlace::class);
    }

    public function subjectPeople(): HasMany
    {
        return $this->hasMany(SubjectPeople::class);
    }

    public function subjectTimes(): HasMany
    {
        return $this->hasMany(SubjectTime::class);
    }
}
