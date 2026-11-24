@if (Auth::user()->hasRole('Admin'))
    <div class="form-group">
        <label for="assigned_user_id">Bagikan ke Canvasser</label>
        <select id="assigned_user_id" name="user_id" class="form-control @error('user_id') is-invalid @enderror">
            <option value="">Belum dibagikan</option>
            @if (isset($lead) && $lead->user_id && ! $canvassers->contains('id', $lead->user_id))
                <option value="{{ $lead->user_id }}" selected disabled>{{ $lead->canvasser?->name ?? 'Penerima tidak tersedia' }} (tidak aktif)</option>
            @endif
            @foreach ($canvassers as $canvasser)
                <option value="{{ $canvasser->id }}" @selected((string) old('user_id', $lead->user_id ?? '') === (string) $canvasser->id)>{{ $canvasser->name }} ({{ $canvasser->email }})</option>
            @endforeach
        </select>
        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
@endif
