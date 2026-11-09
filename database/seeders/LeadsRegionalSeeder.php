<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\LeadsMaster;
use App\Models\User;

class LeadsRegionalSeeder extends Seeder
{
    protected array $userCache = [];
    protected array $sourceCache = [];

    public function run()
    {
        $handle = fopen(storage_path('app/leads_master_provinsi.csv'), 'r');

        // Skip header
        fgetcsv($handle);

        /**
         * ===============================
         * RULE CONFIGURATION
         * ===============================
         */

        $canvasserMap = [
            'special2'  => null,             // BUANG
            'rani'      => 'riva',
            'rino'      => 'akbar',
            'christian' => 'hika',
            '#n/a'      => 'self service',
        ];

        $allowedSources = [
            'Leads OBC',
            'Leads Enterprise',
            'Leads PADI UMKM',
            'Leads B2B',
            'Leads Mandiri',
            'Leads Other',
        ];

        $processedEmails = [];

        /**
         * ===============================
         * CSV LOOP
         * ===============================
         */
        while (($row = fgetcsv($handle)) !== false) {

            [
                $handledBy,
                $kodeVoucher,
                $company,
                $phone,
                $email,
                $countEmail,
                $cek,
                $source,
                $nama,
                $sector,
                $remarks,
                $provinsi
            ] = array_pad($row, 12, null);

            /**
             * ===============================
             * BASIC VALIDATION
             * ===============================
             */

            // BUANG jika canvasser kosong / null
            if (empty($handledBy)) {
                continue;
            }

            // BUANG jika email kosong
            if (empty($email)) {
                continue;
            }

            $email = strtolower(trim($email));

            // Skip duplicate CSV
            if (in_array($email, $processedEmails)) {
                continue;
            }

            $processedEmails[] = $email;

            // Skip jika email sudah ada di mitra_sbp
            $existsInMitra = DB::table('mitra_sbp')
                ->whereRaw('LOWER(email_myads) = ?', [$email])
                ->exists();

            if ($existsInMitra) {
                continue;
            }

            $normalizedPhone = $phone ?: '-';

            /**
             * ===============================
             * CANVASSER RULE
             * ===============================
             */

            $handledByLower = strtolower(trim($handledBy));

            // Buang special2
            if ($handledByLower === 'special2') {
                continue;
            }

            if (array_key_exists($handledByLower, $canvasserMap)) {
                $targetUserName = $canvasserMap[$handledByLower];

                // Kalau mapping = null → BUANG
                if ($targetUserName === null) {
                    continue;
                }

                $userId = $this->getUserIdByName($targetUserName);
            } else {
                $userId = $this->getUserIdByName($handledBy);
            }

            // Jika userId tidak ditemukan → BUANG (safety)
            if (empty($userId)) {
                continue;
            }

            /**
             * ===============================
             * SOURCE RULE
             * ===============================
             */

            $source = trim($source);

            if (!in_array($source, $allowedSources)) {
                $source = 'Daftar Sendiri';
            }

            $sourceId = $this->getSourceIdByName($source);

            /**
             * ===============================
             * INSERT DATA
             * ===============================
             */

            LeadsMaster::create([
                'user_id'      => $userId,
                'source_id'    => $sourceId,
                'sector_id'    => 1,
                'regional'     => $provinsi,
                'kode_voucher' => $kodeVoucher ?: '-',
                'company_name' => $company ?: '-',
                'mobile_phone' => $normalizedPhone,
                'email'        => $email,
                'status'       => str_contains($cek ?? '', 'OK') ? 1 : 0,
                'nama'         => $nama ?: '-',
                'data_type'    => 'Eksisting Akun',
                'remarks'      => $remarks ?: '-'
            ]);
        }

        fclose($handle);
    }


    // ----------------------------------
    // USER (always returns valid ID)
    // ----------------------------------
    private function getUserIdByName(?string $name): int
    {
        if (empty($name)) {
            return 1; // default user
        }

        $name = trim($name);

        if (isset($this->userCache[$name])) {
            return $this->userCache[$name];
        }

        $user = User::firstOrCreate(
            ['name' => $name],
            [
                'email'    => strtolower(str_replace(' ', '.', $name)) . '@gmail.com',
                'password' => Hash::make('123456'),
                'role'     => 'cvsr',
            ]
        );

        return $this->userCache[$name] = $user->id;
    }

    // ----------------------------------
    // SOURCE (always returns valid ID)
    // ----------------------------------
    private function getSourceIdByName(?string $name): int
    {
        if (empty($name)) {
            return 0; // Data Sendiri
        }

        $name = trim($name);

        if (isset($this->sourceCache[$name])) {
            return $this->sourceCache[$name];
        }

        $source = DB::table('leads_source')
            ->where('name', $name)
            ->first();

        return $this->sourceCache[$name] = $source?->id ?? 1;
    }

    // ----------------------------------
    // PHONE NORMALIZER
    // ----------------------------------
    // private function normalizePhone($phone): ?string
    // {
    //     if (empty($phone)) {
    //         return null;
    //     }

    //     return preg_replace('/[^0-9]/', '', $phone);
    // }
}
