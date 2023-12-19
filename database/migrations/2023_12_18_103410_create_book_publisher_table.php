<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('book_publisher', function (Blueprint $table) {
            $table->foreignId('book_id')
                ->unique()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('publisher_id')
                ->constrained()
                ->cascadeOnUpdate();
        });
    }

    public function down()
    {
        Schema::dropIfExists('book_publisher');
    }
};
