@extends('layout.guest')

@section('title', 'taqbook - ' . __("Logout"))

@section('content')
<div class="min-h-screen bg-main-bg flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Main Logout Card -->
        <div class="bg-white rounded-xl shadow-lg border border-soft-border overflow-hidden">
            <!-- Header Section -->
            <div class="bg-header-bg px-8 py-10 text-center">
                <div class="mb-6">
                    <img src="{{ url('/themes/'.user_theme().'/images/logo.svg') }}" alt="taqbook ERP" class="h-12 mx-auto">
                </div>
                <h1 class="text-2xl font-bold text-header-txt mb-2">
                    taqbook <span class="text-lg font-normal">ERP</span>
                </h1>
                <p class="text-header-txt opacity-75 text-sm">
                    {{ __("Enterprise Resource Planning") }}
                </p>
            </div>

            <!-- Content Section -->
            <div class="px-8 py-10">
                <!-- Success Icon -->
                <div class="flex justify-center mb-8">
                    <div class="w-20 h-20 bg-success-accent bg-opacity-10 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-success-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Main Message -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-primary-txt mb-4">
                        {{ __("Successfully Logged Out") }}
                    </h2>
                    <p class="text-general-txt mb-2">
                        {{ __("Thank you for using") }}
                    </p>
                    <p class="text-lg font-semibold text-primary-txt">
                        taqbook <span class="text-base font-normal text-general-txt">{{ $version ?? config('app.version', '2.5') }}</span>
                    </p>
                </div>

                <!-- Security Notice -->
                <div class="bg-table-bg border-l-4 border-primary-accent p-4 rounded-r-lg mb-8">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-primary-accent mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <div>
                            <p class="font-medium text-primary-txt text-sm">
                                {{ __("Session Securely Terminated") }}
                            </p>
                            <p class="text-general-txt text-sm mt-1">
                                {{ __("Your session has been safely closed and all data is protected.") }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <a href="{{ url('/index.php') }}" 
                       class="w-full bg-primary-accent hover:bg-primary-accent hover:opacity-90 text-white font-semibold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center group">
                        <svg class="w-5 h-5 mr-3 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        {{ __("Login Again") }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-8 text-center">
            <div class="bg-footer-bg border border-soft-border rounded-lg px-6 py-4">
                <p class="text-footer-txt text-sm">
                    {{ __("Powered by") }} <span class="font-semibold text-primary-txt">taqbook ERP</span>
                </p>
                <p class="text-footer-txt text-xs mt-1 flex items-center justify-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ request()->getHost() }} • {{ Today() }} • {{ Now() }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection