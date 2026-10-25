@extends('layouts.app')
@section('title', $purpose === 'reset' ? 'Reset password' : 'Verify phone')
@section('content')
<section class="form-page"><div class="form-card"><span class="eyebrow">PHONE VERIFICATION</span><h1>{{ $purpose === 'reset' ? 'Reset your password.' : 'Check your phone.' }}</h1><p>Enter the six-digit code sent to {{ $phone }}.</p>
    @if($errors->any())<div class="error-list" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('phone.verify.store') }}" class="form-stack">@csrf<label>Verification code<input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required></label>
        @if($purpose === 'reset')<label>New password<input name="password" type="password" minlength="8" autocomplete="new-password" required></label><label>Confirm new password<input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></label>@endif
        <button class="button button-dark" type="submit">{{ $purpose === 'reset' ? 'Verify and reset password' : 'Verify phone' }}</button>
    </form>
    <form method="post" action="{{ route('phone.verify.resend') }}" class="form-foot">@csrf<button class="text-link" type="submit">Send a new code</button></form>
</div></section>
@endsection
