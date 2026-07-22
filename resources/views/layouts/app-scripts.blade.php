<script>
    // Configuration bridge for external app-scripts.js
    window.AppConfig = {
        sessionSuccess: @json(session('success')),
        idleTimeoutSeconds: {{ config('session.idle_timeout', 30) }} * 60,
        casLoginUrl: @json(route('cas.login')),
        dashboardUrl: @json(url('/dashboard')),
        isAuthenticated: @json(auth()->check())
    };
</script>
<script src="{{ asset('js/app-scripts.js') }}?v={{ time() }}"></script>
