const API_BASE = '/api/auth';

const bufferToBase64Url = (buffer) => {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
};

const base64UrlToBuffer = (base64url) => {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const padding = '='.repeat((4 - base64.length % 4) % 4);
    return Uint8Array.from(atob(base64 + padding), c => c.charCodeAt(0)).buffer;
};

const encodeCredentialForServer = (credential, isRegistration = true) => ({
    id: credential.id,
    rawId: bufferToBase64Url(credential.rawId),
    type: credential.type,
    clientExtensionResults: credential.getClientExtensionResults(),
    response: isRegistration ? {
        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
        attestationObject: bufferToBase64Url(credential.response.attestationObject),
    } : {
        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
        authenticatorData: bufferToBase64Url(credential.response.authenticatorData),
        signature: bufferToBase64Url(credential.response.signature),
        userHandle: credential.response.userHandle
            ? bufferToBase64Url(credential.response.userHandle)
            : null,
    }
});

// token management 

const TokenManager = {
    save: (token, refreshToken) => {
        localStorage.setItem('jwt_token', token);
        localStorage.setItem('refresh_token', refreshToken);
    },
    clear: () => {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('refresh_token');
    },
    getToken: () => localStorage.getItem('jwt_token'),
    getRefreshToken: () => localStorage.getItem('refresh_token'),
    isAuthenticated: () => !!localStorage.getItem('jwt_token'),
};

// api helpers 

const apiFetch = async (url, options = {}) => {
    const token = TokenManager.getToken();
    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers ?? {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };

    const response = await fetch(url, { ...options, headers });

    if (response.status === 401) {
        const refreshed = await refreshToken();
        if (refreshed) return apiFetch(url, options);
        TokenManager.clear();
        throw new Error('Session expired. Please log in again.');
    }

    return response;
};

// classic auth email +mdp 

const registerWithPassword = async (email, password) => {
    const res = await apiFetch(`${API_BASE}/register`, {
        method: 'POST',
        body: JSON.stringify({ email, password }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Registration failed.');
    TokenManager.save(data.token, data.refresh_token);
    return data;
};

const loginWithPassword = async (email, password) => {
    const res = await apiFetch(`${API_BASE}/login`, {
        method: 'POST',
        body: JSON.stringify({ email, password }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Login failed.');
    TokenManager.save(data.token, data.refresh_token);
    return data;
};

// passkey registration 

const registerPasskey = async (email, credentialName = 'My Passkey') => {
    if (!window.PublicKeyCredential) {
        throw new Error('WebAuthn is not supported in this browser.');
    }

    const optRes = await apiFetch(`${API_BASE}/passkey/register/options`, {
        method: 'POST',
        body: JSON.stringify({ email }),
    });
    const options = await optRes.json();
    if (!optRes.ok) throw new Error(options.error ?? 'Failed to get registration options.');

    const credential = await navigator.credentials.create({
        publicKey: {
            ...options,
            challenge: base64UrlToBuffer(options.challenge),
            user: {
                ...options.user,
                id: base64UrlToBuffer(options.user.id),
            },
            excludeCredentials: (options.excludeCredentials ?? []).map(c => ({
                ...c,
                id: base64UrlToBuffer(c.id),
            })),
        },
    });

    const verifyRes = await apiFetch(`${API_BASE}/passkey/register/verify`, {
        method: 'POST',
        body: JSON.stringify({
            email,
            name: credentialName,
            credential: encodeCredentialForServer(credential, true),
        }),
    });
    const result = await verifyRes.json();
    if (!verifyRes.ok) throw new Error(result.error ?? 'Passkey registration failed.');

    TokenManager.save(result.token, result.refresh_token);
    return result;
};

// passkey login 

const loginWithPasskey = async () => {
    if (!window.PublicKeyCredential) {
        throw new Error('WebAuthn is not supported in this browser.');
    }

    const optRes = await apiFetch(`${API_BASE}/passkey/login/options`, {
        method: 'POST',
    });
    const options = await optRes.json();
    if (!optRes.ok) throw new Error(options.error ?? 'Failed to get login options.');

    const assertion = await navigator.credentials.get({
        publicKey: {
            ...options,
            challenge: base64UrlToBuffer(options.challenge),
            allowCredentials: (options.allowCredentials ?? []).map(c => ({
                ...c,
                id: base64UrlToBuffer(c.id),
            })),
        },
    });

    const verifyRes = await apiFetch(`${API_BASE}/passkey/login/verify`, {
        method: 'POST',
        body: JSON.stringify({
            credential: encodeCredentialForServer(assertion, false),
        }),
    });
    const result = await verifyRes.json();
    if (!verifyRes.ok) throw new Error(result.error ?? 'Passkey login failed.');

    TokenManager.save(result.token, result.refresh_token);
    return result;
};

// refresh token 

const refreshToken = async () => {
    const refresh = TokenManager.getRefreshToken();
    if (!refresh) return false;

    const res = await fetch('/api/token/refresh', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh_token: refresh }),
    });

    if (!res.ok) {
        TokenManager.clear();
        return false;
    }

    const data = await res.json();
    TokenManager.save(data.token, data.refresh_token ?? refresh);
    return true;
};

// logout 

const logout = () => {
    TokenManager.clear();
    window.location.href = '/';
};

// get current user

const getCurrentUser = async () => {
    const res = await apiFetch(`${API_BASE}/me`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Failed to get user.');
    return data;
};

export {
    TokenManager,
    apiFetch,
    registerWithPassword,
    loginWithPassword,
    registerPasskey,
    loginWithPasskey,
    refreshToken,
    logout,
    getCurrentUser,
};
