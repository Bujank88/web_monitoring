# Spectrum Leads

Menu Input Leads dan Data Leads hanya untuk Admin. Controller Spectrum,
model LeadSpectrum, importer, dan view berada terpisah dari modul lain.

Upload Excel `.xlsx` atau `.xls` melalui Input Leads. Header baris pertama
mengikuti file Master Leads Digiads Daily 2026.xlsx: Provider, Last Name,
Company / Account, Mobile Phone, email, Company Size, product, Lead Source,
Create Date, Share Date, Description, Pillar, Sektor Industri Company, FU,
Respon. Urutan kolom bebas; kapitalisasi dan spasi header dinormalisasi.
Kolom tambahan UTM Source (`utm_source`) bersifat opsional, string maksimal
255 karakter. Kolom ini tampil di Data Leads dan dapat diubah melalui Edit.
File Excel lama tanpa UTM Source tetap diterima. UTM Source kosong memakai
fingerprint lama agar upload ulang data lama tetap terdeteksi sebagai duplikat.
Kolom tanpa header diabaikan. Gunakan satu sheet dengan header lengkap.

Tanggal Excel disimpan sebagai tanggal, termasuk kalender Excel 1904.
Tanggal teks menerima YYYY-MM-DD atau M/D/YYYY sesuai contoh workbook.
Nomor telepon disimpan sebagai string; gunakan sel teks di Excel untuk
mempertahankan nol awal. Kolom boleh kosong. Formula/error Excel ditolak.

Maksimal 10 MB / 10.000 baris. Semua baris divalidasi sebelum transaksi
database dimulai; kegagalan membatalkan seluruh upload. Baris kosong dilewati.
Hash seluruh 15 kolom yang sudah dinormalisasi mencegah duplikat identik,
termasuk upload ulang. Email sama dengan kolom lain berbeda tetap merupakan
lead berbeda. Upload tidak memperbarui atau menghapus baris sebelumnya.

Hasil tersimpan di `leads_spectrum` beserta nama file dan ID pengunggah,
kemudian langsung terlihat di Data Leads. Daftar memiliki pencarian
nama/company/email/telepon dan pagination 25 baris.

Data Leads menyediakan Action Edit dan Delete untuk Admin. Edit mencakup
seluruh 16 kolom, dengan validasi tanggal YYYY-MM-DD dan batas panjang sesuai
kolom database. Delete meminta konfirmasi sebelum menghapus permanen.
Fingerprint impor asli dipertahankan setelah edit sehingga upload ulang file
asal tetap dilewati sebagai duplikat dan perubahan manual tetap tersimpan.
Lead yang dihapus dapat ditambahkan kembali melalui upload.

Migration khusus modul ini:

```sh
php artisan migrate --path=database/migrations/2026_10_06_000001_create_leads_spectrum_table.php --force
php artisan migrate --path=database/migrations/2026_10_27_000001_add_utm_source_to_leads_spectrum_table.php --force
```

Pengujian terisolasi SQLite:

```sh
php artisan test --filter=SpectrumLeadsTest
```
