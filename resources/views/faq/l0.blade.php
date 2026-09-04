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

    .faq-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .btn-faq-download {
        border: 0;
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        color: #fff;
        padding: 8px 14px;
        border-radius: 10px;
        font-weight: 600;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .2);
    }

    .btn-faq-download:disabled {
        opacity: .7;
        cursor: wait;
    }

    .faq-pdf-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 6px 0;
    }

    .faq-pdf-subtitle {
        font-size: 12px;
        color: #475569;
        margin: 0 0 14px 0;
    }

    @media print {
        .faq-card {
            page-break-inside: avoid;
        }
    }
</style>
@endsection

@section('content')
<div class="faq-hero">
    <h3><i class="fas fa-life-ring mr-2"></i>FAQ L0 Support</h3>
    <p>Panduan jawaban cepat untuk pertanyaan user yang paling sering muncul (Canvasser, PowerHouse).</p>
</div>

<div class="faq-actions">
    <div class="faq-badge">SOP Operasional L0</div>
    <button type="button" id="btnDownloadFaqPdf" class="btn-faq-download">
        <i class="fas fa-file-pdf mr-2"></i>Download PDF
    </button>
</div>

<div id="faqContent">
    <div class="faq-pdf-title">FAQ L0 Support</div>
    <div class="faq-pdf-subtitle">Dokumen panduan jawaban cepat untuk pertanyaan user yang sering muncul.</div>

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
</div>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    (function () {
        const btn = document.getElementById('btnDownloadFaqPdf');
        const faqContent = document.getElementById('faqContent');

        if (!btn || !faqContent) return;

        btn.addEventListener('click', async function () {
            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';

            const collapses = Array.from(document.querySelectorAll('#faqAccordion .collapse'));
            const wasOpen = collapses.map(el => el.classList.contains('show'));
            collapses.forEach(el => {
                el.classList.add('show');
            });

            try {
                const prevWidth = faqContent.style.width;
                const prevMaxWidth = faqContent.style.maxWidth;
                const prevMargin = faqContent.style.margin;
                const prevTransform = faqContent.style.transform;
                const prevTransformOrigin = faqContent.style.transformOrigin;
                faqContent.style.width = '100%';
                faqContent.style.maxWidth = '100%';
                faqContent.style.margin = '0';
                faqContent.style.transform = 'scale(0.9)';
                faqContent.style.transformOrigin = 'top left';

                const opt = {
                    margin: [10, 12, 10, 12],
                    filename: 'FAQ_L0_Support.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak: { mode: ['avoid-all', 'css'] }
                };

                await html2pdf().set(opt).from(faqContent).save();

                faqContent.style.width = prevWidth;
                faqContent.style.maxWidth = prevMaxWidth;
                faqContent.style.margin = prevMargin;
                faqContent.style.transform = prevTransform;
                faqContent.style.transformOrigin = prevTransformOrigin;
            } finally {
                collapses.forEach((el, idx) => {
                    if (!wasOpen[idx]) {
                        el.classList.remove('show');
                    }
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    })();
</script>
@endsection
