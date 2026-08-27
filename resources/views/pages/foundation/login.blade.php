@extends('layout.guest')

@section('title', 'taqbook - ' . __('Login'))

@section('content')
<div class="min-h-screen bg-page-bg flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Main Login Card -->
        <div class="bg-card-bg rounded-xl shadow-lg border border-card-border overflow-hidden">
            <!-- Header Section -->
            <div class="bg-hero-bg px-8 py-10 text-center">
                <div class="mb-6">
                    <a href="{{ config('legacy.power_url', '#') }}" target="_blank" class="inline-block transition-transform hover:scale-105">
                        <x-ui.brand-logo class="h-12 mx-auto" />
                    </a>
                </div>
                <h1 class="text-2xl font-bold text-hero-title-txt mb-2">
                    taqbook <span class="text-lg font-normal">ERP</span>
                </h1>
                <p class="text-hero-subtitle-txt text-sm">
                    {{ __('Enterprise Resource Planning') }}
                </p>
            </div>

            <!-- Form Section -->
            <div class="px-8 py-8">
                {{-- Session status (e.g. idle-timeout notice) --}}
                @if (session('status'))
                    <div class="mb-6 bg-banner-warning-bg border border-banner-warning-border text-banner-warning-txt text-sm font-semibold py-3 px-4 rounded-lg text-center">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    @error('username')
                        <p class="mt-2 text-sm text-error-accent font-semibold">{{ $message }}</p>
                    @enderror

                    <!-- Username Field -->
                    <div class="mb-5">
                        <label for="username" class="block text-sm font-semibold text-card-title-txt mb-2">
                            {{ __('User name') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="icon icon-circle-user text-xl text-field-icon"></i>
                            </div>
                            <input type="text"
                                   name="username"
                                   id="username"
                                   value="{{ old('username') }}"
                                   maxlength="30"
                                   class="w-full pl-12 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-field-focus-border focus:border-field-focus-border transition-colors text-field-emphasis-txt bg-field-bg @error('username') border-error-accent @else border-field-border @enderror"
                                   placeholder="{{ __('Enter your username') }}"
                                   autofocus>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-semibold text-card-title-txt mb-2">
                            {{ __('Password:') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="icon icon-lock text-xl text-field-icon"></i>
                            </div>
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="w-full pl-12 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-field-focus-border focus:border-field-focus-border transition-colors text-field-emphasis-txt bg-field-bg @error('password') border-error-accent @else border-field-border @enderror"
                                   placeholder="{{ __('Enter your password') }}">
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-error-accent font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full bg-button-primary-bg hover:bg-button-primary-hover-bg text-button-primary-txt font-semibold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center group">
                        <span>{{ __('Login') }}</span>
                        <i class="icon icon-login text-xl ml-3 group-hover:translate-x-1 transition-transform duration-200"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-8 text-center">
            <div class="bg-page-caption-bg border border-card-border rounded-lg px-6 py-4">
                <p class="text-page-caption-txt text-sm">
                    {{ __('Powered by') }} <span class="font-semibold text-card-title-txt">taqbook ERP</span>
                </p>
                <p class="text-page-caption-txt text-xs mt-1 flex items-center justify-center">
                    <i class="icon icon-clock text-xs mr-1"></i>
                    {{ request()->getHost() }} • {{ \Carbon\Carbon::now()->format('m/d/Y | h.i a') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
