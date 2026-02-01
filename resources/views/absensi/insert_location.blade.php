@extends('master')
@section('title') Kelola Lokasi Presensi CVSR @endsection
@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" />
<style>
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1.5rem;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    table.dataTable thead th {
        background: #667eea;
        color: white;
        border-bottom: 2px solid #667eea;
        font-weight: 600;
        padding: 1rem;
    }

    table.dataTable tbody td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #eee;
    }

    table.dataTable tbody tr:hover {
        background: #f8f9fa;
    }

    .btn-action {
        padding: 0.6rem 1.2rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
        margin: 0.25rem;
    }

    .btn-add {
        background: #28a745;
        color: white;
    }

    .btn-add:hover {
        background: #218838;
        transform: translateY(-2px);
    }

    .btn-edit {
        background: #667eea;
        color: white;
    }

    .btn-edit:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }

    .btn-delete {
        background: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    .location-text {
        font-size: 0.85rem;
        color: #666;
        max-width: 200px;
        word-break: break-word;
    }

    .status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-badge.set {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.not-set {
        background: #f8d7da;
        color: #721c24;
    }

    .modal-custom {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        overflow-y: auto;
        align-items: center;
        justify-content: center;
    }

    .modal-custom.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        width: 95%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        margin: auto;
    }

    .modal-header {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #333;
    }

    .form-section {
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
        font-size: 0.95rem;
    }

    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        font-family: monospace;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    #mapModal {
        width: 100%;
        height: 300px;
        border: 2px solid #667eea;
        border-radius: 5px;
        margin-bottom: 1rem;
    }

    .map-info {
        background: #e7f3ff;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        color: #004085;
        border-left: 4px solid #667eea;
    }

    .input-method-toggle {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .toggle-btn {
        flex: 1;
        padding: 0.75rem;
        border: 2px solid #ddd;
        background: white;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }

    .toggle-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .modal-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .modal-buttons button {
        flex: 1;
        padding: 0.75rem;
        border: none;
        border-radius: 5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-submit {
        background: #667eea;
        color: white;
    }

    .btn-submit:hover {
        background: #5568d3;
    }

    .btn-cancel {
        background: #e9ecef;
        color: #333;
    }

    .btn-cancel:hover {
        background: #dee2e6;
    }

    .leaflet-container {
        border-radius: 5px;
        overflow: hidden;
    }

    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            padding: 1.5rem;
        }
    }

</style>
@endsection

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Kelola Lokasi Presensi CVSR</h1>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                📍 Daftar CVSR & Lokasi Presensi
            </div>
            <div class="card-body">
                <table id="cvsrTable" class="table table-striped table-hover w-100">
                    <thead>
                        <tr>
                            <th>Nama Canvasser</th>
                            <th>Email</th>
                            <th>No. HP</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cvsrs as $cvsr)
                            <tr>
                                <td><strong>{{ $cvsr->name }}</strong></td>
                                <td>{{ $cvsr->email }}</td>
                                <td>{{ $cvsr->phone ?? '-' }}</td>
                                <td>
                                    @if ($cvsr->locationPresensi)
                                        <div class="location-text">
                                            <i class="fas fa-map-marker-alt"></i>
                                            {{ number_format($cvsr->locationPresensi->latitude, 6) }}, 
                                            {{ number_format($cvsr->locationPresensi->longitude, 6) }}
                                            @if ($cvsr->locationPresensi->keterangan)
                                                <br><small><strong>{{ $cvsr->locationPresensi->keterangan }}</strong></small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($cvsr->locationPresensi)
                                        <span class="status-badge set">✓ Sudah Diset</span>
                                    @else
                                        <span class="status-badge not-set">⚠ Belum Diset</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($cvsr->locationPresensi)
                                        <button class="btn-action btn-edit" onclick="openEditModal({{ $cvsr->id }}, '{{ $cvsr->name }}', {{ $cvsr->locationPresensi->latitude }}, {{ $cvsr->locationPresensi->longitude }}, '{{ $cvsr->locationPresensi->keterangan }}')">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteLocation({{ $cvsr->id }})">
                                            🗑️ Hapus
                                        </button>
                                    @else
                                        <button class="btn-action btn-add" onclick="openAddModal({{ $cvsr->id }}, '{{ $cvsr->name }}')">
                                            ➕ Tambah
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-users" style="font-size: 2rem; opacity: 0.3;"></i><br>
                                    Tidak ada CVSR terdaftar
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Location -->
<div class="modal-custom" id="locationModal">
    <div class="modal-content">
        <div class="modal-header" id="modalTitle">Tambah Lokasi Presensi</div>

        <input type="hidden" id="cvsrId">
        <input type="hidden" id="isEditMode" value="false">

        <!-- Input Method Toggle -->
        <div class="input-method-toggle">
            <button class="toggle-btn active" onclick="switchMethod('manual')">📝 Input Manual</button>
            <button class="toggle-btn" onclick="switchMethod('map')">🗺️ Pilih di Maps</button>
        </div>

        <!-- Manual Input Section -->
        <div id="manualSection" class="form-section">
            <div class="form-group">
                <label>Nama CVSR</label>
                <input type="text" id="cvsrName" readonly style="background: #f8f9fa;">
            </div>
            <div class="form-group">
                <label>Latitude <span style="color: #dc3545;">*</span></label>
                <input type="number" id="inputLatitude" placeholder="Contoh: -6.123456" step="0.000001" required>
            </div>
            <div class="form-group">
                <label>Longitude <span style="color: #dc3545;">*</span></label>
                <input type="number" id="inputLongitude" placeholder="Contoh: 106.123456" step="0.000001" required>
            </div>
            <div class="form-group">
                <label>Keterangan <span style="color: #999;">(Opsional)</span></label>
                <input type="text" id="inputKeterangan" placeholder="Contoh: Kantor Pusat, Lokasi Retail, dsb">
            </div>
        </div>

        <!-- Map Section -->
        <div id="mapSection" class="form-section" style="display: none;">
            <div class="map-info">
                <i class="fas fa-info-circle"></i> Klik pada peta untuk menentukan lokasi. Koordinat akan otomatis terisi.
            </div>
            <div id="mapModal"></div>
            <div class="form-group">
                <label>Keterangan <span style="color: #999;">(Opsional)</span></label>
                <input type="text" id="mapKeterangan" placeholder="Contoh: Kantor Pusat, Lokasi Retail, dsb">
            </div>
        </div>

        <div class="modal-buttons">
            <button type="button" class="btn-submit" onclick="saveLocation()">💾 Simpan</button>
            <button type="button" class="btn-cancel" onclick="closeLocationModal()">Batal</button>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@1.13.0/dist/Control.Geocoder.js"></script>
<script>
    let mapInstance = null;
    let markerInstance = null;
    let currentMethod = 'manual';

    // Initialize DataTable
    $(document).ready(function() {
        $('#cvsrTable').DataTable({
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ data",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    });

    // Switch between input methods
    function switchMethod(method) {
        currentMethod = method;
        
        // Update toggle buttons
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Toggle sections
        document.getElementById('manualSection').style.display = method === 'manual' ? 'block' : 'none';
        document.getElementById('mapSection').style.display = method === 'map' ? 'block' : 'none';

        // Initialize map if needed
        if (method === 'map' && !mapInstance) {
            setTimeout(() => initializeMap(), 100);
        }
    }

    // Initialize map
    function initializeMap() {
        if (mapInstance) return;

        const defaultLat = parseFloat(document.getElementById('inputLatitude').value) || -6.2088;
        const defaultLng = parseFloat(document.getElementById('inputLongitude').value) || 106.8456;

        mapInstance = L.map('mapModal').setView([defaultLat, defaultLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(mapInstance);

        mapInstance.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            document.getElementById('inputLatitude').value = lat.toFixed(6);
            document.getElementById('inputLongitude').value = lng.toFixed(6);

            if (markerInstance) {
                markerInstance.setLatLng([lat, lng]);
            } else {
                markerInstance = L.marker([lat, lng]).addTo(mapInstance);
            }
        });

        // Add initial marker if coordinates exist
        const lat = parseFloat(document.getElementById('inputLatitude').value);
        const lng = parseFloat(document.getElementById('inputLongitude').value);
        
        if (!isNaN(lat) && !isNaN(lng)) {
            markerInstance = L.marker([lat, lng]).addTo(mapInstance);
        }
    }

    // Open Add Modal
    function openAddModal(cvsrId, cvsrName) {
        document.getElementById('cvsrId').value = cvsrId;
        document.getElementById('cvsrName').value = cvsrName;
        document.getElementById('isEditMode').value = 'false';
        document.getElementById('modalTitle').textContent = 'Tambah Lokasi Presensi';
        
        // Reset form
        document.getElementById('inputLatitude').value = '';
        document.getElementById('inputLongitude').value = '';
        document.getElementById('inputKeterangan').value = '';
        document.getElementById('mapKeterangan').value = '';
        
        currentMethod = 'manual';
        document.getElementById('manualSection').style.display = 'block';
        document.getElementById('mapSection').style.display = 'none';
        document.querySelectorAll('.toggle-btn')[0].classList.add('active');
        document.querySelectorAll('.toggle-btn')[1].classList.remove('active');

        mapInstance = null;
        markerInstance = null;

        document.getElementById('locationModal').classList.add('active');
    }

    // Open Edit Modal
    function openEditModal(cvsrId, cvsrName, lat, lng, keterangan) {
        document.getElementById('cvsrId').value = cvsrId;
        document.getElementById('cvsrName').value = cvsrName;
        document.getElementById('inputLatitude').value = lat;
        document.getElementById('inputLongitude').value = lng;
        document.getElementById('inputKeterangan').value = keterangan || '';
        document.getElementById('mapKeterangan').value = keterangan || '';
        document.getElementById('isEditMode').value = 'true';
        document.getElementById('modalTitle').textContent = 'Edit Lokasi Presensi';

        currentMethod = 'manual';
        document.getElementById('manualSection').style.display = 'block';
        document.getElementById('mapSection').style.display = 'none';
        document.querySelectorAll('.toggle-btn')[0].classList.add('active');
        document.querySelectorAll('.toggle-btn')[1].classList.remove('active');

        mapInstance = null;
        markerInstance = null;

        document.getElementById('locationModal').classList.add('active');
    }

    // Close Modal
    function closeLocationModal() {
        document.getElementById('locationModal').classList.remove('active');
        if (mapInstance) {
            mapInstance.remove();
            mapInstance = null;
            markerInstance = null;
        }
    }

    // Save Location
    async function saveLocation() {
        const cvsrId = document.getElementById('cvsrId').value;
        let latitude = document.getElementById('inputLatitude').value;
        let longitude = document.getElementById('inputLongitude').value;
        let keterangan = currentMethod === 'manual' 
            ? document.getElementById('inputKeterangan').value 
            : document.getElementById('mapKeterangan').value;
        const isEditMode = document.getElementById('isEditMode').value === 'true';

        if (!latitude || !longitude) {
            alert('Latitude dan Longitude harus diisi!');
            return;
        }

        try {
            const url = isEditMode 
                ? `{{ route('location-presensi.update', ['id' => 'ID']) }}`.replace('ID', cvsrId)
                : '{{ route("location-presensi.store") }}';

            const formData = new FormData();
            formData.append('user_id', cvsrId);
            formData.append('latitude', latitude);
            formData.append('longitude', longitude);
            formData.append('keterangan', keterangan);
            formData.append('_token', '{{ csrf_token() }}');
            
            if (isEditMode) {
                formData.append('_method', 'PUT');
            }

            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                alert('Lokasi berhasil disimpan!');
                closeLocationModal();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Delete Location
    async function deleteLocation(cvsrId) {
        if (!confirm('Apakah Anda yakin ingin menghapus lokasi ini?')) {
            return;
        }

        try {
            const response = await fetch(
                `{{ route('location-presensi.destroy', ['id' => 'ID']) }}`.replace('ID', cvsrId),
                {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }
            );

            const data = await response.json();

            if (data.success) {
                alert('Lokasi berhasil dihapus!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Close modal on outside click
    document.getElementById('locationModal')?.addEventListener('click', (e) => {
        if (e.target.id === 'locationModal') {
            closeLocationModal();
        }
    });
</script>
@endsection
