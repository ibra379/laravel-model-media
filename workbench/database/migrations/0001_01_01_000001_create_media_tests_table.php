<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_tests', function (Blueprint $table) {
            $table->id();
            $table->string('avatar')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('slug')->nullable();
            $table->timestamps();

            $table->unique(['slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_tests');
    }
};
