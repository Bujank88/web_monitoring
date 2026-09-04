<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leads_powerhouse')) {
            Schema::create('leads_powerhouse', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('source_id')->nullable();
                $table->integer('sector_id');
                $table->string('regional')->nullable();
                $table->string('kode_voucher')->nullable();
                $table->string('company_name');
                $table->string('mobile_phone')->unique();
                $table->string('email')->nullable()->unique();
                $table->integer('status')->default(0);
                $table->string('nama')->nullable();
                $table->text('address')->nullable();
                $table->string('myads_account')->nullable();
                $table->enum('data_type', ['Leads', 'Eksisting Akun'])->default('Leads');
                $table->decimal('komitmen', 5, 2)->default(0);
                $table->integer('plan_min_topup')->default(0);
                $table->text('remarks')->nullable();
                $table->string('flag_event')->nullable();
                $table->string('usecase')->nullable();
                $table->string('reg_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_powerhouse');
    }
};
