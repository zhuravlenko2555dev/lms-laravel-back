<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('olid', 15)->unique()->nullable();
            $table->string('isbn', 15)->unique()->nullable();
            $table->string('name')->index();
            $table->string('publish_date')->nullable();
            $table->text('description')->nullable();

            $table->foreignIdFor(\App\Models\Publisher::class)
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate();

            $table->string('image_small')->nullable();
            $table->string('image_medium')->nullable();
            $table->string('image_large')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books');
    }
};
