<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existingColumns = Schema::getColumnListing('leads_spectrum');

        Schema::table('leads_spectrum', function (Blueprint $table) use ($existingColumns) {
            if (! in_array('tgl_call_1st', $existingColumns, true)) {
                $table->string('tgl_call_1st')->nullable()->after('utm_source');
            }
            if (! in_array('status_call', $existingColumns, true)) {
                $table->string('status_call')->nullable()->after('tgl_call_1st');
            }
            if (! in_array('reason_call', $existingColumns, true)) {
                $table->string('reason_call')->nullable()->after('status_call');
            }
            if (! in_array('sub_reason_call', $existingColumns, true)) {
                $table->string('sub_reason_call')->nullable()->after('reason_call');
            }
            if (! in_array('tgl_call_fu', $existingColumns, true)) {
                $table->string('tgl_call_fu')->nullable()->after('sub_reason_call');
            }
            if (! in_array('status_call_fu', $existingColumns, true)) {
                $table->string('status_call_fu')->nullable()->after('tgl_call_fu');
            }
            if (! in_array('reason_call_fu', $existingColumns, true)) {
                $table->string('reason_call_fu')->nullable()->after('status_call_fu');
            }
            if (! in_array('sub_reason_call_fu', $existingColumns, true)) {
                $table->string('sub_reason_call_fu')->nullable()->after('reason_call_fu');
            }
            if (! in_array('notes', $existingColumns, true)) {
                $table->text('notes')->nullable()->after('sub_reason_call_fu');
            }
            if (! in_array('region', $existingColumns, true)) {
                $table->string('region')->nullable()->after('notes');
            }
            if (! in_array('alamat', $existingColumns, true)) {
                $table->text('alamat')->nullable()->after('region');
            }
            if (! in_array('cek_mea', $existingColumns, true)) {
                $table->string('cek_mea')->nullable()->after('alamat');
            }
            if (! in_array('nama_pic', $existingColumns, true)) {
                $table->string('nama_pic')->nullable()->after('cek_mea');
            }
            if (! in_array('nomor_pic', $existingColumns, true)) {
                $table->string('nomor_pic')->nullable()->after('nama_pic');
            }
            if (! in_array('email_pic', $existingColumns, true)) {
                $table->string('email_pic')->nullable()->after('nomor_pic');
            }
            if (! in_array('karyawan', $existingColumns, true)) {
                $table->string('karyawan')->nullable()->after('email_pic');
            }
            if (! in_array('source_leads', $existingColumns, true)) {
                $table->string('source_leads')->nullable()->after('karyawan');
            }
            if (! in_array('product_offer', $existingColumns, true)) {
                $table->string('product_offer')->nullable()->after('source_leads');
            }
            if (! in_array('est_cb', $existingColumns, true)) {
                $table->decimal('est_cb', 15, 2)->nullable()->after('product_offer');
            }
            if (! in_array('est_revenue', $existingColumns, true)) {
                $table->decimal('est_revenue', 15, 2)->nullable()->after('est_cb');
            }
            if (! in_array('revenue', $existingColumns, true)) {
                $table->decimal('revenue', 15, 2)->nullable()->after('est_revenue');
            }
            if (! in_array('detail_kebutuhan', $existingColumns, true)) {
                $table->text('detail_kebutuhan')->nullable()->after('revenue');
            }
            if (! in_array('agent_leads', $existingColumns, true)) {
                $table->string('agent_leads')->nullable()->after('detail_kebutuhan');
            }
            if (! in_array('tgl_dispatch', $existingColumns, true)) {
                $table->date('tgl_dispatch')->nullable()->after('agent_leads');
            }
            if (! in_array('corp_name_valid', $existingColumns, true)) {
                $table->string('corp_name_valid')->nullable()->after('tgl_dispatch');
            }
            if (! in_array('bulan_dispatch', $existingColumns, true)) {
                $table->string('bulan_dispatch')->nullable()->after('corp_name_valid');
            }
            if (! in_array('update_status', $existingColumns, true)) {
                $table->string('update_status')->nullable()->after('bulan_dispatch');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads_spectrum', function (Blueprint $table) {
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
