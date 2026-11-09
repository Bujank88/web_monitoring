@extends('master')

@section('title', 'New Leads')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .card-title { font-weight: bold; }
    .form-group label { font-weight: 600; font-size: 14px; color: #333; }
    
    /* Input styling */
    .form-control {
        border: 2px solid #e0e0e0 !important;
        border-radius: 8px !important;
        padding: 10px 12px !important;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #17a2b8 !important;
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25) !important;
        background-color: #f8ffff;
    }
    
    /* Date and Time inputs - full clickable area */
    input[type="date"],
    input[type="time"] {
        cursor: pointer;
        padding: 10px 12px !important;
        font-size: 14px;
    }
    
    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="time"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        margin-right: 8px;
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    
    input[type="date"]::-webkit-calendar-picker-indicator:hover,
    input[type="time"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
    }
    
    .select2-container .select2-selection--single {
        height: 40px !important;
        padding: 8px 12px !important;
        border: 2px solid #e0e0e0 !important;
        border-radius: 8px !important;
        display: flex;
        align-items: center;
        font-size: 14px;
        background-color: #fff;
    }
    
    .select2-container .select2-selection--single:focus {
        border-color: #17a2b8 !important;
    }
    
    .d-flex.gap-3 > label {
        cursor: pointer;
        user-select: none;
    }
    
    .text-danger { font-size: 13px; }
    
    /* Modal styling */
    .modal-header {
        border-radius: 8px 8px 0 0 !important;
        padding: 1.5rem !important;
    }
    
    .modal-body {
        padding: 2rem !important;
    }
    
    .modal-footer {
        padding: 1.5rem !important;
        background-color: #f8f9fa;
        border-top: 1px solid #e0e0e0;
    }
    
    /* Button styling */
    .btn {
        border-radius: 6px !important;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-info {
        background-color: #17a2b8 !important;
        border-color: #17a2b8 !important;
    }
    
    .btn-info:hover {
        background-color: #138496 !important;
        border-color: #117a8b !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
    }
    
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
    }
    
    /* Status badge styling */
    #scheduleStatus {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
    }
    
    #scheduleStatus .text-success {
        background-color: #d4edda;
        color: #155724;
        padding: 6px 12px;
        border-radius: 20px;
        display: inline-block;
    }
    
    /* Card styling */
    .card {
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }
    
    .card-header {
        background-color: #007bff !important;
        border-radius: 8px 8px 0 0 !important;
    }
</style>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">New Leads</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('leads-master.store') }}" method="POST" enctype="multipart/form-data" id="formNewLead">
            @csrf
            
            <!-- Hidden inputs untuk schedule -->
            <input type="hidden" id="schedule_lokasi" name="schedule_lokasi">
            <input type="hidden" id="schedule_tanggal" name="schedule_tanggal">
            <input type="hidden" id="schedule_waktu_mulai" name="schedule_waktu_mulai">
            <input type="hidden" id="schedule_waktu_selesai" name="schedule_waktu_selesai">
            <input type="hidden" id="schedule_keterangan" name="schedule_keterangan">

            <div class="row">
                {{-- USER NAME --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label>User Canvasser</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                        <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                    </div>
                </div>

                {{-- LEAD SOURCES --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="source_id">Source Leads</label>
                        <select name="source_id" id="source_id" class="form-control select2" required>
                            {{-- <option value="">-- Pilih Source --</option> --}}
                            @foreach ($leadSources as $ls)
                                <option value="{{ $ls->id }}" {{ old('source_id') == $ls->id ? 'selected' : '' }}>
                                    {{ $ls->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('source_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- NAMA PERUSAHAAN / INSTANSI --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="company_name">Nama Perusahaan / Instansi</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" 
                            placeholder="Masukkan nama perusahaan atau instansi" value="{{ old('company_name') }}">
                        @error('company_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- NO HP --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="mobile_phone">No HP Pelanggan</label>
                        <input type="text" id="mobile_phone" name="mobile_phone" class="form-control" 
                            placeholder="Masukkan nomor HP pelanggan" value="{{ old('mobile_phone') }}">
                        @error('mobile_phone')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- EMAIL --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">Email Pelanggan</label>
                        <input type="email" id="email" name="email" class="form-control" 
                            placeholder="Masukkan email pelanggan" value="{{ old('email') }}">
                        @error('email')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- NAMA PELANGGAN --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nama">Nama Pelanggan</label>
                        <input type="text" id="nama" name="nama" class="form-control" 
                            placeholder="Masukkan nama pelanggan" value="{{ old('nama') }}">
                        @error('nama')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- SECTOR --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sector_id">Sector</label>
                        <select name="sector_id" id="sector_id" class="form-control select2">
                            {{-- <option value="">-- Pilih Sector --</option> --}}
                            @foreach ($sectors as $sector)
                                <option value="{{ $sector->id }}" {{ old('sector_id') == $sector->id ? 'selected' : '' }}>
                                    {{ $sector->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('sector_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Akun Myads --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="myads_account">Akun MyAds</label>
                        <input type="text" id="myads_account" name="myads_account" class="form-control" 
                            placeholder="Masukkan Akun MyAds" value="{{ old('myads_account') }}">
                            <small class="text-danger">*) Diisi jika sudah register akun MyAds</small>
                        @error('myads_account')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- REMARKS --}}
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="3" 
                            placeholder="Tambahkan catatan jika perlu">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            @if(auth()->user()->hasRole('Admin') || auth()->user()->role === 'PH')
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="btnSchedule" class="d-block">Jadwal Kunjungan</label>
                        <button type="button" class="btn btn-info" id="btnScheduleVisit" data-toggle="modal" data-target="#modalScheduleVisit">
                            <i class="fas fa-calendar-plus"></i> Atur Jadwal Kunjungan
                        </button>
                        <span id="scheduleStatus" class="ml-2 text-muted">Belum diatur</span>
                        <small class="text-danger">*) Wajib Diisi sebelum menambahkan New Leads</small>
                    </div>
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group d-flex gap-2">
                        <a href="{{ route('leads-master.index') }}" class="btn btn-secondary flex-grow-1 m-1">Kembali</a>
                        <button type="submit" class="btn btn-primary flex-grow-1 m-1">Simpan</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

<!-- Modal Atur Jadwal Kunjungan -->
<div class="modal fade" id="modalScheduleVisit" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check mr-2"></i>Atur Jadwal Kunjungan
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" id="btnCloseModal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="modalScheduleLokasi"><i class="fas fa-map-marker-alt text-info mr-2"></i>Lokasi Kunjungan</label>
                            <input type="text" id="modalScheduleLokasi" class="form-control" placeholder="Masukkan lokasi kunjungan" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="modalScheduleDate"><i class="fas fa-calendar-alt text-info mr-2"></i>Tanggal Kunjungan</label>
                            <input type="date" id="modalScheduleDate" class="form-control" style="cursor: pointer; padding-right: 8px;" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="modalScheduleTimeStart"><i class="fas fa-clock text-info mr-2"></i>Waktu Mulai</label>
                            <input type="time" id="modalScheduleTimeStart" class="form-control" style="cursor: pointer; padding-right: 8px;" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="modalScheduleTimeEnd"><i class="fas fa-clock text-info mr-2"></i>Waktu Selesai</label>
                            <input type="time" id="modalScheduleTimeEnd" class="form-control" style="cursor: pointer; padding-right: 8px;" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="modalScheduleKeterangan"><i class="fas fa-pen-fancy text-info mr-2"></i>Keterangan Kunjungan</label>
                            <textarea id="modalScheduleKeterangan" class="form-control" rows="3" 
                                placeholder="Tambahkan keterangan kunjungan (opsional)"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelSchedule" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="btnSaveSchedule">
                    <i class="fas fa-check mr-2"></i>Simpan Jadwal
                </button>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "-- Pilih --",
            allowClear: true,
            width: '100%'
        });

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        $('#modalScheduleDate').attr('min', today);

        // Check apakah user adalah PH
        const isPH = {{ (auth()->user()->hasRole('Admin') || auth()->user()->role === 'PH') ? 'true' : 'false' }};

        // Ketika modal ditutup dengan X, jangan clear data jika sudah ada
        $('#modalScheduleVisit').on('hidden.bs.modal', function() {
            const hasSchedule = $('#schedule_tanggal').val() && $('#schedule_waktu_mulai').val();
            if (!hasSchedule && isPH && $('#btnScheduleVisit:visible').length > 0) {
                // Jangan lakukan apa-apa, user bisa batal tanpa schedule untuk admin
                // Tapi untuk PH, akan dicek saat submit
            }
        });

        // Tombol Atur Jadwal
        $('#btnScheduleVisit').on('click', function() {
            // Load data yang sudah ada ke modal
            $('#modalScheduleLokasi').val($('#schedule_lokasi').val());
            $('#modalScheduleDate').val($('#schedule_tanggal').val());
            $('#modalScheduleTimeStart').val($('#schedule_waktu_mulai').val());
            $('#modalScheduleTimeEnd').val($('#schedule_waktu_selesai').val());
            $('#modalScheduleKeterangan').val($('#schedule_keterangan').val());
        });

        // Tombol Simpan Jadwal
        $('#btnSaveSchedule').on('click', function() {
            const lokasi = $('#modalScheduleLokasi').val();
            const tanggal = $('#modalScheduleDate').val();
            const waktuMulai = $('#modalScheduleTimeStart').val();
            const waktuSelesai = $('#modalScheduleTimeEnd').val();
            const keterangan = $('#modalScheduleKeterangan').val();

            // Validasi
            if (!lokasi || !tanggal || !waktuMulai || !waktuSelesai) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Tidak Lengkap',
                    html: 'Mohon isi <strong>lokasi, tanggal, dan waktu kunjungan</strong>',
                    confirmButtonColor: '#17a2b8',
                    confirmButtonText: 'Mengerti'
                });
                return;
            }

            // Simpan ke hidden inputs
            $('#schedule_lokasi').val(lokasi);
            $('#schedule_tanggal').val(tanggal);
            $('#schedule_waktu_mulai').val(waktuMulai);
            $('#schedule_waktu_selesai').val(waktuSelesai);
            $('#schedule_keterangan').val(keterangan);

            // Update status text
            const dateObj = new Date(tanggal);
            const dateStr = dateObj.toLocaleDateString('id-ID', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            $('#scheduleStatus').html(`<span class="text-success"><i class="fas fa-check-circle"></i> ${dateStr} ${waktuMulai}-${waktuSelesai}</span>`);

            // Tutup modal
            $('#modalScheduleVisit').modal('hide');

            // Success notification
            Swal.fire({
                icon: 'success',
                title: 'Jadwal Berhasil Disimpan',
                html: `Jadwal kunjungan ke <strong>${$('#schedule_lokasi').val()}</strong> pada <strong>${dateStr}</strong> telah diatur`,
                timer: 2000,
                timerProgressBar: true,
                confirmButtonColor: '#17a2b8',
                showConfirmButton: false
            });
        });

        // Form submission
        $('#formNewLead').on('submit', function(e) {
            // Cek apakah user adalah PH
            const tanggal = $('#schedule_tanggal').val();
            const waktuMulai = $('#schedule_waktu_mulai').val();
            const waktuSelesai = $('#schedule_waktu_selesai').val();

            if (isPH && (!tanggal || !waktuMulai || !waktuSelesai)) {
                e.preventDefault();
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Jadwal Kunjungan Belum Diatur',
                    html: 'Anda <strong>harus mengatur jadwal kunjungan</strong> terlebih dahulu sebelum menyimpan leads',
                    confirmButtonColor: '#17a2b8',
                    confirmButtonText: 'Atur Jadwal Sekarang'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#modalScheduleVisit').modal('show');
                    }
                });
                return false;
            }
        });
    });
</script>
@endsection

