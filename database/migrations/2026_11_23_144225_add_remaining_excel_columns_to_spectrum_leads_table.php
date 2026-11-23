<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spectrum_leads', function (Blueprint $table) {
            $table->string('tgl_call_1st')->nullable()->after('utm_source');
            $table->string('status_call')->nullable()->after('tgl_call_1st');
            $table->string('reason_call')->nullable()->after('status_call');
            $table->string('sub_reason_call')->nullable()->after('reason_call');
            $table->string('tgl_call_fu')->nullable()->after('sub_reason_call');
            $table->string('status_call_fu')->nullable()->after('tgl_call_fu');
            $table->string('reason_call_fu')->nullable()->after('status_call_fu');
            $table->string('sub_reason_call_fu')->nullable()->after('reason_call_fu');
            $table->text('notes')->nullable()->after('sub_reason_call_fu');
            $table->string('region')->nullable()->after('notes');
            $table->text('alamat')->nullable()->after('region');
            $table->string('cek_mea')->nullable()->after('alamat');
            $table->string('nama_pic')->nullable()->after('cek_mea');
            $table->string('nomor_pic')->nullable()->after('nama_pic');
            $table->string('email_pic')->nullable()->after('nomor_pic');
            $table->string('karyawan')->nullable()->after('email_pic');
            $table->string('source_leads')->nullable()->after('karyawan');
            $table->string('product_offer')->nullable()->after('source_leads');
            $table->decimal('est_cb', 15, 2)->nullable()->after('product_offer');
            $table->decimal('est_revenue', 15, 2)->nullable()->after('est_cb');
            $table->decimal('revenue', 15, 2)->nullable()->after('est_revenue');
            $table->text('detail_kebutuhan')->nullable()->after('revenue');
            $table->string('agent_leads')->nullable()->after('detail_kebutuhan');
            $table->date('tgl_dispatch')->nullable()->after('agent_leads');
            $table->string('corp_name_valid')->nullable()->after('tgl_dispatch');
            $table->string('bulan_dispatch')->nullable()->after('corp_name_valid');
            $table->string('update_status')->nullable()->after('bulan_dispatch');
        });
    }

    public function down(): void
    {
        Schema::table('spectrum_leads', function (Blueprint $table) {
            $table->dropColumn([
                'tgl_call_1st', 'status_call', 'reason_call', 'sub_reason_call',
                'tgl_call_fu', 'status_call_fu', 'reason_call_fu', 'sub_reason_call_fu',
                'notes', 'region', 'alamat', 'cek_mea',
                'nama_pic', 'nomor_pic', 'email_pic', 'karyawan',
                'source_leads', 'product_offer', 'est_cb', 'est_revenue', 'revenue',
                'detail_kebutuhan', 'agent_leads', 'tgl_dispatch',
                'corp_name_valid', 'bulan_dispatch', 'update_status',
            ]);
        });
    }
};