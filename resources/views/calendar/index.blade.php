@extends('master')
@section('title', 'Booking Calendar')
@section('css')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
    .fc-event {
        border-radius: 6px !important;
        padding: 2px 6px !important;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.4;
        cursor: pointer;
    }

    .fc-daygrid-event {
        margin-bottom: 3px;
    }

    .fc-event-title {
        white-space: normal !important;
    }

    .fc-daygrid-day-number {
        font-weight: 600;
        color: #6c757d;
    }

    /* Highlight today */
    .fc-day-today {
        background: #fff3cd !important;
    }

    /* Header */
    .fc-toolbar-title {
        font-weight: 600;
        font-size: 18px;
    }

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

    /* Select styling untuk nama */
    #namaSelect {
        min-height: 45px !important;
        padding: 12px 15px !important;
    }

    #modalBooking .select2-container .select2-selection--single {
        height: 45px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
    }

    #modalBooking .select2-selection--single .select2-selection__rendered {
        line-height: 41px;
        padding-left: 12px;
        font-size: 14px;
    }

    #modalBooking .select2-selection--single .select2-selection__arrow {
        height: 41px;
    }

    #modalBooking .select2-container--open .select2-selection--single,
    #modalBooking .select2-container--focus .select2-selection--single {
        border-color: #17a2b8;
    }

    #modalBooking .select2-results__group {
        color: #138496;
        background: #f0f7f8;
    }

    .form-group label {
        font-weight: 600;
        font-size: 14px;
        color: #333;
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
</style>
@endsection
@section('content')
<div class="d-flex justify-content-between mb-2">
    <h5>Calendar Booking</h5>
    <div>
        <button class="btn btn-success" id="btnDownload">
            <i class="fa fa-download"></i> Download Calendar
        </button>
        <button class="btn btn-primary" data-toggle="modal" data-target="#modalBooking">
            + Tambah Booking
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-1"></div>
    <div class="col-md-10">
        <div id="calendar"></div>
    </div>
    <div class="col-md-1"></div>
</div>
<br>
<hr>
<br>


<div class="modal fade" id="modalDetailBooking" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check mr-2"></i>Detail Booking
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="event-id">
                <div class="form-group">
                    <label><b>Nama</b></label>
                    <p id="d-nama">-</p>
                </div>
                <div class="form-group">
                    <label><b>Lokasi</b></label>
                    <p id="d-lokasi">-</p>
                </div>
                <div class="form-group">
                    <label><b>Tanggal</b></label>
                    <p id="d-tanggal">-</p>
                </div>
                <div class="form-group">
                    <label><b>Waktu</b></label>
                    <p id="d-waktu">-</p>
                </div>
                <div class="form-group">
                    <label><b>Keterangan</b></label>
                    <p id="d-keterangan">-</p>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary btn-sm" id="btnEdit">
                    <i class="fa fa-edit"></i> Edit Booking
                </button>
                <button class="btn btn-danger btn-sm" id="btnDelete">
                    <i class="fa fa-trash"></i> Hapus Booking
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBooking" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <form id="formBooking">
            @csrf
            <input type="hidden" id="booking-id" name="booking_id">
            <div class="modal-content">

                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-calendar-check mr-2"></i>Tambah Booking
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label for="namaSelect"><i class="fas fa-user text-info mr-2"></i>Nama</label>
                        <select name="nama" class="form-control" id="namaSelect" required>
                            <option value="">-- Pilih Nama --</option>

                            @foreach($participantGroups as $role => $members)
                                <optgroup label="{{ $role === 'PH' ? 'PH (Powerhouse)' : $role }}">
                                    @foreach($members as $name => $color)
                                        <option value="{{ $name }}" data-color="{{ $color }}">{{ $name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>

                        <!-- hidden untuk kirim warna -->
                        <input type="hidden" name="color" id="colorInput">
                    </div>

                    <div class="form-group">
                        <label for="lokasiInput"><i class="fas fa-map-marker-alt text-info mr-2"></i>Lokasi</label>
                        <input type="text" id="lokasiInput" name="lokasi" class="form-control" placeholder="Masukkan lokasi kunjungan" required>
                    </div>

                    <div class="form-group">
                        <label for="tanggalInput"><i class="fas fa-calendar-alt text-info mr-2"></i>Tanggal</label>
                        <input type="date" id="tanggalInput" name="tanggal" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="startInput"><i class="fas fa-clock text-info mr-2"></i>Mulai</label>
                                <input type="time" id="startInput" name="start" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endInput"><i class="fas fa-clock text-info mr-2"></i>Selesai</label>
                                <input type="time" id="endInput" name="end" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="keteranganInput"><i class="fas fa-pen-fancy text-info mr-2"></i>Keterangan</label>
                        <textarea id="keteranganInput" name="keterangan" class="form-control" rows="3" placeholder="Tambahkan keterangan (opsional)"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check mr-2"></i>Simpan
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>


<script>
let calendar;
let selectedEventId = null;

document.addEventListener('DOMContentLoaded', function () {

    $('#namaSelect').select2({
        placeholder: '-- Pilih Nama --',
        width: '100%',
        dropdownParent: $('#modalBooking'),
        minimumResultsForSearch: 0,
        language: {
            noResults: function () { return 'Nama tidak ditemukan'; }
        }
    }).on('select2:open', function () {
        const search = document.querySelector('#modalBooking .select2-search__field');
        if (search) {
            search.placeholder = 'Cari nama...';
            search.focus();
        }
    });

    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        locale: 'id',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: "{{ url('/calendar/events') }}",

        eventClick: function(info) {
            let e = info.event.extendedProps;

            selectedEventId = info.event.id;
            $('#event-id').val(selectedEventId);

            // Format tanggal ke format lokal Indonesia
            let formattedDate = '-';
            if (e.tanggal) {
                const dateObj = new Date(e.tanggal);
                formattedDate = dateObj.toLocaleDateString('id-ID', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            }

            // Format waktu hanya HH:MM
            let formattedTime = '-';
            if (e.waktu) {
                const times = e.waktu.split(' - ');
                const startTime = times[0] ? times[0].substring(0, 5) : '-';
                const endTime = times[1] ? times[1].substring(0, 5) : '-';
                formattedTime = startTime + ' - ' + endTime;
            }

            $('#d-nama').text(e.nama || '-');
            $('#d-lokasi').text(e.lokasi || '-');
            $('#d-tanggal').text(formattedDate);
            $('#d-waktu').text(formattedTime);
            $('#d-keterangan').text(e.keterangan || '-');
            
            $('#modalDetailBooking').modal('show');
        }
    });

    calendar.render();
});

/* ======================
   TAMBAH BOOKING
====================== */
$('#formBooking').on('submit', function (e) {
    e.preventDefault();
    
    const bookingId = $('#booking-id').val();
    const isEdit = bookingId ? true : false;
    const url = isEdit ? "{{ url('/calendar/update') }}/" + bookingId : "{{ url('/calendar/store') }}";
    const method = isEdit ? "POST" : "POST";

    $.ajax({
        url: url,
        method: method,
        data: $(this).serialize(),
        success: function () {
            $('#modalBooking').modal('hide');
            calendar.refetchEvents();
            $('#formBooking')[0].reset();
            $('#booking-id').val('');
            $('#modalTitle').text('Tambah Booking');

            if (isEdit) {
                // Show success alert untuk edit
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Diubah',
                    text: 'Jadwal booking berhasil diperbarui',
                    confirmButtonColor: '#17a2b8',
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false
                });

                // Reload detail modal otomatis setelah refetch selesai
                setTimeout(function() {
                    if (selectedEventId) {
                        const event = calendar.getEventById(selectedEventId);
                        if (event) {
                            let e = event.extendedProps;

                            // Format tanggal ke format lokal Indonesia
                            let formattedDate = '-';
                            if (e.tanggal) {
                                const dateObj = new Date(e.tanggal);
                                formattedDate = dateObj.toLocaleDateString('id-ID', { 
                                    weekday: 'long', 
                                    year: 'numeric', 
                                    month: 'long', 
                                    day: 'numeric' 
                                });
                            }

                            // Format waktu hanya HH:MM
                            let formattedTime = '-';
                            if (e.waktu) {
                                const times = e.waktu.split(' - ');
                                const startTime = times[0] ? times[0].substring(0, 5) : '-';
                                const endTime = times[1] ? times[1].substring(0, 5) : '-';
                                formattedTime = startTime + ' - ' + endTime;
                            }

                            $('#d-nama').text(e.nama || '-');
                            $('#d-lokasi').text(e.lokasi || '-');
                            $('#d-tanggal').text(formattedDate);
                            $('#d-waktu').text(formattedTime);
                            $('#d-keterangan').text(e.keterangan || '-');
                            $('#modalDetailBooking').modal('show');
                        }
                    }
                }, 500);
            } else {
                // Show success alert untuk tambah baru
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Ditambahkan',
                    text: 'Booking baru berhasil ditambahkan',
                    confirmButtonColor: '#17a2b8',
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            }
        }
    });
});

/* ======================
   EDIT BOOKING
====================== */
$('#btnEdit').on('click', function () {
    if (!selectedEventId) {
        Swal.fire({
            icon: 'warning',
            title: 'Pilih Event',
            text: 'Silakan pilih event terlebih dahulu',
            confirmButtonColor: '#17a2b8',
            confirmButtonText: 'Mengerti'
        });
        return;
    }

    // Get event data
    const event = calendar.getEventById(selectedEventId);
    const extendedProps = event.extendedProps;

    // Fill form with event data
    $('#booking-id').val(selectedEventId);
    const nameSelect = $('#namaSelect');
    nameSelect.find('option[data-booking-only]').remove();
    if (!Array.from(nameSelect[0].options).some(option => option.value === extendedProps.nama)) {
        const option = new Option(extendedProps.nama, extendedProps.nama);
        $(option).attr('data-booking-only', 'true').attr('data-color', event.backgroundColor);
        nameSelect.append(option);
    }
    nameSelect.val(extendedProps.nama).trigger('change');
    $('input[name="lokasi"]').val(extendedProps.lokasi);
    $('input[name="tanggal"]').val(extendedProps.tanggal);
    $('input[name="start"]').val(event.start.toLocaleTimeString('sv-SE').slice(0, 5));
    $('input[name="end"]').val(event.end.toLocaleTimeString('sv-SE').slice(0, 5));
    $('textarea[name="keterangan"]').val(extendedProps.keterangan);

    $('#modalTitle').text('Edit Booking');
    $('#modalBooking').modal('show');
});

/* ======================
   DELETE BOOKING
====================== */
$('#btnDelete').on('click', function () {

    if (!selectedEventId) {
        Swal.fire({
            icon: 'warning',
            title: 'Pilih Event',
            text: 'Silakan pilih event terlebih dahulu',
            confirmButtonColor: '#17a2b8',
            confirmButtonText: 'Mengerti'
        });
        return;
    }

    Swal.fire({
        icon: 'question',
        title: 'Hapus Booking',
        text: 'Yakin ingin menghapus booking ini?',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('/calendar/delete') }}/" + selectedEventId,
                method: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function () {
                    // Tutup modal detail booking
                    $('#modalDetailBooking').modal('hide');
                    
                    calendar.refetchEvents();
                    selectedEventId = null;

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dihapus',
                        text: 'Booking berhasil dihapus',
                        confirmButtonColor: '#17a2b8',
                        timer: 1500,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
});

/* ======================
   DOWNLOAD CALENDAR
====================== */
$('#btnDownload').on('click', function () {
    window.location.href = "{{ url('/calendar/download') }}";
});
$('#namaSelect').on('change', function () {
    const color = $(this).find(':selected').data('color') || '#3788d8';
    $('#colorInput').val(color);
});

$('#modalBooking').on('hidden.bs.modal', function () {
    $('#namaSelect option[data-booking-only]').remove();
    $('#formBooking')[0].reset();
    $('#namaSelect').val('').trigger('change');
    $('#booking-id').val('');
    $('#colorInput').val('');
    $('#modalTitle').text('Tambah Booking');
});

</script>
@endsection
