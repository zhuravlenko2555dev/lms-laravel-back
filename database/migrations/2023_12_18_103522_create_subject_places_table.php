<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('subject_places', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->unique();
            $table->string('name')->unique();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subject_places');
    }
};
