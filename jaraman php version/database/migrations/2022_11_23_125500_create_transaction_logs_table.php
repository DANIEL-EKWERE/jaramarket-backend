<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('account_owner'); // user account the record belongs to
            $table->nullableMorphs('owner'); // model the record belongs to
            $table->string('reference');
            $table->string('transaction_type');
            $table->double('amount');
            $table->double('old_balance');
            $table->double('new_balance');
            $table->string('status')->nullable();
            $table->boolean('is_refund')->default(0);
            $table->boolean('has_refund')->nullable();
            $table->foreignId('wallet_id')->nullable()->constrained('wallets');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_logs');
    }
};
