-- Jumlah klien unik yang topup Januari-September 2026, khusus AREA 3.
-- Jalankan melalui tab SQL phpMyAdmin pada database aplikasi; hanya SELECT.
-- Identitas klien: LOWER(TRIM(email_client)); email kosong tidak dihitung.
-- Filter transaksi mengikuti laporan revenue: settlement tidak NULL,
-- bukan Voucher Bonus. Mapping Yogyakarta/DI Yogyakarta disamakan.
-- Kolom bulanan menghitung klien unik dalam bulan tersebut.
-- total_klien_unik_jan_sep_2026 menghitung klien unik sepanjang periode,
-- bukan menjumlahkan kolom bulanan.
-- TOTAL AREA 3 menghitung klien sekali walaupun topup di beberapa province;
-- hasilnya dapat lebih kecil daripada penjumlahan jumlah klien per province.
SELECT
    'AREA 3' AS area,
    COALESCE(mapping.province, 'TOTAL AREA 3') AS province,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 1 THEN tx.email_key END) AS jan_2026,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 2 THEN tx.email_key END) AS feb_2026,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 3 THEN tx.email_key END) AS mar_2026,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 4 THEN tx.email_key END) AS apr_2026,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 5 THEN tx.email_key END) AS mei_2026,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 6 THEN tx.email_key END) AS jun_2026,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 7 THEN tx.email_key END) AS jul_2026,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 8 THEN tx.email_key END) AS agu_2026,
    COUNT(DISTINCT CASE WHEN MONTH(tx.tgl_transaksi) = 9 THEN tx.email_key END) AS sep_2026,
    COUNT(DISTINCT tx.email_key) AS total_klien_unik_jan_sep_2026
FROM (
    SELECT DISTINCT
        CASE
            WHEN LOWER(TRIM(province)) IN ('yogyakarta', 'di yogyakarta', 'diy', 'daerah istimewa yogyakarta')
                THEN 'DI Yogyakarta'
            ELSE TRIM(province)
        END AS province
    FROM regional_provinces
    WHERE UPPER(TRIM(area)) = 'AREA 3'
      AND province IS NOT NULL AND TRIM(province) <> ''
) AS mapping
LEFT JOIN (
    SELECT
        tgl_transaksi,
        LOWER(TRIM(email_client)) AS email_key,
        CASE
            WHEN LOWER(TRIM(data_province_name)) IN ('yogyakarta', 'di yogyakarta', 'diy', 'daerah istimewa yogyakarta')
                THEN 'di yogyakarta'
            ELSE LOWER(TRIM(data_province_name))
        END AS province_key
    FROM report_balance_top_up
    WHERE tgl_transaksi >= '2026-01-01 00:00:00'
      AND tgl_transaksi < '2026-10-01 00:00:00'
      AND email_client IS NOT NULL
      AND TRIM(email_client) <> ''
      AND total_settlement_klien IS NOT NULL
      AND payment_method_name <> 'Voucher Bonus'
) AS tx ON tx.province_key = LOWER(mapping.province)
GROUP BY mapping.province WITH ROLLUP;
