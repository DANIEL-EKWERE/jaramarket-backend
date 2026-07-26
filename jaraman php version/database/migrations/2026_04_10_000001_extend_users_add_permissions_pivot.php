<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend users table ──────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            // Admin state scoping
            if (! Schema::hasColumn('users', 'state_id')) {
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete()->after('country_id');
            }

            // Firebase push token
            if (! Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('payment_method');
            }

            // Soft-verify vendors
            if (! Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('is_active');
            }
        });

        // ── 2. Extend permissions table ────────────────────────────────────
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (! Schema::hasColumn('permissions', 'group')) {
                    $table->string('group')->default('general')->after('slug');
                }
            });
        }

        // ── 3. Create user_permissions pivot (replaces admin_permissions) ──
        if (! Schema::hasTable('user_permissions')) {
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

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\State::class);
            $table->dropColumn(['state_id', 'fcm_token', 'is_verified']);
        });
    }
};
