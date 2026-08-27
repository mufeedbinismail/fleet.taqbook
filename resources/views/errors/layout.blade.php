@extends('layout.base')

@section('title', 'taqbook - '.$heading)

@section('body')
<div class="flex min-h-screen items-center justify-center bg-page-bg p-4">
    <div class="w-full max-w-lg">
        <div class="overflow-hidden rounded-xl border border-card-border bg-card-bg shadow-lg">
            <div class="flex flex-col items-center gap-4 px-8 pb-6 pt-10 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full {{ $accentBg }}">
                    <span class="icon {{ $icon }} text-3xl {{ $accentText }}"></span>
                </span>

                <p class="text-5xl font-bold tabular-nums {{ $accentText }}">{{ $code }}</p>

                <h1 class="text-xl font-semibold text-card-title-txt">{{ $heading }}</h1>

                <p class="text-sm leading-relaxed text-card-txt">{{ $message }}</p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2 border-t border-card-divider bg-card-footer-bg px-8 py-4">
                @yield('actions')

                <a href="{{ legacy_url('/index.php') }}"
                   class="inline-flex items-center gap-2 rounded-lg border-0 bg-button-primary-bg px-4 py-2 text-sm font-semibold text-button-primary-txt no-underline transition hover:bg-button-primary-hover-bg">
                    <span class="icon icon-statistics"></span>
                    {{ __("Go to dashboard") }}
                </a>
            </div>
        </div>

        <p class="mt-6 flex items-center justify-center gap-1 text-center text-xs text-page-caption-txt">
            <span class="icon icon-clock text-xs"></span>
            {{ request()->getHost() }} • {{ \Carbon\Carbon::now()->format('m/d/Y | h.i a') }}
        </p>
    </div>
</div>
@endsection
