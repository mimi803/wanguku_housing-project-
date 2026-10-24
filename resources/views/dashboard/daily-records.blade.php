@extends('layouts.app')
@section('title', 'Daily flock & production')
@section('content')
<section class="workspace">
    <div class="workspace-head">
        <div><span class="eyebrow">FLOCK & PRODUCTION TRACKING</span><h1>Daily farm records</h1><p>Keep a dated record of flock size, eggs, feed, water and losses.</p></div>
        <a class="button button-light bordered" href="{{ route('farm.dashboard') }}">← Dashboard</a>
    </div>

    <div class="operation-stats daily-stats">
        <article><span>Current headcount · latest record</span><strong>{{ $latest?->flock_headcount ?? '—' }}</strong></article>
        <article><span>Eggs collected · last 7 days</span><strong>{{ $weekEggs }}</strong></article>
        <article><span>Feed used · last 7 days</span><strong>{{ rtrim(rtrim(number_format((float) $weekFeed, 3), '0'), '.') }} <small>kg</small></strong></article>
        <article><span>Deaths · last 7 days</span><strong>{{ $weekDeaths }}</strong></article>
    </div>

    <div class="operations-grid">
        <div class="panel">
            <span class="eyebrow">TODAY’S RECORD</span><h2>Log flock and production</h2>
            @if($errors->any())<div class="error-list">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('farm.daily-records.store') }}" class="form-stack">
                @csrf
                <label>Record date<input type="date" name="recorded_on" value="{{ old('recorded_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></label>
                <label>Flock headcount<input type="number" min="0" step="1" name="flock_headcount" value="{{ old('flock_headcount', $latest?->flock_headcount) }}" placeholder="Number of birds"></label>
                <label>Eggs collected<input type="number" min="0" step="1" name="eggs_collected" value="{{ old('eggs_collected') }}" placeholder="Eggs collected today"></label>
                <div class="form-columns">
                    <label>Feed used (kg)<input type="number" min="0" step="0.001" name="feed_kg" value="{{ old('feed_kg') }}"></label>
                    <label>Water used (litres)<input type="number" min="0" step="0.001" name="water_litres" value="{{ old('water_litres') }}"></label>
                </div>
                <label>Deaths today<input type="number" min="0" step="1" name="deaths" value="{{ old('deaths', 0) }}" required></label>
                <label>Notes<textarea name="notes" rows="4" maxlength="3000" placeholder="Optional observations, hatching or health notes">{{ old('notes') }}</textarea></label>
                <button class="button button-dark" type="submit">Save daily record</button>
                <small class="muted">Saving another record for the same date updates that day’s record.</small>
            </form>
        </div>

        <div class="panel">
            <div class="panel-head"><div><span class="eyebrow">FARM HISTORY</span><h2>Recent daily records</h2></div><span class="muted">Latest 60 days</span></div>
            @forelse($records as $record)
                <article class="daily-record-row">
                    <div class="daily-record-date"><strong>{{ $record->recorded_on->format('M j') }}</strong><small>{{ $record->recorded_on->format('Y') }} · {{ $record->farmer?->name }}</small></div>
                    <div class="daily-record-metrics">
                        @if($record->flock_headcount !== null)<span><b>{{ $record->flock_headcount }}</b> birds</span>@endif
                        @if($record->eggs_collected !== null)<span><b>{{ $record->eggs_collected }}</b> eggs</span>@endif
                        @if($record->feed_kg !== null)<span><b>{{ rtrim(rtrim(number_format((float)$record->feed_kg, 3), '0'), '.') }}</b> kg feed</span>@endif
                        @if($record->water_litres !== null)<span><b>{{ rtrim(rtrim(number_format((float)$record->water_litres, 3), '0'), '.') }}</b> L water</span>@endif
                        <span><b>{{ $record->deaths }}</b> deaths</span>
                    </div>
                    @if($record->notes)<p>{{ $record->notes }}</p>@endif
                </article>
            @empty
                <div class="empty-state">No daily records yet. Add today’s flock and production figures to begin your farm history.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection
