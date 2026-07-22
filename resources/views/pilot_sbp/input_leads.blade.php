@extends('master')

@section('title', $pageTitle ?? 'Input Leads')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $pageTitle ?? 'Input Leads' }}</h3>
            </div>
            <form method="POST" action="{{ route('pilot-sbp-sme.input-leads.store') }}">
                @csrf
                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="form-group">
                        <label for="referral_id">Code Referral - Nama Referral</label>
                        <select name="referral_id" id="referral_id" class="form-control" required>
                            <option value="">Pilih Code Referral - Nama Referral</option>
                            @foreach($referrals as $referral)
                            <option value="{{ $referral->id }}" {{ old('referral_id') == $referral->id ? 'selected' : '' }}>
                                {{ $referral->referral_code }} - {{ $referral->name }}
                            </option>
                            @endforeach
                        </select>
                        @if($referrals->isEmpty())
                        <small class="text-muted">Belum ada referral aktif yang terhubung ke email login ini.</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="company_name">Nama Perusahaan</label>
                        <input type="text" name="company_name" id="company_name" class="form-control" value="{{ old('company_name') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email_myads">Email Myads</label>
                        <input type="email" name="email_myads" id="email_myads" class="form-control" value="{{ old('email_myads') }}" required>
                    </div>

                    <div class="form-group mb-0">
                        <label for="mobile_phone">No Telp</label>
                        <input type="text" name="mobile_phone" id="mobile_phone" class="form-control" value="{{ old('mobile_phone') }}" required>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
