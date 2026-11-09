@extends('master')

@section('title', 'Change Password')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .password-wrapper {
        position: relative;
    }
    .password-wrapper i {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }
</style>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Change Password</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('password.update') }}" method="POST">
            @csrf

            {{-- CURRENT PASSWORD --}}
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-wrapper">
                    <input type="password" id="current_password" name="current_password" 
                           class="form-control" placeholder="Enter current password">
                    <i class="bi bi-eye-slash toggle-password" data-target="#current_password"></i>
                </div>
                @error('current_password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- CONFIRM CURRENT PASSWORD --}}
            <div class="form-group">
                <label for="current_password_confirmation">Re-enter Current Password</label>
                <div class="password-wrapper">
                    <input type="password" id="current_password_confirmation" 
                           name="current_password_confirmation"
                           class="form-control" placeholder="Re-enter current password">
                    <i class="bi bi-eye-slash toggle-password" data-target="#current_password_confirmation"></i>
                </div>
                @error('current_password_confirmation')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- NEW PASSWORD --}}
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" 
                           class="form-control" placeholder="Enter new password">
                    <i class="bi bi-eye-slash toggle-password" data-target="#new_password"></i>
                </div>
                @error('new_password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- CONFIRM NEW PASSWORD --}}
            <div class="form-group">
                <label for="new_password_confirmation">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password_confirmation"
                           name="new_password_confirmation"
                           class="form-control" placeholder="Re-enter new password">
                    <i class="bi bi-eye-slash toggle-password" data-target="#new_password_confirmation"></i>
                </div>
                @error('new_password_confirmation')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group d-flex gap-2">
                {{-- <a href="{{ route('dashboard') }}" class="btn btn-secondary flex-grow-1 m-1">Cancel</a> --}}
                <button type="submit" class="btn btn-primary flex-grow-1 m-1">Update Password</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    // Toggle Password Visibility
    $(document).on('click', '.toggle-password', function() {
        let input = $($(this).data('target'));

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).removeClass('bi-eye-slash').addClass('bi-eye');
        } else {
            input.attr('type', 'password');
            $(this).removeClass('bi-eye').addClass('bi-eye-slash');
        }
    });
</script>
@endsection
