@extends('layouts.app')
@section('title', 'Farmer profile review')
@section('content')<section class="form-page"><div class="form-card"><span class="eyebrow">FARMER PROFILE REVIEW</span><h1>Your farm is in review.</h1><p>Your phone is verified. A Farmers Connect administrator must review your farm details before it appears in the public directory, map or marketplace.</p><p class="form-foot"><a href="{{ route('profile.edit') }}">Review your farm profile</a> · <a href="{{ route('home') }}">Return home</a></p></div></section>@endsection
