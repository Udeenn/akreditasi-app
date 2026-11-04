<script>
    // Configuration bridge for external app-scripts.js
    window.AppConfig = {
        sessionSuccess: @json(session('success')),
        idleTimeoutSeconds: {{ config('session.idle_timeout', 30) }} * 60,
        casLoginUrl: @json(route('cas.login', ['renew' => 'true'])),
        logoutUrl: @json(route('logout')),
        dashboardUrl: @json(url('/dashboard')),
        isAuthenticated: @json(auth()->check())
    };
    window.AppSvgIcons = {!! \App\Helpers\SvgHelper::getAllIconsJson() !!};
</script>
<script src="{{ asset('js/app-scripts.js') }}?v={{ time() }}"></script>
