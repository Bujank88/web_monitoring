# 🤖 Setup Bot WhatsApp untuk Notifikasi Panen Poin

## 📋 Persyaratan

1. **Node.js** versi 16 atau lebih baru
2. **npm** atau **yarn**
3. **WhatsApp** di HP untuk scan QR code

---

## 🚀 Langkah Setup

### 1️⃣ Install Dependencies

Buka terminal di folder `app/Http/` dan jalankan:

```bash
cd g:\00-KERJA\00-KERJA_TSEL\kodingan\web_monitoring\app\Http

# Install package yang dibutuhkan
npm install express body-parser
```

**Atau** jika `package.json` belum ada, jalankan:

```bash
npm init -y
npm install @whiskeysockets/baileys qrcode-terminal qrcode pino mysql2 nodemailer dotenv express body-parser
```

---

### 2️⃣ Konfigurasi Environment

Tambahkan di file `.env` Laravel (di root project):

```env
# WhatsApp Bot Configuration
WA_BOT_URL=http://localhost:3000
WA_BOT_PORT=3000
```

**Penjelasan:**
- `WA_BOT_URL`: URL dimana bot WA berjalan (untuk Laravel hit API)
- `WA_BOT_PORT`: Port dimana bot WA listen (untuk Node.js)

---

### 3️⃣ Jalankan Bot WhatsApp

```bash
cd g:\00-KERJA\00-KERJA_TSEL\kodingan\web_monitoring\app\Http

node index.js
```

**Output yang diharapkan:**
```
✅ HTTP API Server running on http://localhost:3000
📍 Health check: http://localhost:3000/api/health
📍 Send WA: POST http://localhost:3000/api/send-wa
```

---

### 4️⃣ Scan QR Code

1. Bot akan generate QR code di terminal
2. QR code juga disimpan sebagai file: `qr.png` dan `qr.jpg`
3. Buka WhatsApp di HP → **Linked Devices** → **Link a Device**
4. Scan QR code yang muncul
5. Tunggu sampai muncul: `✅ WA connected`

**⚠️ PENTING:**
- Jangan logout dari WhatsApp Web
- Jangan hapus folder `auth/` (berisi session)
- HP harus tetap online dan terkoneksi internet

---

### 5️⃣ Testing Koneksi

Buka browser atau gunakan Postman:

**Test 1: Health Check**
```
GET http://localhost:3000/api/health
```

**Response:**
```json
{
  "status": "ok",
  "wa_connected": true,
  "timestamp": "2026-01-15T10:30:00.000Z"
}
```

**Test 2: Kirim Pesan Manual**
```
POST http://localhost:3000/api/send-wa
Content-Type: application/json

{
  "phone": "081234567890",
  "nama_akun": "Test User",
  "email": "test@example.com",
  "password": "123456",
  "uuid": "test-uuid-123"
}
```

**Response:**
```json
{
  "success": true,
  "jid": "6281234567890@s.whatsapp.net",
  "message": "Message sent"
}
```

---

### 6️⃣ Testing dari Laravel

Buat route test di `routes/web.php`:

```php
Route::get('/test-wa', function() {
    $controller = new \App\Http\Controllers\PanenPoinController();
    
    // Buat akun dummy untuk testing
    $akun = new \App\Models\AkunPanenPoin();
    $akun->uuid = 'test-uuid-' . time();
    $akun->nama_akun = 'Test Customer';
    $akun->email_client = 'test@example.com';
    
    // Kirim notifikasi ke nomor kamu
    $controller->sendAccountNotification(
        $akun,
        '081234567890', // GANTI DENGAN NOMOR KAMU
        '123456'
    );
    
    return 'Cek WhatsApp kamu!';
});
```

Buka browser: `http://localhost/test-wa`

---

## 🔧 Troubleshooting

### Bot tidak connect / QR tidak muncul
```bash
# Hapus session lama
rm -rf auth/

# Jalankan ulang
node index.js
```

### Error "WhatsApp not connected"
- Pastikan bot sudah jalan dan QR sudah di-scan
- Cek health check: `http://localhost:3000/api/health`
- Pastikan `wa_connected: true`

### Pesan tidak terkirim
- Cek log Laravel: `storage/logs/laravel.log`
- Cek log Node.js di terminal bot
- Pastikan format nomor benar (62xxx atau 08xxx)

### Port sudah digunakan
Ganti port di `.env`:
```env
WA_BOT_PORT=3001
WA_BOT_URL=http://localhost:3001
```

---

## 📱 Menjalankan di Background (Production)

### Menggunakan PM2 (Recommended)

```bash
# Install PM2 global
npm install -g pm2

# Jalankan bot dengan PM2
cd g:\00-KERJA\00-KERJA_TSEL\kodingan\web_monitoring\app\Http
pm2 start index.js --name wa-bot

# Auto-restart saat server reboot
pm2 startup
pm2 save

# Monitoring
pm2 status
pm2 logs wa-bot
pm2 monit
```

### Menggunakan nohup (Alternative)

```bash
nohup node index.js > wa-bot.log 2>&1 &

# Cek process
ps aux | grep node

# Stop
kill <PID>
```

---

## 🔐 Keamanan

1. **Jangan expose port bot ke public** (gunakan firewall)
2. **Tambahkan authentication** jika diakses dari luar localhost
3. **Rate limiting** untuk prevent spam
4. **Backup folder `auth/`** secara berkala

---

## 📊 Monitoring & Logs

**Laravel Logs:**
```bash
tail -f storage/logs/laravel.log | grep WhatsApp
```

**Bot Logs:**
```bash
pm2 logs wa-bot
```

**Check Connection:**
```bash
curl http://localhost:3000/api/health
```

---

## 🎯 Next Steps

1. ✅ Setup bot selesai
2. ✅ Testing kirim pesan manual
3. ✅ Integration dengan Laravel
4. 🔄 Setup PM2 untuk auto-restart
5. 🔄 Setup monitoring & alerts
6. 🔄 Backup session secara berkala

---

## 📞 Support

Jika ada masalah:
1. Cek log Laravel dan Node.js
2. Pastikan bot masih connect (QR tidak expire)
3. Test endpoint health check
4. Restart bot jika perlu

**Status Bot:**
- ✅ Connected: Siap kirim pesan
- ⏳ Connecting: Tunggu scan QR
- ❌ Disconnected: Restart bot dan scan ulang

---

**Good luck! 🚀**
