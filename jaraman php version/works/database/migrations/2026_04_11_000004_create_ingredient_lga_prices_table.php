<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_lga_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lga_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['ingredient_id', 'lga_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_lga_prices');
    }
};
