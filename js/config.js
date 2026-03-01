const API_BASE = '/api';

async function api(method, path) {
    const res = await fetch(`${API_BASE}${path}`, { method });
    if (!res.ok) return null;
    return res.json();
}
