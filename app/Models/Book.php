<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Book extends Model
{
    protected $fillable = [
        'olid',
        'isbn',
        'name',
        'publish_year',
        'description',
        'image_small',
        'image_medium',
        'image_large',
    ];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
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

    public function covers(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order')
            ->orderBy('order');
    }
}
