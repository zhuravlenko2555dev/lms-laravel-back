<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('book_subject_place', function (Blueprint $table) {
            $table->foreignId('book_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('subject_place_id')
                ->constrained()
                ->cascadeOnUpdate();

            $table->unique(['book_id', 'subject_place_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('book_subject_place');
    }
};
