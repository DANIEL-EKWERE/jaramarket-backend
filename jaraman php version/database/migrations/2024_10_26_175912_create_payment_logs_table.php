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
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('txn_ref')->unique()->index();
            $table->string('authorization_url')->nullable();
            $table->integer('amount');
            $table->json('meta')->nullable();
            $table->string('gateway_response')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('transaction_mode')->nullable();
            $table->unsignedBigInteger('transaction_owner_id')->nullable();
            $table->string('transaction_owner_type')->nullable();
            $table->unsignedBigInteger('transaction_initiator_id')->nullable();
            $table->string('transaction_initiator_type')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users');
            $table->string('provider')->nullable();
            $table->string('plan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
