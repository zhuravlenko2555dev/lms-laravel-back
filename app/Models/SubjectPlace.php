<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectPlace extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alias',
        'name',
    ];
}
