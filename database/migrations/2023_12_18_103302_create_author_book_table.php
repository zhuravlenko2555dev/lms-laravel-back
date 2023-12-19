<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('author_book', function (Blueprint $table) {
            $table->foreignId('book_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('author_id')
                ->constrained()
                ->cascadeOnUpdate();

            $table->unique(['book_id', 'author_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('author_book');
    }
};
