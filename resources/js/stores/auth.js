const API = '/api';

const storage = () =>
    localStorage.getItem('auth_storage') === 'session' ? sessionStorage : localStorage;

let initialUser = {};

try {
    initialUser = JSON.parse(storage().getItem('user') || '{}');
} catch (error) {
    initialUser = {};
}

export function createAuthStore() {
    return {
        getStorage() {
            return storage();
        },
        user: initialUser,
        token: storage().getItem('token'),
        _refreshTimer: null,
        refreshing: false,
        async refreshToken() {
            const token = this.token || this.getStorage().getItem('token');
            if (!token) return false;

            try {
                const response = await fetch(`${API}/auth/refresh`, {
                    method: 'POST',
                    headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
                });
                const data = await response.json().catch(() => ({}));

                if (response.ok && data.access_token) {
                    this.token = data.access_token;
                    if (data.user) this.user = data.user;

                    const store = this.getStorage();
                    store.setItem('token', this.token);
                    store.setItem('user', JSON.stringify(this.user));
                    this.scheduleRefresh();

                    return true;
                }
            } catch (error) {
                /* ignore */
            }

            return false;
        },
        scheduleRefresh() {
            if (this._refreshTimer) clearTimeout(this._refreshTimer);
            this._refreshTimer = null;

            const token = this.token || this.getStorage().getItem('token');
            if (!token) return;

            try {
                const payload = JSON.parse(atob(token.split('.')[1]));
                const expiry = (payload.exp || 0) * 1000;
                const remaining = expiry - Date.now();
                const delay = Math.max(60000, remaining - 5 * 60 * 1000);

                if (remaining < 10 * 60 * 1000) {
                    this.refreshToken();

                    return;
                }

                this._refreshTimer = setTimeout(() => this.refreshToken(), delay);
            } catch (error) {
                /* ignore malformed tokens */
            }
        },
        async refreshUser() {
            const token = this.token || this.getStorage().getItem('token');
            if (!token || this.refreshing) return;

            this.refreshing = true;

            try {
                const response = await fetch(`${API}/auth/user-profile`, {
                    headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
                });

                if (response.ok) {
                    const updatedUser = await response.json();
                    this.user = updatedUser;
                    this.getStorage().setItem('user', JSON.stringify(updatedUser));
                    this.scheduleRefresh();

                    if (!['admin', 'developer'].includes(updatedUser.role) && window.location.pathname.startsWith('/admin')) {
                        window.location.href = '/dashboard';
                    }
                } else if (response.status === 401) {
                    if (!(await this.refreshToken())) this.logout();
                    else await this.refreshUser();
                }
            } catch (error) {
                console.error('Failed to refresh user profile:', error);
            } finally {
                this.refreshing = false;
            }
        },
        login(user, token, keepMeSignedIn = true) {
            localStorage.setItem('auth_storage', keepMeSignedIn ? 'local' : 'session');
            const store = keepMeSignedIn ? localStorage : sessionStorage;
            store.setItem('user', JSON.stringify(user));
            store.setItem('token', token);

            if (!keepMeSignedIn) {
                localStorage.removeItem('user');
                localStorage.removeItem('token');
            }

            this.user = user;
            this.token = token;
            this.scheduleRefresh();
        },
        async logout() {
            if (this._refreshTimer) clearTimeout(this._refreshTimer);
            this._refreshTimer = null;

            try {
                const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
                await fetch('/logout', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': match ? decodeURIComponent(match[1]) : ''
                    }
                });
            } catch (error) {
                /* clear local state even if the server call fails */
            }

            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('auth_storage');
            sessionStorage.removeItem('token');
            sessionStorage.removeItem('user');
            this.user = {};
            this.token = null;
            window.location.href = '/login';
        },
    };
}
