{{--
    Reachable for the first time now that mutations run on 'web', which — unlike legacy.web —
    verifies the CSRF token.
--}}
@extends('errors.layout', [
    'code' => 419,
    'icon' => 'icon-clock',
    'accentText' => 'text-banner-warning-txt',
    'accentBg' => 'bg-banner-warning-bg',
    'heading' => __("This page has expired"),
    'message' => __("You had it open long enough for its security token to lapse. Reload to get a fresh one, then make your change again."),
])

@section('actions')
    {{-- location.reload() would resubmit the request that just failed; assigning href re-GETs it. --}}
    <button type="button" onclick="window.location.href = window.location.href"
            class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-button-outline-border bg-button-outline-bg px-4 py-2 text-sm font-semibold text-button-outline-txt transition hover:bg-button-outline-hover-bg">
        <span class="icon icon-refresh"></span>
        {{ __("Reload the page") }}
    </button>
@endsection
