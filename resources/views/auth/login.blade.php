@extends('layout.guest')

@section('title', $login_timeout ? __('Authorization timeout') : 'taqbook - ' . __("Login"))

@section('content')
<div class="min-h-screen bg-main-bg flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Main Login Card -->
        <div class="bg-white rounded-xl shadow-lg border border-soft-border overflow-hidden">
            <!-- Header Section -->
            <div class="bg-header-bg px-8 py-10 text-center">
                <div class="mb-6">
                    @if (!$login_timeout)
                        <a href="{{ $SysPrefs->power_url }}" target="_blank" class="inline-block transition-transform hover:scale-105">
                            <img src="{{ url('/themes/'.user_theme().'/images/logo.svg') }}" alt="taqbook ERP" class="h-12 mx-auto">
                        </a>
                    @else
                        <div class="w-20 h-20 bg-warning-accent bg-opacity-10 rounded-full flex items-center justify-center mx-auto">
                            <svg class="w-10 h-10 text-warning-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                    @endif
                </div>
                <h1 class="text-2xl font-bold text-header-txt mb-2">
                    @if ($login_timeout)
                        {{ __('Authorization timeout') }}
                    @else
                        taqbook <span class="text-lg font-normal">ERP</span>
                    @endif
                </h1>
                @if (!$login_timeout)
                <p class="text-header-txt opacity-75 text-sm">
                    {{ __("Enterprise Resource Planning") }}
                </p>
                @endif
            </div>

            <!-- Form Section -->
            <div class="px-8 py-8">
                <form method="POST" action="{{ session('timeout')['uri'] }}" name="loginform" id="loginform">
                    @if ($allow)
                        <!-- Username Field -->
                        <div class="mb-5">
                            <label for="user_name_entry_field" class="block text-sm font-semibold text-primary-txt mb-2">
                                {{ __("User name") }}
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-general-txt" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <input type="text" 
                                       name="user_name_entry_field" 
                                       id="user_name_entry_field"
                                       value="{{ $username }}"
                                       maxlength="30"
                                       class="w-full pl-12 pr-4 py-3 border border-input-border rounded-lg focus:ring-2 focus:ring-primary-accent focus:border-primary-accent transition-colors text-primary-txt bg-white"
                                       placeholder="{{ __('Enter your username') }}"
                                       autofocus>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="mb-5">
                            <label for="password" class="block text-sm font-semibold text-primary-txt mb-2">
                                {{ __("Password:") }}
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-general-txt" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <input type="password" 
                                       name="password" 
                                       id="password"
                                       value="{{ $password }}"
                                       class="w-full pl-12 pr-4 py-3 border border-input-border rounded-lg focus:ring-2 focus:ring-primary-accent focus:border-primary-accent transition-colors text-primary-txt bg-white"
                                       placeholder="{{ __('Enter your password') }}">
                            </div>
                        </div>

                        <!-- Company Selection -->
                        <input type="hidden" name="company_login_name" value="{{ $coy }}">
                    @endif

                    <!-- Status Message -->
                    <div class="mb-6">
                        <div id="log_msg" class="text-center py-3 px-4 rounded-lg {{ $blocked || (session('wa_current_user')->login_attempt ?? 0) > 1 ? 'bg-error-accent bg-opacity-10 border border-error-accent' : 'bg-table-bg' }}">
                            @if (!empty($demo_text))
                                <div class="text-sm {{ $blocked || (session('wa_current_user')->login_attempt ?? 0) > 1 ? 'text-error-accent font-semibold' : 'text-general-txt' }} [&_a]:text-primary-accent [&_a]:underline [&_a]:font-semibold [&_a]:hover:opacity-80 [&_a]:transition-opacity">
                                    {!! $demo_text !!}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" id="ui_mode" name="ui_mode" value="{{ !fallback_mode() ? '1' : '0' }}">
                    
                    @foreach(session('timeout')['post'] as $p => $val)
                        @if (!in_array($p, ['ui_mode', 'user_name_entry_field', 'password', 'SubmitUser', 'company_login_name']))
                            @if (!is_array($val))
                                <input type="hidden" name="{{ $p }}" value="{{ $val }}">
                            @else
                                @foreach($val as $i => $v)
                                    <input type="hidden" name="{{ $p }}[{{ $i }}]" value="{{ $v }}">
                                @endforeach
                            @endif
                        @endif
                    @endforeach

                    <!-- Submit Button -->
                    @if ($allow)
                        <button type="submit" 
                                name="SubmitUser"
                                {{ $blocked ? 'disabled' : '' }}
                                onclick="{{ in_ajax() ? 'retry();' : 'set_fullmode();' }}"
                                class="w-full bg-primary-accent hover:bg-primary-accent hover:opacity-90 text-white font-semibold py-4 px-6 rounded-lg transition-all duration-200 flex items-center justify-center group {{ $blocked ? 'opacity-50 cursor-not-allowed' : '' }}">
                            <span>{{ __("Login") }}</span>
                            <svg class="w-5 h-5 ml-3 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </button>
                    @endif
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

@if (!$login_timeout && !isset($blocked))
<script>
function defaultCompany() {
    const companySelect = document.forms[0]?.company_login_name;
    if (companySelect && companySelect.options) {
        companySelect.options[{{ user_company() }}].selected = true;
    }
}

// Set default company on load
defaultCompany();

// Focus on username field
if (document.forms.length && document.forms[0].user_name_entry_field) {
    document.forms[0].user_name_entry_field.select();
    document.forms[0].user_name_entry_field.focus();
}
</script>
@endif

@if ($blocked)
<script>
setTimeout(function() {
    const submitBtn = document.getElementsByName('SubmitUser')[0];
    const logMsg = document.getElementById('log_msg');
    if (submitBtn) submitBtn.disabled = false;
    if (logMsg) logMsg.innerHTML = {!! json_encode($original_demo_text) !!};
}, 1000 * {{ $SysPrefs->login_delay }});
</script>
@endif
@endsection

