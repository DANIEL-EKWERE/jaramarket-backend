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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('assurance_user_id')->after('status')->nullable()->constrained('users')->onDelete('cascade');
            $table->datetime('assurance_at')->after('assurance_user_id')->nullable();
            $table->boolean('pass_quality_assurance')->after('assurance_at')->nullable();
            $table->text('remark')->after('pass_quality_assurance')->nullable();
            $table->boolean('re_assigned')->after('remark')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['assurance_user_id','assurance_at','pass_quality_assurance','remark','re_assigned']);
        });
    }
};
