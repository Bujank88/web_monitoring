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
        Schema::create('summary_panen_poin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nama_canvasser');
            $table->string('email_client')->index();
            $table->string('nomor_hp_client')->nullable();
            $table->decimal('total_settlement', 15, 2)->default(0);
            $table->integer('poin_bulan_ini')->default(0);
            $table->integer('poin_akumulasi')->default(0);
            $table->integer('poin')->default(0);
            $table->string('bulan', 20);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'email_client']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('summary_panen_poin');
    }
};
