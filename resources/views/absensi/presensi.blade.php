@extends('master')
@section('title') Presensi CVSR @endsection
@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.css">
<style>
    .content-wrapper {
        min-height: 80vh;
        display: center;
        align-items: center;
        justify-content: center;
        margin-left: -30px;
    }
    .presensi-container {
        max-width: 550px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        padding: 2rem;
        width: 100%;
        margin: 0 auto;
    }
    .presensi-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .presensi-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 0.5rem;
    }
    .tanggal-jam {
        font-size: 0.95rem;
        color: #666;
    }
    .jam-besar {
        font-size: 3rem;
        font-weight: 700;
        color: #667eea;
        text-align: center;
        margin: 1.5rem 0;
        font-variant-numeric: tabular-nums;
    }
    .status-badge {
        display: inline-block;
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        text-align: center;
    }
    .status-hadir { background-color: #d4edda; color: #155724; }
    .status-terlambat { background-color: #fff3cd; color: #856404; }
    .status-izin { background-color: #d1ecf1; color: #0c5460; }
    .status-sakit { background-color: #f8d7da; color: #721c24; }
    
    .button-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .btn-clock {
        padding: 1.5rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: white;
    }
    .btn-clock i { font-size: 2rem; }
    .btn-clock-in { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
    .btn-clock-in:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3); }
    .btn-clock-in:disabled { background: #ccc; cursor: not-allowed; }
    
    .btn-clock-out { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); }
    .btn-clock-out:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3); }
    .btn-clock-out:disabled { background: #ccc; cursor: not-allowed; }
    
    .btn-izin {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-izin:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); }
    .btn-izin:disabled { background: #ccc; cursor: not-allowed; }
    
    .btn-izin-container { margin-top: 1.5rem; }
    
    .info-section {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 12px;
        margin-top: 2rem;
    }
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #dee2e6;
    }
    .info-item:last-child { border-bottom: none; }
    .info-label { color: #666; font-weight: 600; font-size: 0.9rem; }
    .info-value { font-size: 1.1rem; font-weight: 700; color: #333; }
    
    .modal-custom {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        overflow-y: auto;
    }
    .modal-custom.active { display: flex; }
    .modal-content {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        max-width: 400px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        margin: auto;
    }
    .modal-header { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; color: #333; }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        font-family: inherit;
    }
    .form-group textarea { resize: vertical; min-height: 80px; }
    
    .modal-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    .modal-buttons button {
        flex: 1;
        padding: 0.75rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-submit { background: #667eea; color: white; }
    .btn-submit:hover { background: #5568d3; }
    .btn-cancel { background: #e9ecef; color: #333; }
    .btn-cancel:hover { background: #dee2e6; }
    
    .preview-section { margin-bottom: 1.5rem; }
    .preview-title { font-weight: 600; margin-bottom: 0.5rem; color: #333; }
    #videoPreview { width: 100%; border-radius: 10px; background: #000; max-height: 200px; }
    #locationPreview { width: 100%; height: 200px; border-radius: 10px; border: 1px solid #ddd; margin-top: 0.5rem; }
    .location-info { background: #f0f4ff; padding: 1rem; border-radius: 8px; margin-top: 0.5rem; font-size: 0.9rem; color: #555; }
    
    /* Modal Distance Error */
    .modal-distance-error { display: none; }
    .modal-distance-error.active { display: flex; }
    .distance-error-content {
        background: white;
        border-radius: 15px;
        padding: 2.5rem;
        max-width: 450px;
        width: 95%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .distance-error-icon {
        width: 80px;
        height: 80px;
        background: #ff6b6b;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        color: white;
        font-size: 2.5rem;
    }
    .distance-error-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 1.5rem;
    }
    .distance-info-box {
        background: #f8f9fa;
        border-left: 4px solid #ff6b6b;
        padding: 1.2rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        text-align: left;
    }
    .distance-info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.6rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    .distance-info-row:last-child { border-bottom: none; }
    .distance-info-label { font-weight: 600; color: #666; font-size: 0.95rem; }
    .distance-info-value { font-weight: 700; color: #333; font-size: 1rem; }
    .distance-message {
        color: #333;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    .distance-message strong { display: block; margin: 1rem 0 0.5rem; font-weight: 700; color: #ff6b6b; }
    .distance-maps-link {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 1.5rem;
        transition: all 0.3s;
    }
    .distance-maps-link:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3); }
    .distance-error-buttons {
        display: flex;
        gap: 1rem;
    }
    .distance-error-buttons button {
        flex: 1;
        padding: 0.8rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.95rem;
    }
    .btn-error-ok { background: #ff6b6b; color: white; }
    .btn-error-ok:hover { background: #ff5252; }
    
    /* Modal Location Not Set */
    .modal-location-not-set { display: none; }
    .modal-location-not-set.active { display: flex; }
    .location-not-set-content {
        background: white;
        border-radius: 15px;
        padding: 2.5rem;
        max-width: 450px;
        width: 95%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .location-not-set-icon {
        width: 80px;
        height: 80px;
        background: #ffc107;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        color: white;
        font-size: 2.5rem;
    }
    .location-not-set-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 1rem;
    }
    .location-not-set-message {
        color: #666;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    @media (max-width: 480px) {
        .presensi-container { padding: 1.5rem; }
        .button-group { grid-template-columns: 1fr 1fr; }
    }
    
    /* Loading State */
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 2000;
        justify-content: center;
        align-items: center;
    }
    .loading-overlay.active {
        display: flex;
    }
    .loading-content {
        background: white;
        border-radius: 15px;
        padding: 2.5rem;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    .spinner {
        width: 60px;
        height: 60px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1.5rem;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .loading-text {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }
    .loading-subtext {
        font-size: 0.9rem;
        color: #666;
        margin-top: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper">
    <div class="presensi-container">
        <div class="presensi-header">
            <h1>PRESENSI</h1>
            <div class="tanggal-jam">{{ now()->translatedFormat('l, d F Y') }}</div>
        </div>

        <div class="jam-besar" id="jamRealtime">00:00:00</div>

        @if ($presensiHariIni)
            <div style="text-align: center;">
                @if ($presensiHariIni->status_datang === 'Terlambat')
                    <div class="status-badge status-terlambat">⚠️ TERLAMBAT</div>
                @elseif ($presensiHariIni->status_datang === 'Izin')
                    <div class="status-badge status-izin">📋 IZIN</div>
                @elseif ($presensiHariIni->status_datang === 'Sakit')
                    <div class="status-badge status-sakit">🏥 SAKIT</div>
                @elseif ($presensiHariIni->status_datang === 'Hadir')
                    <div class="status-badge status-hadir">✓ HADIR</div>
                @endif
            </div>
        @endif

        <div class="button-group">
            <button class="btn-clock btn-clock-in" id="btnClockIn" onclick="prepareClockIn()" @if($presensiHariIni) disabled @endif>
                <i class="fas fa-right-to-bracket"></i>
                Clock In
            </button>
            <button class="btn-clock btn-clock-out" id="btnClockOut" onclick="prepareClockOut()" @if(!$presensiHariIni || !$presensiHariIni->jam_datang || $presensiHariIni->jam_pulang) disabled @endif>
                <i class="fas fa-right-from-bracket"></i>
                Clock Out
            </button>
        </div>

        <div class="btn-izin-container">
            <button class="btn-izin" id="btnIzin" onclick="openIzinModal()" @if($presensiHariIni) disabled @endif>
                📋 Input Izin
            </button>
        </div>

        <div class="info-section">
            <div class="info-item">
                <span class="info-label">Clock In</span>
                <span class="info-value" id="jamMasuk">
                    @if ($presensiHariIni && $presensiHariIni->jam_datang)
                        {{ $presensiHariIni->jam_datang->format('H:i') }}
                    @else
                        -
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Clock Out</span>
                <span class="info-value" id="jamKeluar">
                    @if ($presensiHariIni && $presensiHariIni->jam_pulang)
                        {{ $presensiHariIni->jam_pulang->format('H:i') }}
                    @else
                        -
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value" id="statusDisplay">
                    @if ($presensiHariIni)
                        {{ $presensiHariIni->status_datang }}
                    @else
                        Belum Presensi
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <div class="loading-text">Memproses Presensi...</div>
        <div class="loading-subtext">Mohon tunggu, sedang menyimpan data dan mengirim notifikasi</div>
    </div>
</div>

<!-- Modal Preview -->
<div class="modal-custom" id="modalPreview">
    <div class="modal-content">
        <div class="modal-header" id="modalPreviewTitle">Preview Clock In</div>
        
        <div class="preview-section">
            <div class="preview-title">📷 Kondisi Muka</div>
            <video id="videoPreview" autoplay playsinline></video>
        </div>

        <div class="preview-section">
            <div class="preview-title">📍 Lokasi Anda</div>
            <div id="locationPreview"></div>
            <div class="location-info" id="locationInfo">Mendeteksi lokasi...</div>
        </div>

        <div class="modal-buttons">
            <button type="button" class="btn-submit" onclick="confirmClockAction()">Lanjutkan</button>
            <button type="button" class="btn-cancel" onclick="closePreviewModal()">Batal</button>
        </div>
    </div>
</div>

<!-- Modal Distance Error -->
<div class="modal-custom modal-distance-error" id="modalDistanceError">
    <div class="distance-error-content">
        <div class="distance-error-icon">⚠️</div>
        <div class="distance-error-title">Anda berada diluar Jangkauan!</div>
        
        <div class="distance-info-box">
            <div class="distance-info-row">
                <span class="distance-info-label">Jarak Saat Ini:</span>
                <span class="distance-info-value" id="currentDistance">-</span>
            </div>
            <div class="distance-info-row">
                <span class="distance-info-label">Jarak Maksimal:</span>
                <span class="distance-info-value">150 Meter</span>
            </div>
        </div>
        
        <div class="distance-message">
            <strong>Silahkan Mendekati lokasi penugasan anda!</strong>
        </div>
        
        <a href="#" id="mapsLink" class="distance-maps-link" target="_blank">📍 Lihat Lokasi Penugasan</a>
        
        <div class="distance-error-buttons">
            <button class="btn-error-ok" onclick="closeDistanceErrorModal()">OK</button>
        </div>
    </div>
</div>

<!-- Modal Location Not Set -->
<div class="modal-custom modal-location-not-set" id="modalLocationNotSet">
    <div class="location-not-set-content">
        <div class="location-not-set-icon">📍</div>
        <div class="location-not-set-title">Lokasi Penugasan Belum Di Set</div>
        <div class="location-not-set-message">
            Admin belum menentukan lokasi presensi untuk Anda. Silakan hubungi Tim Admin untuk mengatur lokasi penugasan Anda.
        </div>
        <div class="distance-error-buttons">
            <button class="btn-error-ok" onclick="closeLocationNotSetModal()">OK</button>
        </div>
    </div>
</div>

<!-- Modal Izin -->
<div class="modal-custom" id="modalIzin">
    <div class="modal-content">
        <div class="modal-header">Input Izin</div>
        <form id="formIzin">
            @csrf
            <div class="form-group">
                <label>Tipe Izin</label>
                <select name="tipe_izin" required>
                    <option value="">-- Pilih Tipe Izin --</option>
                    <option value="Izin">Izin</option>
                    <option value="Sakit">Sakit</option>
                </select>
            </div>
            <div class="form-group">
                <label>Keterangan (Opsional)</label>
                <textarea name="keterangan" placeholder="Tuliskan alasan izin..."></textarea>
            </div>
            <div class="modal-buttons">
                <button type="submit" class="btn-submit">Simpan</button>
                <button type="button" class="btn-cancel" onclick="closeIzinModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script>
    let currentAction = null;
    let currentPosition = null;
    let currentAssignedLocation = null;

    // Format jarak dengan pemisah ribuan
    function formatDistance(meters) {
        return new Intl.NumberFormat('id-ID').format(meters) + ' Meter';
    }

    // Generate Google Maps URL untuk lokasi penugasan
    function generateMapsUrl(lat, lng, locName = 'Lokasi Penugasan') {
        return `https://www.google.com/maps/search/${lat},${lng}/@${lat},${lng},17z?entry=ttu`;
    }

    function updateJam() {
        const now = new Date();
        const jam = String(now.getHours()).padStart(2, '0');
        const menit = String(now.getMinutes()).padStart(2, '0');
        const detik = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('jamRealtime').textContent = `${jam}:${menit}:${detik}`;
    }
    updateJam();
    setInterval(updateJam, 1000);

    async function prepareClockIn() {
        currentAction = 'clockIn';
        document.getElementById('modalPreviewTitle').textContent = 'Preview Clock In';
        await openPreviewModal();
    }

    async function prepareClockOut() {
        currentAction = 'clockOut';
        document.getElementById('modalPreviewTitle').textContent = 'Preview Clock Out';
        await openPreviewModal();
    }

    async function openPreviewModal() {
        document.getElementById('modalPreview').classList.add('active');
        
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
            const video = document.getElementById('videoPreview');
            video.srcObject = stream;
            getLocationForPreview();
        } catch (error) {
            swal('Error', 'Gagal mengakses kamera: ' + error.message, 'error');
            closePreviewModal();
        }
    }

    function getLocationForPreview() {
        if (!navigator.geolocation) {
            document.getElementById('locationInfo').innerHTML = 'Browser tidak mendukung Geolocation';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                currentPosition = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                };
                
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.getElementById('locationInfo').innerHTML = `
                    <strong>Latitude:</strong> ${lat.toFixed(6)}<br>
                    <strong>Longitude:</strong> ${lng.toFixed(6)}<br>
                    <strong>Akurasi:</strong> ${position.coords.accuracy.toFixed(0)} meter
                `;
                
                const mapURL = `https://www.openstreetmap.org/export/embed.html?bbox=${lng-0.01},${lat-0.01},${lng+0.01},${lat+0.01}&layer=mapnik&marker=${lat},${lng}`;
                document.getElementById('locationPreview').innerHTML = `<iframe width="100%" height="200" frameborder="0" src="${mapURL}"></iframe>`;
            },
            (error) => {
                document.getElementById('locationInfo').innerHTML = 'Gagal mendapatkan lokasi: ' + error.message;
            }
        );
    }

    function closePreviewModal() {
        const video = document.getElementById('videoPreview');
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
        }
        document.getElementById('modalPreview').classList.remove('active');
        currentAction = null;
    }

    async function confirmClockAction() {
        if (!currentPosition) {
            swal('Error', 'Lokasi tidak terdeteksi', 'error');
            return;
        }

        try {
            const video = document.getElementById('videoPreview');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            
            canvas.toBlob(async (blob) => {
                const formData = new FormData();
                formData.append('latitude', currentPosition.latitude);
                formData.append('longitude', currentPosition.longitude);
                formData.append('foto', new File([blob], 'foto.jpg', { type: 'image/jpeg' }));
                formData.append('_token', document.querySelector('input[name="_token"]').value);
                
                try {
                    let url = currentAction === 'clockIn' 
                        ? '{{ route("presensi.clock_in") }}' 
                        : '{{ route("presensi.clock_out") }}';
                    
                    // Show loading state
                    document.getElementById('loadingOverlay').classList.add('active');
                    closePreviewModal();
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Keep loading state visible and refresh page
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        // Hide loading state and show error
                        document.getElementById('loadingOverlay').classList.remove('active');
                        
                        // Check jika error adalah distance error
                        if (data.distance !== undefined && data.max_distance !== undefined) {
                            showDistanceErrorModal(data.distance, data.max_distance, data.assigned_location);
                        } else if (data.message && data.message.includes('belum menentukan lokasi')) {
                            showLocationNotSetModal();
                        } else {
                            swal('Error', data.message, 'error');
                        }
                    }
                } catch (error) {
                    // Hide loading state and show error
                    document.getElementById('loadingOverlay').classList.remove('active');
                    swal('Error', error.message, 'error');
                }
            }, 'image/jpeg', 0.9);
        } catch (error) {
            swal('Error', error.message, 'error');
        }
    }

    function showDistanceErrorModal(distance, maxDistance, assignedLocation) {
        document.getElementById('currentDistance').textContent = formatDistance(distance);
        
        // Setup Google Maps link jika ada lokasi penugasan
        if (assignedLocation && assignedLocation.latitude && assignedLocation.longitude) {
            const mapsUrl = generateMapsUrl(assignedLocation.latitude, assignedLocation.longitude, assignedLocation.nama_lokasi);
            document.getElementById('mapsLink').href = mapsUrl;
            document.getElementById('mapsLink').style.display = 'inline-block';
        } else {
            document.getElementById('mapsLink').style.display = 'none';
        }
        
        document.getElementById('modalDistanceError').classList.add('active');
    }

    function closeDistanceErrorModal() {
        document.getElementById('modalDistanceError').classList.remove('active');
    }

    function showLocationNotSetModal() {
        document.getElementById('modalLocationNotSet').classList.add('active');
    }

    function closeLocationNotSetModal() {
        document.getElementById('modalLocationNotSet').classList.remove('active');
    }

    function openIzinModal() {
        document.getElementById('modalIzin').classList.add('active');
    }

    function closeIzinModal() {
        document.getElementById('modalIzin').classList.remove('active');
        document.getElementById('formIzin').reset();
    }

    document.getElementById('formIzin').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            // Show loading state
            document.getElementById('loadingOverlay').classList.add('active');
            closeIzinModal();
            
            const response = await fetch('{{ route("presensi.izin") }}', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Keep loading state visible and refresh page
                setTimeout(() => location.reload(), 1500);
            } else {
                // Hide loading state and show error
                document.getElementById('loadingOverlay').classList.remove('active');
                swal('Error', data.message, 'error');
            }
        } catch (error) {
            // Hide loading state and show error
            document.getElementById('loadingOverlay').classList.remove('active');
            swal('Error', error.message, 'error');
        }
    });

    document.getElementById('modalPreview').addEventListener('click', (e) => {
        if (e.target.id === 'modalPreview') {
            closePreviewModal();
        }
    });

    document.getElementById('modalIzin').addEventListener('click', (e) => {
        if (e.target.id === 'modalIzin') {
            closeIzinModal();
        }
    });

    document.getElementById('modalDistanceError').addEventListener('click', (e) => {
        if (e.target.id === 'modalDistanceError') {
            closeDistanceErrorModal();
        }
    });

    document.getElementById('modalLocationNotSet').addEventListener('click', (e) => {
        if (e.target.id === 'modalLocationNotSet') {
            closeLocationNotSetModal();
        }
    });
</script>
@endsection
