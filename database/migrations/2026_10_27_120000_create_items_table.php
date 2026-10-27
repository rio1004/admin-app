<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('image')->nullable();
            $table->string('link', 2048)->nullable();
            $table->string('slug', 200)->unique();
            $table->timestamps();
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
