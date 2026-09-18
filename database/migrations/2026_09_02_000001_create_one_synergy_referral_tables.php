<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('one_synergy_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('referral_code', 50)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions_one_synergy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_id', 191)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('channel_code', 191)->nullable();
            $table->string('customer_phone', 191);
            $table->string('customer_email', 191);
            $table->string('customer_name', 191)->nullable();
            $table->string('referral_code', 191)->nullable()->index();
            $table->unsignedBigInteger('transaction_amount');
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('grand_total_amount')->default(0);
            $table->string('product_category', 191)->default('MYADS2');
            $table->string('product_type', 191)->default('ADVERTISEMENT');
            $table->string('product_detail', 191)->default('Advertisement Payment');
            $table->string('status', 50)->default('PENDING')->index();
            $table->string('payment_code', 191)->nullable();
            $table->text('qris_url')->nullable();
            $table->text('redirect_url')->nullable();
            $table->timestamp('transaction_date')->nullable()->index();
            $table->timestamp('transaction_expire')->nullable();
            $table->timestamp('payment_date')->nullable()->index();
            $table->timestamp('success_email_sent_at')->nullable();
            $table->longText('gateway_response')->nullable();
            $table->longText('callback_payload')->nullable();
            $table->string('id_transaksi_myads', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions_one_synergy');
        Schema::dropIfExists('one_synergy_referrals');
    }
};
