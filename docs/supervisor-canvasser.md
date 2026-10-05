# Supervisor canvasser

Role `Supervisor` memiliki dashboard baca-saja di `/supervisor`. Semua query leads,
pencarian, filter, ringkasan, dan pagination dibatasi melalui `users.supervisor_id`
dan role anggota `cvsr`. Supervisor tanpa anggota melihat halaman kosong.
Role ini tidak memperoleh akses ke endpoint edit leads. Daily Top Up Channel,
Report Canvasser, dan Program Campaign menampilkan laporan global sesuai permintaan.

## Pemasangan

1. Jalankan migrasi: `php artisan migrate` (atau gunakan `--path=database/migrations/2026_10_05_000000_add_supervisor_id_to_users_table.php` untuk migrasi ini saja).
2. Jalankan `php artisan canvassers:import-interns`. Perintah berisi kelima nama,
   telepon, dan email yang diberikan. Password awal akun baru adalah `123456`
   sesuai permintaan, disimpan sebagai hash. Akun yang sudah ada tidak diubah;
   konflik role membatalkan impor.
3. Admin membuat akun PIC dari menu User dengan role **Supervisor**.
4. Admin membuka **Tim Canvasser** (`/supervisor/team`), memilih PIC untuk setiap
   anggota, lalu menyimpan. Anggota dapat dipindahkan atau dilepas dari PIC.
5. PIC login dan otomatis diarahkan ke **Monitoring Tim**.

Dashboard menampilkan jumlah leads, eksisting akun, total per anggota, kontak,
rencana top up, catatan, dan tanggal pembuatan. Filter tersedia untuk anggota,
periode tanggal pembuatan, serta perusahaan/email. Rencana top up bukan realisasi
transaksi. Akun canvasser nonaktif tetap tercantum agar histori bisa dipantau.

Impor akun tidak otomatis menetapkan PIC karena akun PIC akan dibuat oleh admin.
Sampaikan kredensial awal secara pribadi; pengguna dapat menggantinya melalui
fitur perubahan password yang sudah tersedia.

## Menu Supervisor

- Monitoring Tim dan Data Leads & Akun: data leads anggota binaan, tanpa input/edit leads.
- Daily Top Up Channel: halaman, data, dan ekspor laporan channel global yang sudah ada.
- Report Canvasser: laporan canvasser global yang sudah ada, termasuk halaman detail.
- Tips Sales (termasuk unduhan panduan) dan FAQ L0: materi umum.
- Logbook Monthly / Daily: monitoring data anggota, tanpa edit/realisasi atas nama anggota.
- Program Campaign: Panen Poin V3/V4 (Report Poin, Report Canvasser, List Akun,
  ekspor laporan poin) dan Program Referral Champion. Perhitungan poin memakai
  controller program yang sudah ada; query dan ekspor bersifat global.
- Pengajuan SOF: Supervisor memilih anggota aktif untuk mengajukan SOF atas namanya.
- List SOF: hanya pengajuan anggota binaan, dengan filter bulan dan canvasser.
- Change Password dan Logout.

Laporan global memakai perhitungan dan periode laporan yang sudah ada. SOF lama hanya menyimpan nama PIC: nama yang dipakai lebih dari satu akun
tidak ditampilkan/tidak bisa dipilih untuk mencegah kebocoran data antar tim.
Jika ada nama duplikat, admin perlu membedakan nama akun terkait.

Validasi: `php artisan test --filter='SupervisorAccessTest|SupervisorReportTest|PanenPoinV4Test|AmLeaderAccessTest'`.
