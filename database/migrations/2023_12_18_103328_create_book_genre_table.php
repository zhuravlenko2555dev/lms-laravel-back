<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('book_genre', function (Blueprint $table) {
            $table->foreignId('book_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('genre_id')
                ->constrained()
                ->cascadeOnUpdate();

            $table->unique(['book_id', 'genre_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('book_genre');
    }
};
