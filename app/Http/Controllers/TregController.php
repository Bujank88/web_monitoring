<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;
use Carbon\Carbon;


class TregController extends Controller
{
    public function getTregSummaryData(Request $request)
    {
        // Ambil semua TREG dengan join ke voucher_treg untuk mendapatkan summary
        $data = DB::table('treg as t')
            ->leftJoin('voucher_treg as vt', 't.voucher_code', '=', 'vt.voucher_code')
            ->select(
                't.id',
                't.treg_name',
                't.voucher_code',
                DB::raw('COUNT(CASE WHEN vt.status = "PAID" THEN 1 END) as jumlah_akuisisi'),
                DB::raw('COALESCE(SUM(CASE WHEN vt.status = "PAID" THEN vt.total_amount END), 0) as total_topup')
            )
            ->groupBy('t.id', 't.treg_name', 't.voucher_code')
            ->orderBy('t.treg_name')
            ->get();

        return DataTables::of($data)
            ->addColumn('tier_remarks', function($row) {
                $jumlah = (int) $row->jumlah_akuisisi;
                $totalTopup = (float) $row->total_topup;
                
                if ($jumlah >= 1 && $jumlah <= 2) {
                    return 'BLACK';
                } elseif ($jumlah >= 3 && $jumlah <= 9) {
                    return 'BRONZE';
                } elseif ($jumlah >= 10 && $jumlah <= 15) {
                    return 'SILVER';
                } elseif ($jumlah >= 16 && $jumlah <= 25) {
                    return 'GOLD';
                } elseif ($jumlah >= 26 && $jumlah <= 50) {
                    return 'PLATINUM';
                } elseif ($jumlah > 50) {
                    return 'DIAMOND';
                } else {
                    return 'NO TIER';
                }
            })
            ->make(true);
    }

    public function getDetailAkuisisi(Request $request)
    {
        $data = DB::table('voucher_treg as vt')
            ->join('treg as t', 'vt.voucher_code', '=', 't.voucher_code')
            ->select(
                'vt.tanggal_penukaran',
                'vt.voucher_code',
                'vt.client_email',
                'vt.total_amount'
            )
            ->where('vt.status', 'PAID') // ← FIXED
            ->orderByDesc('vt.tanggal_penukaran')
            ->get();

        return DataTables::of($data)->make(true);
    }

    private function toUtf8($value)
    {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
    public function uploadCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        // Buka file CSV
        $handle = fopen($path, "r");
        $header = true;
        $insertData = [];

        while (($row = fgetcsv($handle, 10000, ",")) !== false) {
            if ($header) {
                $header = false;
                continue;
            }

            foreach ($row as $k => $v) {
                $row[$k] = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
            }

            $insertData[] = [
                'tanggal_mulai_aktif' => Carbon::parse($row[0])->format('Y-m-d H:i:s'),
                'tanggal_penukaran'   => Carbon::parse($row[1])->format('Y-m-d H:i:s'),
                'voucher_id'          => $row[2],
                'voucher_code'        => $row[3],
                'voucher_category'    => $row[4],
                'client_id'           => $row[5],
                'client_name'         => $row[6],
                'client_email'        => $row[7],
                'transaction_id'      => $row[8],
                'invoice_number'      => $row[9],
                'amount'              => (float)$row[10],
                'discount'            => (float)$row[11],
                'total_amount'        => (float)$row[12],
                'payment_method'      => $row[13],
                'status'              => $row[14],
                'created_at'          => now(),
            ];
        }

        fclose($handle);

        if (!empty($insertData)) {
            DB::table('voucher_treg')->insert($insertData);
        }

        return response()->json([
            'message' => 'Data voucher berhasil diupload.'
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function downloadFormatVoucherTreg()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=format_voucher_treg.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            "TANGGAL_MULAI_AKTIF",
            "TANGGAL_PENUKARAN",
            "VOUCHER_ID",
            "VOUCHER_CODE",
            "VOUCHER_CATEGORY",
            "CLIENT_ID",
            "CLIENT_NAME",
            "CLIENT_EMAIL",
            "TRANSACTION_ID",
            "INVOICE_NUMBER",
            "AMOUNT",
            "DISCOUNT",
            "TOTAL_AMOUNT",
            "PAYMENT_METHOD",
            "STATUS"
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // contoh baris dummy (boleh hapus kalau tidak perlu)
            fputcsv($file, [
                "Fri Oct 31 10:41:46 WIB 2025",
                "Fri Oct 31 10:41:46 WIB 2025",
                "VCR-2771",
                "KRZBOGWKQP",
                "SPECIFIC",
                "REG-DO-000000656541",
                "MyAds Account",
                "vanessaimarcella@gmail.com",
                "INV-200128639",
                "TSEL/1478365/DO/41/2025",
                "0",
                "0",
                "250000",
                "Voucher Bonus",
                "PAID"
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
