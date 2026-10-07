-- Rekap revenue Januari-September 2026: per province dan total AREA 3.
-- Jalankan pada database aplikasi melalui tab SQL di phpMyAdmin.
-- Hanya SELECT; tidak mengubah data atau struktur database.
-- Revenue mengikuti SalesAnalysisService: total_settlement_klien,
-- email_client tidak NULL, settlement tidak NULL, bukan Voucher Bonus.
-- Baris terakhir (ROLLUP) adalah total AREA 3.
SELECT
    'AREA 3' AS area,
    COALESCE(mapping.province, 'TOTAL AREA 3') AS province,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 1 THEN tx.revenue ELSE 0 END), 0) AS jan_2026,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 2 THEN tx.revenue ELSE 0 END), 0) AS feb_2026,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 3 THEN tx.revenue ELSE 0 END), 0) AS mar_2026,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 4 THEN tx.revenue ELSE 0 END), 0) AS apr_2026,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 5 THEN tx.revenue ELSE 0 END), 0) AS mei_2026,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 6 THEN tx.revenue ELSE 0 END), 0) AS jun_2026,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 7 THEN tx.revenue ELSE 0 END), 0) AS jul_2026,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 8 THEN tx.revenue ELSE 0 END), 0) AS agu_2026,
    COALESCE(SUM(CASE WHEN MONTH(tx.tgl_transaksi) = 9 THEN tx.revenue ELSE 0 END), 0) AS sep_2026,
    COALESCE(SUM(tx.revenue), 0) AS total_jan_sep_2026
FROM (
    -- DISTINCT mencegah revenue berlipat jika mapping tercatat berulang.
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
        CASE
            WHEN LOWER(TRIM(data_province_name)) IN ('yogyakarta', 'di yogyakarta', 'diy', 'daerah istimewa yogyakarta')
                THEN 'di yogyakarta'
            ELSE LOWER(TRIM(data_province_name))
        END AS province_key,
        CAST(total_settlement_klien AS DECIMAL(18, 2)) AS revenue
    FROM report_balance_top_up
    WHERE tgl_transaksi >= '2026-01-01 00:00:00'
      AND tgl_transaksi < '2026-10-01 00:00:00'
      AND email_client IS NOT NULL
      AND total_settlement_klien IS NOT NULL
      AND payment_method_name <> 'Voucher Bonus'
) AS tx ON tx.province_key = LOWER(mapping.province)
GROUP BY mapping.province WITH ROLLUP;
