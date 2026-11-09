# Tracers - Web Monitoring Telkomsel

Aplikasi web monitoring untuk berbagai program dan kampanye digital Telkomsel. Dibangun menggunakan Laravel 12 dengan AdminLTE untuk dashboard administration.

## 📋 Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Tech Stack](#tech-stack)
- [Instalasi](#instalasi)
- [Konfigurasi Database](#konfigurasi-database)
- [User Roles](#user-roles)
- [Modul Monitoring](#modul-monitoring)
- [API Endpoints](#api-endpoints)
- [PostgreSQL External Connection](#postgresql-external-connection)

## 🚀 Fitur Utama

- **Multi-role Access Control**: Admin, Tsel, Treg
- **Real-time Monitoring Dashboard**: Monitoring berbagai program MyAds dan kampanye digital
- **Data Export & Import**: Upload CSV, download reports
- **Voucher Management System**: Kelola voucher dan tracking klaim user
- **External Database Integration**: Koneksi ke PostgreSQL eksternal
- **DataTables Integration**: Tabel data interaktif dengan filter dan pencarian

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP**: ^8.2
- **Database**: MySQL/MariaDB (Primary), PostgreSQL (External)
- **Admin Panel**: AdminLTE 3.15

### Frontend
- **CSS Framework**: TailwindCSS 4.0
- **Build Tool**: Vite 7.x
- **JavaScript**: Axios for AJAX requests
- **Datatables**: Yajra Laravel Datatables

### Additional Libraries
- **PHPSpreadsheet**: Excel/CSV processing
- **Carbon**: Date manipulation
- **Laravel Breeze**: Authentication scaffolding

## 📥 Instalasi

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL/MariaDB
- Git

### Langkah-langkah Instalasi

1. **Clone Repository**
```bash
git clone <repository-url>
cd web_monitoring
```

2. **Install Dependencies**
```bash
# Install PHP dependencies
composer install

# Install NPM dependencies
npm install
```

3. **Setup Environment**
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

4. **Konfigurasi Database**
Edit file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=username
DB_PASSWORD=password
```

5. **Run Migrations**
```bash
php artisan migrate
```

6. **Build Assets**
```bash
# Development
npm run dev

# Production
npm run build
```

7. **Start Development Server**
```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

## 🗄️ Konfigurasi Database

### Primary Database (MySQL/MariaDB)
Database utama untuk menyimpan data aplikasi. Konfigurasi di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### External PostgreSQL Database
Untuk koneksi ke PostgreSQL eksternal, tambahkan di `.env`:
```env
PGSQL_EXT_HOST=your_postgres_host
PGSQL_EXT_PORT=your_postgres_port
PGSQL_EXT_DATABASE=your_database_name
PGSQL_EXT_USERNAME=your_username
PGSQL_EXT_PASSWORD=your_password
```

**Note**: Hubungi administrator untuk mendapatkan kredensial PostgreSQL yang valid.

Lihat dokumentasi lengkap di `POSTGRESQL_GUIDE.md`

### Tables Structure

**Main Tables:**
- `users` - User management (Admin, Tsel, Treg)
- `treg` - Regional data
- `myads_voucher` - Voucher management
- `myads_user` - User yang klaim voucher

**Campaign Tables:**
- `padi_umkm` & `summary_padi_umkm`
- `simpati_tiktok` & `summary_simpati_tiktok`
- `creator_partner` & `rekruter_kol`
- `revenue` & `revenue_kol`
- `event_sponsorship`
- `referral_champion_am`
- `sultam_racing`
- `rahasia_bisnis`
- `manajemen_user_register`

## 👥 User Roles

### 1. Admin
- Full access ke semua fitur
- User management
- Upload data MyAds
- Monitoring semua program
- Voucher management

### 2. Tsel (Telkomsel Staff)
- Monitoring program
- View reports
- Export data
- Limited management access

### 3. Treg (Regional)
- View regional summary
- Upload voucher CSV
- Monitoring akuisisi regional
- Download format voucher

## 📊 Modul Monitoring

### 1. Padi UMKM
- Monitoring pendaftaran UMKM
- Tracking top-up per user
- Summary bulanan
- Export data

**Route:** `/monitoring-padi-umkm`

### 2. Event Sponsorship
- Data event sponsorship
- Tracking participants

**Route:** `/monitoring-event-sponsorship`

### 3. Creator Partner
- Monitoring KOL (Key Opinion Leader)
- Jenis: Buzzer & Seller Online/Affiliate
- Tracking rekruter per creator
- Tier calculation based on revenue
- Area & regional filtering

**Route:** `/monitoring-creator-partner`

### 4. Rekruter KOL
- **Buzzer**: Min topup Rp 250.000
- **Influencer**: Min topup Rp 1.000.000
- Eligibility tracking
- Revenue calculation

**Routes:**
- `/monitoring-rekruter-kol-buzzer`
- `/monitoring-rekruter-kol-influencer`

### 5. Area Marcom
- Statistics per area
- Star rating system
- KOL performance tracking

**Route:** `/monitoring-kol-area-marcom`

### 6. Simpati TikTok
- User registration tracking
- Top-up monitoring
- Summary by email

**Route:** `/monitoring-simpati-tiktok`

### 7. Referral Champion
- AM (Account Manager)
- Tele AM
- Canvasser

**Routes:**
- `/monitoring-referral-champion-am`
- `/monitoring-referral-champion-tele-am`
- `/monitoring-referral-champion-canvasser`

### 8. Sultam Racing
- Racing program monitoring

**Route:** `/monitoring-sultam-racing`

### 9. Voucher Management
- Generate & manage vouchers
- Track claimed vouchers
- User data management
- Export claimed vouchers

**Routes:**
- `/monitor-voucher` - Voucher list
- `/monitor-claim-voucher` - Claimed vouchers

## 🔌 API Endpoints

### Authentication
```
POST /login
POST /register-simpan
GET  /logout
```

### Data Retrieval (Public)
```
GET /get-data-rahasia-bisnis-kuesioner
GET /get-data-padi-umkm
GET /get-data-creator-partner
GET /get-data-simpati-tiktok
GET /get-data-referral-champion-am
GET /get-data-sultam-racing
GET /get-data-event-sponsorship
GET /get-data-rekruter-kol
```

### Summary Refresh
```
GET /summary-padi-umkm
GET /summary-tiktok
```

### Admin Routes (Auth Required)
```
GET  /admin/home
GET  /upload-file-myads
POST /store-csv-myads
GET  /download-format/{table}

# Padi UMKM
GET /get-padi-umkm-data
GET /padi-umkm/summary

# Creator Partner
GET /get-creator-partner-data
GET /get-regionals

# Voucher Management
GET    /vouchers/data
POST   /vouchers/tambah
POST   /vouchers/update/{id}
DELETE /vouchers/hapus/{id}
GET    /vouchers/stats
GET    /vouchers/claimed/data
POST   /vouchers/update-user/{user_id}
DELETE /vouchers/unclaim/{voucher_id}
GET    /vouchers/download-claimed-voucher

# User Management
GET  /users-data
POST /users-store
GET  /users-edit/{id}
POST /users-update/{id}
POST /users-delete/{id}
```

### Treg Routes (Auth Required)
```
GET  /monitoring-akuisisi-treg
GET  /race-summary-treg
GET  /get-akuisisi-data
GET  /get-treg-summary-data
POST /upload-voucher-csv
GET  /download-format-voucher-treg
```

## 🔗 PostgreSQL External Connection

Aplikasi ini mendukung koneksi ke PostgreSQL eksternal untuk integrasi data.

### Penggunaan di Controller

```php
use Illuminate\Support\Facades\DB;

// Select data
$data = DB::connection('pgsql_external')
    ->table('nama_tabel')
    ->get();

// Insert data
DB::connection('pgsql_external')
    ->table('nama_tabel')
    ->insert(['kolom' => 'nilai']);

// Raw query
$results = DB::connection('pgsql_external')
    ->select('SELECT * FROM table WHERE condition = ?', [$value]);
```

Lihat dokumentasi lengkap di:
- `POSTGRESQL_GUIDE.md` - Panduan lengkap koneksi PostgreSQL
- `POSTGRES_QUICK_REFERENCE.md` - Quick reference
- `INSTALL_POSTGRES_EXTENSION.md` - Setup PostgreSQL extension

## 📝 Default Credentials

Setelah instalasi, gunakan kredensial berikut untuk login:
- **Email**: harus menggunakan domain `@telkomsel.co.id`
- **Password**: Default `123456` (ubah setelah login pertama)

## 🔒 Security Notes

- Hanya email dengan domain `@telkomsel.co.id` yang bisa register/login
- Password di-hash menggunakan bcrypt
- Session-based authentication
- Role-based access control (RBAC)
- CSRF protection enabled

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

**Developed for Telkomsel Internal Use**
