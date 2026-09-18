<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_panen_poin_v3', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nama_pelanggan');
            $table->string('akun_myads_pelanggan')->index();
            $table->string('nomor_hp_pelanggan')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('akun_panen_poin_v3', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('nama_akun');
            $table->string('email_client')->unique();
            $table->string('password');
            $table->string('source', 50);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('summary_panen_poin_v3', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nama_canvasser');
            $table->string('email_client')->index();
            $table->string('nomor_hp_client')->nullable();
            $table->string('source', 50)->default('leads_master');
            $table->decimal('total_settlement', 15, 2)->default(0);
            $table->integer('poin_bulan_ini')->default(0);
            $table->integer('poin_akumulasi')->default(0);
            $table->integer('poin')->default(0);
            $table->integer('poin_package')->default(0);
            $table->integer('poin_redeem')->default(0);
            $table->string('remark', 50)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('periode_label', 50);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'email_client']);
            $table->index(['period_start', 'period_end']);
        });

        Schema::create('prize_redeems_v3', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('prize_id');
            $table->integer('point_used');
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->index(['user_id', 'period_start', 'period_end']);
        });

        if (Schema::hasTable('user_panen_poin_v2')) {
            DB::statement("
                INSERT INTO user_panen_poin_v3 (user_id, nama_pelanggan, akun_myads_pelanggan, nomor_hp_pelanggan, created_at, updated_at)
                SELECT user_id, nama_pelanggan, akun_myads_pelanggan, nomor_hp_pelanggan, created_at, updated_at
                FROM user_panen_poin_v2
            ");
        }

        if (Schema::hasTable('akun_panen_poin_v2')) {
            DB::statement("
                INSERT INTO akun_panen_poin_v3 (uuid, user_id, nama_akun, email_client, password, source, created_at, updated_at)
                SELECT uuid, user_id, nama_akun, email_client, password, source, created_at, updated_at
                FROM akun_panen_poin_v2
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_redeems_v3');
        Schema::dropIfExists('summary_panen_poin_v3');
        Schema::dropIfExists('akun_panen_poin_v3');
        Schema::dropIfExists('user_panen_poin_v3');
    }
};
