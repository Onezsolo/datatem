@auth
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const target = @json(auth()->user()->role === 'admin' ? '/admin/dashboard' : '/dashboard');

        if (localStorage.getItem('token')) {
            window.location.replace(target);
            return;
        }

        try {
            const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
            const response = await fetch('/auth/session-token', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': match ? decodeURIComponent(match[1]) : ''
                }
            });

            if (response.ok) {
                const data = await response.json();
                localStorage.setItem('auth_storage', 'local');
                localStorage.setItem('token', data.access_token);
                localStorage.setItem('user', JSON.stringify(data.user));
            }
        } catch (error) {
            /* stay on this page instead of looping */
        }

        if (localStorage.getItem('token')) {
            window.location.replace(target);
        }
    });
</script>
@endauth
