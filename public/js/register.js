import { registerWithPassword, registerPasskey } from '/js/auth.js';

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

const setLoading = (button, isLoading, text = 'Chargement...') => {
    if (isLoading) {
        button.disabled = true;
        button.innerHTML = `<span class="spinner"></span> ${text}`;
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.original;
    }
};

const passwordInput = document.getElementById('password');
const strengthBars = document.querySelectorAll('.strength-bar');

passwordInput.addEventListener('input', () => {
    const password = passwordInput.value;
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/) && password.match(/[^a-zA-Z0-9]/)) strength++;
    
    strengthBars.forEach((bar, index) => {
        bar.classList.remove('weak', 'medium', 'strong', 'active');
        if (index < strength) {
            bar.classList.add('active');
            if (strength === 1) bar.classList.add('weak');
            else if (strength === 2) bar.classList.add('medium');
            else if (strength === 3) bar.classList.add('strong');
        }
    });
});

const validateForm = (email, password, confirm) => {
    if (!email) return 'Email requis';
    if (!email.includes('@') || !email.includes('.')) return 'Email invalide';
    if (password.length < 6) return 'Mot de passe trop court (minimum 6 caractères)';
    if (password !== confirm) return 'Les mots de passe ne correspondent pas';
    return null;
};

document.addEventListener('DOMContentLoaded', () => {
    const btnReg = document.getElementById('btn-register');
    const btnPass = document.getElementById('btn-register-passkey');
    
    btnReg.dataset.original = btnReg.innerHTML;
    btnPass.dataset.original = btnPass.innerHTML;

    btnReg.addEventListener('click', async () => {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm').value;

        const error = validateForm(email, password, confirm);
        if (error) {
            showAlert(error);
            return;
        }

        setLoading(btnReg, true, 'Création...');

        try {
            await registerWithPassword(email, password);
            showAlert('Compte créé avec succès ! Redirection...', 'success');
            setTimeout(() => window.location.href = '/events', 1500);
        } catch (error) {
            showAlert(error.message || 'Erreur lors de la création du compte');
            setLoading(btnReg, false);
        }
    });

    btnPass.addEventListener('click', async () => {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm').value;

        const error = validateForm(email, password, confirm);
        if (error) {
            showAlert(error);
            return;
        }

        setLoading(btnPass, true, 'Création...');

        try {
            await registerWithPassword(email, password);
            await registerPasskey(email);
            showAlert('Compte créé avec Passkey ! Redirection...', 'success');
            setTimeout(() => window.location.href = '/events', 1500);
        } catch (error) {
            showAlert(error.message || 'Erreur lors de la création avec Passkey');
            setLoading(btnPass, false);
        }
    });
});