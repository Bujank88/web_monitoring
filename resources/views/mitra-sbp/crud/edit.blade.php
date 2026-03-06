@extends('master')
@section('title') Edit Mitra SBP @endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Edit Data Mitra SBP</h4>
            <a href="{{ route('configuration.mitra-sbp.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('configuration.mitra-sbp.update', $item->id) }}">
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Reg ID</label>
                        <input type="text" name="reg_id" class="form-control" value="{{ old('reg_id', $item->reg_id) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email MyAds <span class="text-danger">*</span></label>
                        <input type="email" name="email_myads" class="form-control" value="{{ old('email_myads', $item->email_myads) }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Area</label>
                        <select name="area" id="area" class="form-control">
                            <option value="">Pilih Area</option>
                            @foreach($areaOptions as $area)
                                <option value="{{ $area }}" {{ old('area', $item->area) === $area ? 'selected' : '' }}>{{ $area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Regional</label>
                        <select name="regional" id="regional" class="form-control">
                            <option value="">Pilih Regional</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Remark</label>
                        <input type="text" name="remark" class="form-control" value="{{ old('remark', $item->remark) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Voucher</label>
                    <input type="text" name="voucher" class="form-control" value="{{ old('voucher', $item->voucher) }}">
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    (function () {
        const map = @json($areaRegionalMap);
        const areaEl = document.getElementById('area');
        const regionalEl = document.getElementById('regional');
        let selectedRegional = @json(old('regional', $item->regional));

        function renderRegionalOptions(keepSelected) {
            const selectedArea = areaEl.value;
            const options = map[selectedArea] || [];

            regionalEl.innerHTML = '<option value="">Pilih Regional</option>';
            regionalEl.disabled = options.length === 0;
            options.forEach(function (regional) {
                const opt = document.createElement('option');
                opt.value = regional;
                opt.textContent = regional;
                if (keepSelected && selectedRegional && selectedRegional === regional) {
                    opt.selected = true;
                }
                regionalEl.appendChild(opt);
            });
        }

        areaEl.addEventListener('change', function () {
            selectedRegional = '';
            renderRegionalOptions(false);
        });

        renderRegionalOptions(true);
    })();
</script>
@endsection
