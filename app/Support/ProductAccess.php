<?php

namespace App\Support;

use Illuminate\Http\Request;

class ProductAccess
{
    public const REPORT_ROUTES = [
        'daily.topup.channel', 'daily_topup_data', 'daily_topup_by_province_data',
        'export.daily_topup', 'export.daily_topup_by_province',
        'admin.home', 'leads_data_api', 'regional_data', 'regional_chart_data',
        'regional_chart_data_for_ph', 'export.regional',
        'topup-canvasser', 'topup-canvasser.excel', 'topup-canvasser.pdf',
        'topup-canvasser.detail', 'topup-canvasser.detail.overview', 'topup-canvasser.detail.mom',
        'topup-canvasser.detail.trend', 'topup-canvasser.detail.transactions',
        'topup-canvasser.detail.top-leads', 'topup-canvasser.detail.top-leads.csv',
        'transaction-detail', 'transaction-detail.data',
        'sales-analysis.index', 'sales-analysis.script', 'sales-analysis.data',
        'mpcc.report', 'mpcc.report.data', 'mpcc.report.performance-data',
        'mpcc.report.area-summary-data', 'mpcc.report.export', 'mpcc.report.area-branch',
        'mpcc.report.area-branch.data', 'mpcc.report.area-branch.export', 'mpcc.report.pilot-city',
        'region-target', 'admin.monitoring.powerhouse_referral', 'powerhouse_voucher_data',
        'powerhouse_deal_topup_mom_data', 'powerhouse_saldo_transfer_detail_data',
        'admin.monitoring.powerhouse_semester', 'powerhouse_semester_deal_topup_mom_data',
        'powerhouse_semester_target_data', 'export.powerhouse_voucher',
        'powerhouse.2m.summary', 'powerhouse.2m.summary-data', 'powerhouse.2m.summary-export',
        'powerhouse.2m.show', 'powerhouse.2m.report',
        'am.referral.index', 'am.referral.data', 'am.referral.deal-data',
        'am.referral.saldo-data', 'am.referral.export',
        'mitra-sbp', 'export.mitra-sbp', 'export.agency', 'export.internal', 'export.performance-all',
        'report-campaign-sbp', 'report-campaign-sbp.data',
        'report-campaign-sbp.export', 'report-saldo-sbp', 'report-saldo-sbp.data', 'report-saldo-sbp.export',
        'admin.monitoring.canvasser_voucher', 'canvasser_voucher_data', 'canvasser_voucher_summary',
        'export.canvasser_voucher', 'export.canvasser_voucher_summary',
        'amlevelup.summary', 'amlevelup.summary-data', 'amlevelup.clients',
        'amlevelup.report', 'amlevelup.report-data', 'amlevelup.report-canvasser',
        'amlevelup.report-canvasser-data', 'amlevelup.report-ph', 'amlevelup.report-ph-data',
        'amlevelup.export', 'amlevelup.list-akun', 'amlevelup.akun-data', 'amlevelup.akun-detail',
    ];

    public static function allows(Request $request): bool
    {
        $name = $request->route()?->getName();

        if ($name === 'password.update') {
            return $request->isMethod('POST');
        }

        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if (in_array($name, [...self::REPORT_ROUTES, 'home', 'change-password', 'logout', 'get_regionals'], true)) {
            return true;
        }

        // Campaign reports only: input and refresh routes deliberately stay excluded.
        foreach (['panenpoin', 'panenpoinv2', 'panenpoinv3', 'panenpoinv4'] as $campaign) {
            foreach (['report', 'report-data', 'report-canvasser', 'report-canvasser-data',
                'report-ph', 'report-ph-data', 'export', 'list-akun', 'akun-data'] as $report) {
                if ($name === "$campaign.$report") {
                    return true;
                }
            }
        }

        return $name === null && $request->is('topup-canvasser/data', 'login');
    }
}
