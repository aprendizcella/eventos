{{-- Inline script to prevent FOUC. Must run before first paint. --}}
<script>
    (function() {
        var theme = localStorage.getItem('theme') || 'system';
        var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (isDark) document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', theme);
    })();
</script>
