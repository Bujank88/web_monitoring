<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_panen_poin_v5')) {
            Schema::create('user_panen_poin_v5', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('nama_pelanggan');
                $table->string('akun_myads_pelanggan')->index();
                $table->string('nomor_hp_pelanggan')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('akun_panen_poin_v5')) {
            Schema::create('akun_panen_poin_v5', function (Blueprint $table) {
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
        }

        if (! Schema::hasTable('summary_panen_poin_v5')) {
            Schema::create('summary_panen_poin_v5', function (Blueprint $table) {
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
        }

        if (! Schema::hasTable('prize_redeems_v5')) {
            Schema::create('prize_redeems_v5', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('prize_id');
                $table->integer('point_used');
                $table->date('period_start');
                $table->date('period_end');
                $table->timestamps();

                $table->index(['user_id', 'period_start', 'period_end']);
            });
        }

        if (! Schema::hasTable('prizes_v5')) {
            Schema::create('prizes_v5', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('img');
                $table->string('name');
                $table->integer('point');
                $table->integer('stock');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prizes_v5');
        Schema::dropIfExists('prize_redeems_v5');
        Schema::dropIfExists('summary_panen_poin_v5');
        Schema::dropIfExists('akun_panen_poin_v5');
        Schema::dropIfExists('user_panen_poin_v5');
    }
};
