# Input Ticketing

Menu **Input Ticketing** tersedia untuk Admin di `/ticketing/input`, dan **List Ticketing** di `/ticketing`. Model `Ticket`, controller `TicketController`, request validasi, view pada folder `ticketing`, tabel `tickets`, dan importer terpisah dari modul lain.

Menu **Report Ticketing** tersedia untuk Admin di `/ticketing/report`. Controller `TicketReportController`, service `TicketReportService`, dan view pada `ticketing/report/` terpisah dari input/list. Report dihitung langsung dari tabel tickets dan mengikuti sheet `PVT Channel`, `PVT Complaint Type`, serta `Sheet1` pada `Complaint Handling.xlsx`. Tidak ada impor ulang atau perubahan data tiket saat membuka report.

Report menyediakan:

- PVT Channel: jumlah tiket per status/channel, rata-rata durasi penyelesaian per channel, serta kategori penanganan per channel; kolom Week 1–4 dan Grand Total.
- PVT Complaint Type: jumlah tiket per complaint/status, rata-rata penyelesaian per complaint, dan kategori penanganan per complaint; kolom Week 1–4 dan Grand Total.
- Ringkasan bulanan: jumlah per kategori penanganan, rata-rata penyelesaian per kategori, jumlah status, dan persentase status. Bulan detail terpilih diperluas menjadi empat minggu dan subtotal bulan, mengikuti Sheet1. Grand Total tetap menghitung tiket satu kali.

Filter tahun berlaku untuk seluruh report. Kelima kartu ringkasan (total, Closed, sedang berlangsung, resolution rate, dan rata-rata penyelesaian) serta kedua pivot mingguan mengikuti bulan terpilih dan rentang tanggal pengajuan. Bulan juga menentukan perluasan kolom ringkasan bulanan. Tabel ringkasan bulanan dan tren tetap menampilkan konteks tahunan dengan batas rentang tanggal yang sama. Periode awal memakai tahun/bulan tiket terbaru, atau tanggal aplikasi jika belum ada data. Jika tidak ada tiket pada bulan yang dipilih, kartu jumlah menampilkan 0 dan kartu persentase/rata-rata menampilkan tanda kosong.

Pembagian minggu sama dengan Excel: tanggal 1–7, 8–14, 15–21, dan 22–akhir bulan. Durasi dihitung dalam menit dari waktu pengajuan ke waktu penyelesaian, hanya untuk status Closed. Kategori Cepat ≤15 menit; Sedang >15–60 menit; Lambat >60–1.440 menit; Lebih dari 1 Hari >1.440 menit. Status selain Closed menjadi Sedang Berlangsung. Waktu selesai kosong atau durasi negatif menjadi Periksa Waktu; durasi negatif tetap masuk rata-rata seperti Excel, sedangkan waktu kosong tidak masuk. Data bermasalah diberi pemberitahuan pada halaman.

Rata-rata Grand Total memakai seluruh durasi numerik, bukan rata-rata dari rata-rata mingguan. Persentase status memakai jumlah status dibagi seluruh tiket periode tersebut. Ini memperbaiki rumus resolution rate Grand Total dan persentase Open Februari yang keliru pada Sheet1. Dua tiket tepat 15 menit dikategorikan Cepat; Excel menempatkannya sebagai Sedang akibat selisih floating-point pada serial tanggal.

Validasi terhadap workbook: 72 sel agregat jumlah/rata-rata cocok dengan data sumber (toleransi waktu 0,03 menit). Tahun 2026 berisi 901 tiket, September 82 tiket; Closed tahunan 674 dan resolution rate 74,81%. Perhitungan tersebut juga diverifikasi secara read-only terhadap database VPS. Angka akan mengikuti perubahan tiket selanjutnya. Fitur report tidak memerlukan migration baru.

Daftar menampilkan 25 tiket per halaman dengan pencarian nomor tiket, PIC, akun/campaign, atau diagnosis, serta filter status dan rentang tanggal pengajuan. Tanggal awal mulai pukul 00:00:00 dan tanggal akhir mencakup seluruh hari tersebut. Salah satu batas tanggal boleh dikosongkan. Filter tetap terbawa saat berpindah halaman. Status historis kosong dinormalisasi menjadi Open melalui migration data; impor berikutnya juga memakai Open untuk status kosong.

Detail diagnosis, bukti, dan penanganan bisa dibuka pada setiap baris. Aksi **Closed Ticket** hanya tersedia untuk tiket Open. Admin wajib mengisi hasil penanganan serta tanggal dan jam Closed pada dialog. Input waktu diawali dengan waktu aplikasi saat halaman dimuat dan dapat diubah. Server mengubah status menjadi Closed dan menyimpan waktu yang dipilih ke `resolved_at`; waktu tidak boleh sebelum pengajuan atau melewati saat ini. Catatan penanganan lama dipertahankan dan catatan penutupan memakai waktu terpilih. Pemeriksaan status dan waktu serta pembaruan berada dalam transaksi dengan penguncian baris agar penutupan bersamaan atau berulang tidak menimpa hasil pertama. Tiket dengan waktu pengajuan di masa depan belum dapat ditutup. Report menghitung durasi dari waktu Closed tersebut. Perubahan ini tidak membutuhkan migration baru.

Admin dapat **Edit** tiket menggunakan form yang sama dengan input, dengan nomor tiket dan pembuat tetap dipertahankan. PIC historis tetap digunakan jika dropdown tidak diganti. Validasi Closed berlaku juga saat edit. Aksi **Delete** meminta konfirmasi dan menghapus tiket secara permanen; counter nomor tidak dikurangi atau dipakai kembali. Edit dan delete memeriksa versi data di dalam transaksi sehingga halaman yang sudah usang tidak menimpa atau menghapus perubahan terbaru. Isi Excel asli di `import_data` tidak diubah oleh edit.

Form mengikuti Complaint Handling: PIC, waktu pengajuan, jenis permintaan, kategori keluhan, channel, metode, akun/invoice/campaign, diagnosis, referensi bukti, update penanganan, status, waktu penyelesaian, prioritas, dan level penanganan. Channel/metode opsional untuk keluhan yang tidak terkait campaign. Nomor tiket otomatis berurutan dengan format `T0000000001`. Tiket Closed membutuhkan hasil penanganan dan waktu penyelesaian yang tidak lebih awal dari waktu pengajuan. Tanggal mengikuti zona waktu aplikasi dan timestamp Excel dipertahankan sebagai waktu lokal.

PIC pengajuan dipilih dari users Tracers, dikelompokkan berdasarkan role dan bisa dicari berdasarkan nama, email, atau role. Nilai pilihan memakai ID user agar pengguna dengan nama sama tetap dapat dibedakan; backend memvalidasi ID tersebut dan menyimpan nama dari database pada `user_name`. Nama PIC menjadi snapshot saat pengajuan, sedangkan `created_by` tetap pengguna Admin yang menyimpan tiket. Perubahan dropdown tidak membutuhkan migration tambahan.

## Migration dan impor

Jalankan hanya migration modul ini agar migration lama yang belum dijalankan tidak ikut mengubah database:

```powershell
php artisan migrate --path=database/migrations/2026_10_05_000000_create_tickets_table.php --force
php artisan migrate --path=database/migrations/2026_10_05_000001_set_unrecorded_ticket_status_to_open.php --force
php artisan ticketing:import "C:\Users\KAM PC\Downloads\Complaint Handling - Copy.xlsx" --dry-run
php artisan ticketing:import "C:\Users\KAM PC\Downloads\Complaint Handling - Copy.xlsx"
```

Importer membaca sheet `ROW`, memeriksa header, mengecualikan contoh dan baris nomor tiket kosong, serta mempertahankan data historis yang tidak lengkap. Nomor duplikat diberi akhiran `-2`, `-3`, dan seterusnya; nomor sumber tetap tersimpan. Isi sumber A–R dan nomor baris disimpan di `import_data`, termasuk nilai formula lama. Kolom D, P, dan Q adalah nilai durasi turunan pada workbook sehingga tidak menjadi input manual. Kolom R disimpan sebagai level penanganan.

Impor bersifat transaksional: kesalahan tanggal atau konflik dengan tiket lain membatalkan seluruh impor. Mengimpor ulang workbook yang sama melewati baris yang sudah masuk dan tidak mengubah tiket lama. Hash sumber menyertakan posisi baris dan isi; workbook yang telah diedit atau diurutkan ulang perlu ditinjau sebelum diimpor lagi karena dapat memicu konflik nomor. Counter tiket manual dilanjutkan setelah nomor terbesar yang diimpor. File sumber dan data complaint tidak disalin ke repository.

## Pengujian

```powershell
php artisan test --filter=TicketingTest
```

Pengujian memakai SQLite memory dan hanya schema yang dibutuhkan modul, tanpa mengubah database aplikasi.

## Penerapan kode di VPS

Pasang file modul di `app/Models/Ticket.php`, `app/Http/Controllers/TicketController.php`, `app/Http/Requests/StoreTicketRequest.php`, `app/Http/Requests/UpdateTicketRequest.php`, `app/Http/Requests/CloseTicketRequest.php`, `app/Services/TicketExcelImporter.php`, `app/Console/Commands/ImportTickets.php`, `resources/views/ticketing/create.blade.php`, `resources/views/ticketing/index.blade.php`, dan kedua migration ticketing. Terapkan juga perubahan route pada `routes/web.php` dan menu pada `resources/views/sidebar.blade.php`. Jalankan `php artisan optimize:clear` di VPS setelah penerapan. Dependensi menggunakan composer.lock yang sudah ada; tidak ada paket baru. Migration normalisasi status juga sudah dijalankan langsung pada VPS dalam sesi ini.

Untuk menu report, pasang juga `app/Http/Controllers/TicketReportController.php`, `app/Services/TicketReportService.php`, `resources/views/ticketing/report/index.blade.php`, dan `resources/views/ticketing/report/pivot.blade.php` beserta perubahan route/menu tersebut.

Pada sesi implementasi ini migration telah dijalankan dan **1.027 tiket berhasil diimpor langsung ke database VPS**. Nomor manual berikutnya dimulai dari `T0000001087`. Aplikasi VPS membutuhkan penerapan file kode di atas agar menu baru tampil.

## File SQL phpMyAdmin

File SQL disiapkan di `storage/app/private/ticketing/`, di luar berkas source yang dilacak Git:

- `complaint-handling-data.sql`: data 1.027 tiket untuk database yang sudah memiliki tabel ticketing. Data ini sudah masuk ke VPS; tidak perlu diimpor kembali. File diuji dengan impor ulang dan tidak menambah baris ganda. Konflik nomor yang sudah ada tidak ditimpa.
- `ticketing-schema.sql`: pembuatan tabel dan pencatatan migration, untuk pemasangan pertama di database lain.
- `ticketing-phpmyadmin.sql`: gabungan schema dan data, untuk pemasangan pertama di database lain. Jangan jalankan file gabungan pada VPS saat ini karena tabelnya sudah dibuat.

Untuk pemasangan pertama melalui phpMyAdmin, pilih database aplikasi lalu Import file gabungan. Data SQL telah diuji pada MariaDB lokal terpisah. File SQL berisi data complaint asli; simpan bersama berkas privat aplikasi.
