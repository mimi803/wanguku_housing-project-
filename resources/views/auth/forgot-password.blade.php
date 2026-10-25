@extends('layouts.app')
@section('title', 'Reset password by phone')
@section('content')<section class="form-page"><div class="form-card"><span class="eyebrow">ACCOUNT RECOVERY</span><h1>Reset by phone.</h1><p>We’ll send a verification code to the phone number on your account.</p>
@if(session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error-list" role="alert">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('password.email') }}" class="form-stack">@csrf<label>Phone number<input name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="07XX XXX XXX" required></label><button class="button button-dark" type="submit">Send reset code</button></form><p class="form-foot"><a href="{{ route('login') }}">Back to sign in</a></p></div></section>@endsection
