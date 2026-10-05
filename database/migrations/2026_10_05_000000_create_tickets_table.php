<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sequences', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedBigInteger('next_number');
        });
        DB::table('ticket_sequences')->insert(['id' => 1, 'next_number' => 1]);

        Schema::create('tickets', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('ticket_number', 32)->unique();
            $table->string('original_ticket_number', 32)->nullable();
            $table->string('user_name')->nullable();
            $table->dateTime('requested_at');
            $table->string('request_type', 50)->nullable();
            $table->string('complaint_type', 100)->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('method', 50)->nullable();
            $table->text('account_campaign_id')->nullable();
            $table->text('diagnosis_issue')->nullable();
            $table->text('evidence_reference')->nullable();
            $table->text('resolution_update')->nullable();
            $table->string('status', 32)->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->string('priority', 32)->nullable();
            $table->string('handling_level', 32)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('import_source_key', 64)->nullable()->unique();
            $table->json('import_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_sequences');
    }
};
