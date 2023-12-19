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

    /**
     * @return HasMany
     */
    public function authors(): HasMany
    {
        return $this->hasMany(Author::class);
    }

    /**
     * @return HasMany
     */
    public function genres(): HasMany
    {
        return $this->hasMany(Genre::class);
    }

    /**
     * @return HasOne
     */
    public function language(): HasOne
    {
        return $this->hasOne(Language::class);
    }

    /**
     * @return HasOne
     */
    public function publisher(): HasOne
    {
        return $this->hasOne(Publisher::class);
    }

    /**
     * @return HasMany
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * @return HasMany
     */
    public function subjectPlaces(): HasMany
    {
        return $this->hasMany(SubjectPlace::class);
    }

    /**
     * @return HasMany
     */
    public function subjectPeople(): HasMany
    {
        return $this->hasMany(SubjectPeople::class);
    }

    /**
     * @return HasMany
     */
    public function subjectTimes(): HasMany
    {
        return $this->hasMany(SubjectTime::class);
    }
}
