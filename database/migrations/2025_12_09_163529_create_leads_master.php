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
        if (!Schema::hasTable('leads_master')) {
            Schema::create('leads_master', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');          // Handled by Canvasser
                $table->integer('source_id')->nullable();          // Source
                $table->integer('sector_id');          // Sector
                $table->string('regional')->nullable();          // Regional
                $table->string('kode_voucher')->nullable();        // Kode Voucher
                $table->string('company_name');        // Company Name
                $table->string('mobile_phone');        // Mobile Phone
                $table->string('email')->nullable();   // Email
                $table->integer('status')->default(0); // cek (ex: "OK")
                $table->string('nama')->nullable();    // Nama
                $table->text('address')->nullable();  // Address
                $table->string('myads_account')->nullable();   // Akun MyAds kalau ada
                $table->enum('data_type', ['Leads', 'Eksisting Akun']);   // Data Type
                $table->decimal('komitmen', 5, 2)->default(0);   // Data Type
                $table->integer('plan_min_topup')->default(0);   // Data Type
                $table->text('remarks')->nullable();   // Remarks
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads_master');
    }
};
