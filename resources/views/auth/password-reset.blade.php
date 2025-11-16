@extends('layout.guest')

@section('title', 'taqbook - ' . __("Password reset"))

@section('content')
<div class="min-h-screen bg-main-bg flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Main Password Reset Card -->
        <div class="bg-white rounded-xl shadow-lg border border-soft-border overflow-hidden">
            <!-- Header Section -->
            <div class="bg-header-bg px-8 py-10 text-center">
                <div class="mb-6">
                    <a href="{{ $SysPrefs->power_url }}" target="_blank" class="inline-block transition-transform hover:scale-105">
                        <img src="{{ url('/themes/'.user_theme().'/images/logo.svg') }}" alt="taqbook ERP" class="h-12 mx-auto">
                    </a>
                </div>
                <h1 class="text-2xl font-bold text-header-txt mb-2">
                    {{ __("Password reset") }}
                </h1>
                <p class="text-header-txt opacity-75 text-sm">
                    {{ __("Enter your email to receive a new password") }}
                </p>
            </div>

            <!-- Form Section -->
            <div class="px-8 py-8">
                <form method="POST" action="{{ session('timeout')['uri'] ?? '' }}" name="resetform" id="resetform">
                    <!-- Email Field -->
                    <div class="mb-5">
                        <label for="email_entry_field" class="block text-sm font-semibold text-primary-txt mb-2">
                            {{ __("Email") }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-general-txt" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <input type="email" 
                                   name="email_entry_field" 
                                   id="email_entry_field"
                                   maxlength="100"
                                   class="w-full pl-12 pr-4 py-3 border border-input-border rounded-lg focus:ring-2 focus:ring-primary-accent focus:border-primary-accent transition-colors text-primary-txt bg-white"
                                   placeholder="{{ __('Enter your email address') }}"
                                   required
                                   autofocus>
                        </div>
                    </div>

                    <input type="hidden" name="company_login_name" value="{{ $coy }}">

                    <!-- Info Message -->
                    <div class="mb-6">
                        <div class="bg-primary-accent bg-opacity-10 border-l-4 border-primary-accent p-4 rounded-r-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-primary-accent mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-primary-txt text-sm font-medium">
                                        {{ __("Please enter your email address") }}
                                    </p>
                                    <p class="text-general-txt text-sm mt-1">
                                        {{ __("We'll send you a new password to your registered email address.") }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" id="ui_mode" name="ui_mode" value="{{ fallback_mode() }}">

                    <!-- Submit Button -->
                    <div class="space-y-4">
                        <button type="submit" 
                                name="SubmitReset"
                                onclick="set_fullmode();"
                                class="w-full bg-primary-accent hover:bg-primary-accent hover:opacity-90 text-white font-semibold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center group">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                            </svg>
                            <span>{{ __("Send password") }}</span>
                            <svg class="w-5 h-5 ml-3 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </button>

                        <a href="{{ url('/index.php') }}" 
                           class="w-full border border-soft-border hover:bg-table-bg text-primary-txt font-semibold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center group">
                            <svg class="w-5 h-5 mr-3 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            {{ __("Back to Login") }}
                        </a>
                    </div>
                </form>
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
                    {{ request()->getHost() }} • {{ $date }}
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function defaultCompany() {
    const companySelect = document.forms[0]?.company_login_name;
    if (companySelect && companySelect.options) {
        companySelect.options[{{ user_company() }}].selected = true;
    }
}

// Set default company on load
defaultCompany();

// Focus on email field
if (document.forms.length && document.forms[0].email_entry_field) {
    document.forms[0].email_entry_field.select();
    document.forms[0].email_entry_field.focus();
}
</script>
@endsection

