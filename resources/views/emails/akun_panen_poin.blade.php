<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Panen Poin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
            margin: -30px -30px 20px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
        .credentials {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        .credentials p {
            margin: 10px 0;
        }
        .credentials strong {
            color: #1976D2;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        .emoji {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span class="emoji">🎉</span> Akun Panen Poin Anda Telah Dibuat!</h1>
        </div>
        
        <div class="content">
            <p>Halo <strong>{{ $nama_akun }}</strong>,</p>
            
            <p>Selamat! Akun Panen Poin Anda telah berhasil dibuat. Berikut adalah kredensial login Anda:</p>
            
            <div class="credentials">
                <p><strong>📧 Email:</strong> {{ $email }}</p>
                <p><strong>🔑 Password:</strong> {{ $password }}</p>
                <p><strong>🆔 UUID:</strong> {{ $uuid }}</p>
            </div>
            
            <p>Silakan login menggunakan kredensial di atas untuk mengakses sistem Panen Poin.</p>
            
            <div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <p><strong>🚀 Langkah Selanjutnya:</strong></p>
                <ol>
                    <li>Kunjungi website Panen Poin: <strong><a href="https://panenpoin-myads.com/" style="color: #2196F3; text-decoration: none;">https://panenpoin-myads.com/</a></strong></li>
                    <li>Login menggunakan email dan password di atas</li>
                    <li>Cek saldo poin Anda di dashboard</li>
                    <li>Tingkatkan transaksi untuk menambah poin reward</li>
                    <li>Semakin banyak transaksi = Semakin banyak poin! 💰</li>
                </ol>
            </div>
            
            <div class="warning">
                <p><strong>⚠️ Perhatian Penting:</strong></p>
                <ul>
                    <li>Jangan bagikan password Anda kepada siapapun</li>
                    <li>Disarankan untuk mengganti password setelah login pertama kali</li>
                    <li>Simpan email ini dengan aman</li>
                </ul>
            </div>
            
            <p>Jika Anda memiliki pertanyaan atau mengalami kendala, silakan hubungi tim support kami.</p>
            
            <p>Terima kasih telah menggunakan layanan Panen Poin!</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} MyAds-Telkomsel-Program Panen Poin. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
