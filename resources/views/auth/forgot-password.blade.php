@extends('layouts.app')
@section('title', 'Reset password by email')
@section('content')<section class="form-page"><div class="form-card"><span class="eyebrow">ACCOUNT RECOVERY</span><h1>Reset by email.</h1><p>Enter the email address on your account and we’ll send you a secure reset link.</p>
@if(session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error-list" role="alert">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('password.email') }}" class="form-stack">@csrf<label>Email address<input name="email" type="email" inputmode="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com" required></label><button class="button button-dark" type="submit">Email reset link</button></form><p class="form-foot"><a href="{{ route('login') }}">Back to sign in</a></p></div></section>@endsection
