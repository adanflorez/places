// Única línea a cambiar al migrar de backend
const API_BASE = '/api';

// Helpers de autenticación
const Auth = {
    getToken: ()       => localStorage.getItem('palatin_token'),
    getUser:  ()       => JSON.parse(localStorage.getItem('palatin_user') || '{}'),
    isLogged: ()       => !!localStorage.getItem('palatin_token'),
    save: (token, user) => {
        localStorage.setItem('palatin_token', token);
        localStorage.setItem('palatin_user', JSON.stringify(user));
    },
    clear: () => {
        localStorage.removeItem('palatin_token');
        localStorage.removeItem('palatin_user');
    },
    logout: () => {
        Auth.clear();
        window.location.href = 'index.html';
    },
    require: () => {
        if (!Auth.isLogged()) window.location.href = 'index.html';
    },
};

// Wrapper de fetch con JWT automático
async function api(method, path, body = null) {
    const headers = { 'Content-Type': 'application/json' };
    const token   = Auth.getToken();
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const options = { method, headers };
    if (body) options.body = JSON.stringify(body);

    const res = await fetch(`${API_BASE}${path}`, options);

    if (res.status === 401) { Auth.logout(); return; }

    return res.json();
}
