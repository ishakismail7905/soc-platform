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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');          // deactivate instead of delete
            $table->timestamp('locked_at')->nullable()->after('is_active');      // FR-11 lockout (Task 5)
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('locked_at');
            $table->boolean('must_change_password')->default(false)->after('failed_login_attempts');
            $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
            $table->timestamp('last_login_at')->nullable()->after('password_changed_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });

        // FR-11: no reuse of recent passwords. Stores bcrypt hashes only, never plain text.
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('password');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_histories');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'locked_at', 'failed_login_attempts', 'must_change_password',
                'password_changed_at', 'last_login_at', 'last_login_ip']);
        });
    }
};
