<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadSpectrum extends Model
{
    public const REQUIRED_HEADERS = [
        'provider' => 'Provider',
        'last_name' => 'Last Name',
        'company_account' => 'Company / Account',
        'mobile_phone' => 'Mobile Phone',
        'email' => 'email',
        'company_size' => 'Company Size',
        'product' => 'product',
        'lead_source' => 'Lead Source',
        'create_date' => 'Create Date',
        'share_date' => 'Share Date',
        'description' => 'Description',
        'pillar' => 'Pillar',
        'industry_sector' => 'Sektor Industri Company',
        'fu' => 'FU',
        'response' => 'Respon',
    ];

    public const HEADERS = [
        'provider' => 'Provider',
        'last_name' => 'Last Name',
        'company_account' => 'Company / Account',
        'mobile_phone' => 'Mobile Phone',
        'email' => 'email',
        'company_size' => 'Company Size',
        'product' => 'product',
        'lead_source' => 'Lead Source',
        'create_date' => 'Create Date',
        'share_date' => 'Share Date',
        'description' => 'Description',
        'pillar' => 'Pillar',
        'industry_sector' => 'Sektor Industri Company',
        'fu' => 'FU',
        'response' => 'Respon',
        'utm_source' => 'UTM Source',
        'tgl_call_1st' => 'Tgl Call 1st',
        'status_call' => 'Status Call',
        'reason_call' => 'Reason Call',
        'sub_reason_call' => 'Sub Reason Call',
        'tgl_call_fu' => 'Tgl Call FU',
        'status_call_fu' => 'Status Call FU',
        'reason_call_fu' => 'Reason Call (FU)',
        'sub_reason_call_fu' => 'Sub Reason Call (FU)',
        'notes' => 'Notes',
        'region' => 'Region',
        'alamat' => 'Alamat',
        'cek_mea' => 'Cek MEA',
        'nama_pic' => 'Nama PIC',
        'nomor_pic' => 'Nomor PIC',
        'email_pic' => 'Email PIC',
        'karyawan' => 'Karyawan',
        'source_leads' => 'Source Leads',
        'product_offer' => 'Product Offer',
        'est_cb' => 'Est. CB',
        'est_revenue' => 'Est. Revenue',
        'revenue' => 'Revenue',
        'detail_kebutuhan' => 'Detail Kebutuhan',
        'agent_leads' => 'Agent Leads',
        'tgl_dispatch' => 'Tgl Dispatch',
        'corp_name_valid' => 'Corp Name Valid',
        'bulan_dispatch' => 'Bulan Dispatch',
        'update_status' => 'UPDATE',
    ];

    public const INITIAL_FIELDS = [
        'provider' => 'Provider',
        'last_name' => 'Last Name',
        'company_account' => 'Company / Account',
        'mobile_phone' => 'Mobile Phone',
        'email' => 'email',
        'company_size' => 'Company Size',
        'product' => 'product',
        'lead_source' => 'Lead Source',
        'create_date' => 'Create Date',
        'share_date' => 'Share Date',
        'description' => 'Description',
        'pillar' => 'Pillar',
        'industry_sector' => 'Sektor Industri Company',
        'fu' => 'FU',
        'response' => 'Respon',
        'utm_source' => 'UTM Source',
    ];

    public const CANVASSER_FIELDS = [
        'tgl_call_1st' => 'Tgl Call 1st',
        'status_call' => 'Status Call',
        'reason_call' => 'Reason Call',
        'sub_reason_call' => 'Sub Reason Call',
        'tgl_call_fu' => 'Tgl Call FU',
        'status_call_fu' => 'Status Call FU',
        'reason_call_fu' => 'Reason Call (FU)',
        'sub_reason_call_fu' => 'Sub Reason Call (FU)',
        'notes' => 'Notes',
        'region' => 'Region',
        'alamat' => 'Alamat',
        'cek_mea' => 'Cek MEA',
        'nama_pic' => 'Nama PIC',
        'nomor_pic' => 'Nomor PIC',
        'email_pic' => 'Email PIC',
        'karyawan' => 'Karyawan',
        'source_leads' => 'Source Leads',
        'product_offer' => 'Product Offer',
        'est_cb' => 'Est. CB',
        'est_revenue' => 'Est. Revenue',
        'revenue' => 'Revenue',
        'detail_kebutuhan' => 'Detail Kebutuhan',
        'agent_leads' => 'Agent Leads',
        'tgl_dispatch' => 'Tgl Dispatch',
        'corp_name_valid' => 'Corp Name Valid',
        'bulan_dispatch' => 'Bulan Dispatch',
        'update_status' => 'UPDATE',
    ];

    protected $table = 'leads_spectrum';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'create_date' => 'date',
            'share_date' => 'date',
            'tgl_dispatch' => 'date',
        ];
    }
}