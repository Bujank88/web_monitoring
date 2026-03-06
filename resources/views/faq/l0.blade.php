@extends('master')
@section('title') FAQ L0 @endsection

@section('css')
<style>
    .faq-hero {
        background: linear-gradient(135deg, #0f766e 0%, #1d4ed8 100%);
        color: #fff;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 18px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .15);
    }

    .faq-hero h3 {
        margin: 0 0 6px 0;
        font-weight: 700;
    }

    .faq-hero p {
        margin: 0;
        opacity: .95;
    }

    .faq-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 12px;
        background: #fff;
    }

    .faq-question {
        width: 100%;
        text-align: left;
        border: 0;
        background: #f8fafc;
        padding: 14px 16px;
        font-weight: 600;
        color: #0f172a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .faq-question:focus {
        outline: 2px solid #60a5fa;
    }

    .faq-answer {
        padding: 14px 16px;
        border-top: 1px solid #e5e7eb;
        color: #1f2937;
        line-height: 1.55;
        background: #ffffff;
    }

    .faq-answer ul {
        margin-bottom: 0;
        padding-left: 18px;
    }

    .faq-answer li {
        margin-bottom: 6px;
    }

    .faq-badge {
        display: inline-block;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 999px;
        background: #dbeafe;
        color: #1d4ed8;
        margin-bottom: 12px;
    }
</style>
@endsection

@section('content')
<div class="faq-hero">
    <h3><i class="fas fa-life-ring mr-2"></i>FAQ L0 Support</h3>
    <p>Panduan jawaban cepat untuk pertanyaan user yang paling sering muncul (Canvasser, PowerHouse).</p>
</div>

<div class="faq-badge">SOP Operasional L0</div>

<div id="faqAccordion">
    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq1" aria-expanded="true">
            1. Kapan campaign direfund?
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq1" class="collapse show" data-parent="#faqAccordion">
            <div class="faq-answer">
                <p>Tanyakan dulu: <strong>kapan campaign dihentikan</strong> dan <strong>jenis campaign</strong>-nya.</p>
                <ul>
                    <li>Distop sebelum tayang: refund saat itu juga.</li>
                    <li>Distop saat sedang tayang: refund jam 02:00 dini hari.</li>
                    <li>Campaign selesai dan jenis MMS (broadcast/LBA/targeted): refund jam 03:00 dini hari berikutnya.</li>
                    <li>Campaign SMS akun DO: refund H+2 dari tanggal campaign selesai (termasuk hari libur).</li>
                    <li>WABA non-MMLite: refund jam 01:30 dini hari, dan setelah 30 hari jam 03:30 dini hari.</li>
                    <li>WABA MMLite: refund H+2.</li>
                    <li>LBA selain MMS: refund jam 02:00 dini hari.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq2">
            2. Status template WABA belum disetujui
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq2" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Template WABA melewati 2 approval: <strong>admin</strong> lalu <strong>Meta</strong>. Cek dulu ke admin apakah sudah approve.
                Jika admin sudah approve tetapi belum approved di Meta, lanjut eskalasi di grup.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq3">
            3. Top up sudah dibayar tapi saldo belum masuk
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq3" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Minta user kirim <strong>ID Invoice</strong>, lalu cek ke tim Finnet. Jika status sudah PAID tapi saldo belum masuk, infokan ke tim L1.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq4">
            4. Campaign masih menunggu tayang
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq4" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Hubungi tim approval campaign agar campaign segera diproses/disetujui.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq5">
            5. Registrasi Telkomsel tidak menerima OTP
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq5" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Minta user klik <strong>kirim ulang OTP</strong>. Jika tetap gagal, eskalasi ke grup WA untuk pengiriman OTP ulang.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq6">
            6. Display Name WABA terbaru ditolak Meta
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq6" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Gunakan nama lain yang sesuai dokumen/data bisnis, atau kembali ke display name sebelumnya yang pernah lolos.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq7">
            7. Tidak bisa bayar via transfer bank
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq7" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Buat VA baru terlebih dahulu. Jika masih gagal, hubungi tim Finnet sambil lampirkan nomor VA.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq8">
            8. Iklan ditolak
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq8" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Ada 2 kemungkinan:
                <ul>
                    <li>Ditolak admin approval.</li>
                    <li>Auto reject sistem (misal lewat jam tayang belum di-approve).</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq9">
            9. Template WABA ditolak
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq9" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Cek aturan Meta berikut:
                <ul>
                    <li>Button phone number maksimal 1.</li>
                    <li>Button URL maksimal 2.</li>
                    <li>Tidak boleh lebih dari 1 enter.</li>
                    <li>Khusus carousel: maksimal 3 baris dan 2 enter per card.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq10">
            10. WABA video tidak bisa diputar
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq10" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Pastikan video: <strong>landscape</strong>, resolusi <strong>1280x720</strong>, durasi maksimal <strong>30 detik</strong>.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq11">
            11. Template WABA carousel tidak bisa submit
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq11" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Syarat submit carousel:
                <ul>
                    <li>Maksimal 10 card.</li>
                    <li>Semua card harus tipe media yang sama (semua gambar atau semua video).</li>
                    <li>Setiap card maksimal 3 baris dan 2 enter.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq12">
            12. Gambar WABA diterima user tapi terpotong
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq12" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Tampilan gambar bisa berbeda di setiap device; pada sebagian perangkat dapat terlihat terpotong.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq13">
            13. Cara stop/cancel campaign
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq13" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                User bisa melakukan stop/cancel campaign secara mandiri dari menu campaign.
            </div>
        </div>
    </div>

    <div class="faq-card">
        <button class="faq-question" data-toggle="collapse" data-target="#faq14">
            14. Saldo terpotong 2x/3x saat membuat campaign
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="faq14" class="collapse" data-parent="#faqAccordion">
            <div class="faq-answer">
                Biasanya karena panjang pesan melewati 160 karakter:
                <ul>
                    <li>&le; 160 karakter: 1x charge</li>
                    <li>161 - 320 karakter: 2x charge</li>
                    <li>&gt; 320 karakter: 3x charge (dst)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
