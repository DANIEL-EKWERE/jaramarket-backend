<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Enums\TransferStatusEnum;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->string('recipient_code');
            $table->integer('amount');
            $table->morphs('owner');
            $table->string('bank_code');
            $table->string('account_number');
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('status')->default(TransferStatusEnum::PENDING());
            $table->integer('failures')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
