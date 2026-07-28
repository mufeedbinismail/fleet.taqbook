@extends('layout.base')

@section('title', 'taqbook - '.$heading)

@section('body')
<div class="flex min-h-screen items-center justify-center bg-main-bg p-4">
    <div class="w-full max-w-lg">
        <div class="overflow-hidden rounded-xl border border-soft-border bg-white shadow-lg">
            <div class="flex flex-col items-center gap-4 px-8 pb-6 pt-10 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full {{ $accentBg }}">
                    <span class="icon {{ $icon }} text-3xl {{ $accentText }}"></span>
                </span>

                <p class="text-5xl font-bold tabular-nums {{ $accentText }}">{{ $code }}</p>

                <h1 class="text-xl font-semibold text-primary-txt">{{ $heading }}</h1>

                <p class="text-sm leading-relaxed text-general-txt">{{ $message }}</p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2 border-t border-soft-border bg-label-bg px-8 py-4">
                @yield('actions')

                <a href="{{ url('/index.php') }}"
                   class="inline-flex items-center gap-2 rounded-lg border-0 bg-primary-accent px-4 py-2 text-sm font-semibold text-white no-underline transition hover:opacity-90">
                    <span class="icon icon-statistics"></span>
                    {{ __("Go to dashboard") }}
                </a>
            </div>
        </div>

        <p class="mt-6 flex items-center justify-center gap-1 text-center text-xs text-footer-txt">
            <span class="icon icon-clock text-xs"></span>
            {{ request()->getHost() }} • {{ \Carbon\Carbon::now()->format('m/d/Y | h.i a') }}
        </p>
    </div>
</div>
@endsection
