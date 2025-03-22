<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('type');
            $table->string('name')->index();

            $table->unique(['type', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subjects');
    }
};
