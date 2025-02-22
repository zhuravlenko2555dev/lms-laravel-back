<?php

use App\Models\Media;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediables', function (Blueprint $table) {
            $table->foreignIdFor(Media::class)
                ->constrained();
            $table->morphs('mediable');

            $table->unsignedTinyInteger('order')->default(1);

            $table->primary(['media_id', 'mediable_type', 'mediable_id']);
            $table->index(['mediable_id', 'mediable_type']);
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
    }
};
