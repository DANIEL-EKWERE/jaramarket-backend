<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add group column to permissions
        if (!Schema::hasColumn('permissions', 'group')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('group')->default('general')->after('slug');
            });
        }

        // Create user_permissions pivot (replaces admin_permissions)
        if (!Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('permission_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['user_id', 'permission_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::table('permissions', fn ($t) => $t->dropColumn('group'));
    }
};
