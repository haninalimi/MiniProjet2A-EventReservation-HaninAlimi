import { loginWithPassword, loginWithPasskey } from '/js/auth.js';

window.switchTab = (tab) => {
    const isPassword = tab === 'password';
    
    document.getElementById('panel-password').style.display = isPassword ? 'block' : 'none';
    document.getElementById('panel-passkey').style.display = isPassword ? 'none' : 'block';
    
    const tabPassword = document.getElementById('tab-password');
    const tabPasskey = document.getElementById('tab-passkey');
    
    if (isPassword) {
        tabPassword.classList.add('active');
        tabPasskey.classList.remove('active');
    } else {
        tabPassword.classList.remove('active');
        tabPasskey.classList.add('active');
    }
};

const showAlert = (message, type = 'error') => {
    const alertBox = document.getElementById('alert-box');
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    alertBox.innerHTML = `
        <div class="alert-message alert-${type === 'success' ? 'success' : ''}">
            <i class="bi bi-${icon}"></i>
            ${message}
        </div>
    `;
};

const setLoading = (button, isLoading) => {
    const originalContent = button.innerHTML;
    if (isLoading) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner"></span> Connexion...';
    } else {
        button.disabled = false;
        button.innerHTML = originalContent;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const btnLogin = document.getElementById('btn-login');
    const btnPasskey = document.getElementById('btn-passkey');

    btnLogin.addEventListener('click', async () => {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if (!email || !password) {
            showAlert('Veuillez remplir tous les champs');
            return;
        }

        setLoading(btnLogin, true);
        
        try {
            await loginWithPassword(email, password);
            showAlert('Connexion réussie ! Redirection...', 'success');
            setTimeout(() => window.location.href = '/events', 1000);
        } catch (error) {
            showAlert(error.message || 'Erreur de connexion');
            setLoading(btnLogin, false);
        }
    });

    btnPasskey.addEventListener('click', async () => {
        setLoading(btnPasskey, true);
        
        try {
            await loginWithPasskey();
            showAlert('Connexion réussie ! Redirection...', 'success');
            setTimeout(() => window.location.href = '/events', 1000);
        } catch (error) {
            showAlert(error.message || 'Erreur de connexion biométrique');
            setLoading(btnPasskey, false);
        }
    });
});