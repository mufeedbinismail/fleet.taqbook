@extends('layout.guest')

@section('title', 'taqbook - ' . __("Logout"))

@section('content')
<div class="min-h-screen bg-page-bg flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Main Logout Card -->
        <div class="bg-card-bg rounded-xl shadow-lg border border-card-border overflow-hidden">
            <!-- Header Section -->
            <div class="bg-hero-bg px-8 py-8 text-center">
                <div class="mb-3">
                    <x-ui.brand-logo class="h-12 mx-auto" />
                </div>
            </div>

            <!-- Content Section -->
            <div class="px-8 py-10">
                <!-- Success Icon -->
                <div class="flex justify-center mb-8">
                    <div class="w-20 h-20 bg-banner-success-bg rounded-full flex items-center justify-center">
                        <i class="icon icon-circle-check text-[2.5rem] text-banner-success-txt"></i>
                    </div>
                </div>

                <!-- Main Message -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-card-title-txt mb-4">
                        {{ __("Successfully Logged Out") }}
                    </h2>
                    <p class="text-card-txt mb-2">
                        {{ __("Thank you for using") }}
                    </p>
                    <p class="text-lg font-semibold text-card-title-txt">
                        taqbook <span class="text-base font-normal text-card-txt">{{ config('app.version', '2.5') }}</span>
                    </p>
                </div>

                <!-- Security Notice -->
                <div class="bg-card-sunken-bg border-l-4 border-primary-accent p-4 rounded-r-lg mb-8">
                    <div class="flex items-center">
                        <i class="icon icon-lock text-xl text-primary-accent mr-3 flex-shrink-0"></i>
                        <div>
                            <p class="font-medium text-card-title-txt text-sm">
                                {{ __("Session Securely Terminated") }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <a href="{{ route('login') }}"
                       class="w-full bg-button-primary-bg hover:bg-button-primary-hover-bg text-button-primary-txt font-semibold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center group">
                        <i class="icon icon-login text-xl mr-3 group-hover:translate-x-1 transition-transform duration-200"></i>
                        {{ __("Login Again") }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-8 text-center">
            <div class="bg-page-caption-bg border border-card-border rounded-lg px-6 py-4">
                <p class="text-page-caption-txt text-sm">
                    {{ __("Powered by") }} <span class="font-semibold text-card-title-txt">taqbook ERP</span>
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