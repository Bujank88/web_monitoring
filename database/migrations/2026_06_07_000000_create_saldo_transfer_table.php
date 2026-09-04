<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldo_transfer', function (Blueprint $table) {
            $table->id();
            $table->string('email_client');
            $table->decimal('amount', 18, 2);
            $table->dateTime('tgl_transaksi');
            $table->string('parent_email');
            $table->timestamps();

            $table->index('email_client', 'saldo_transfer_email_client_index');
            $table->index('parent_email', 'saldo_transfer_parent_email_index');
            $table->index('tgl_transaksi', 'saldo_transfer_tgl_transaksi_index');
            $table->index(
                ['parent_email', 'tgl_transaksi'],
                'saldo_transfer_parent_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_transfer');
    }
};
