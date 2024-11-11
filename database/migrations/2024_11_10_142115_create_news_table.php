<?php

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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->unique()->index();
            $table->string('source');
            $table->string('category');
            $table->string('author')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('provider');
            $table->timestamps();

            // indexing 
            $table->index('category');
            $table->index('provider');
            $table->index('author');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
