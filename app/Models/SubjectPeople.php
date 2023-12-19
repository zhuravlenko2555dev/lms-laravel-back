<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectPeople extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alias',
        'name',
    ];
}
