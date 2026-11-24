<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('occurred_at');
            // restrictOnDelete: a user or client with log entries can't be deleted (deactivate instead).
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('role', 20)->nullable();            // actor's role AT THE TIME
            $table->foreignId('client_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event', 40)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable();       // 45 chars fits IPv6
            $table->string('user_agent')->nullable();
            $table->longText('properties')->nullable();          // exact JSON text that was hashed
            $table->char('prev_hash', 64);
            $table->char('hash', 64)->unique();
            $table->index(['subject_type', 'subject_id']);
        });

        // Layer 2: the database itself refuses to change or remove log rows (NFR-3).
        DB::unprepared("CREATE TRIGGER activity_logs_block_update BEFORE UPDATE ON activity_logs
            FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activity_logs is append-only'");
        DB::unprepared("CREATE TRIGGER activity_logs_block_delete BEFORE DELETE ON activity_logs
            FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activity_logs is append-only'");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS activity_logs_block_update');
        DB::unprepared('DROP TRIGGER IF EXISTS activity_logs_block_delete');
        Schema::dropIfExists('activity_logs');
    }
};
