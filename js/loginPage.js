const form = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const emailError = document.getElementById('emailError');
const passwordError = document.getElementById('passwordError');
const captchaError = document.getElementById('captchaError');
const loginButton = document.getElementById('loginButton');

// Disable button by default
loginButton.disabled = true;
loginButton.style.opacity = '0.5';
loginButton.style.cursor = 'not-allowed';

// Removed validateEmail() — normal email only

// Password validation
function validatePassword(password) {
    if (password.length < 8) {
        return "Password must be at least 8 characters";
    }
    if (!/[A-Z]/.test(password)) {
        return "Password must contain at least one uppercase letter";
    }
    if (!/[!@#$%^&*(),.?\":{}|<>]/.test(password)) {
        return "Password must contain at least one special character";
    }
    return true;
}

function checkFormValidity() {
    const emailFilled = emailInput.value.trim().length > 0;
    const passwordFilled = passwordInput.value.trim().length > 0;
    let captchaValid = false;

    if (typeof grecaptcha !== 'undefined') {
        try {
            const captchaResponse = grecaptcha.getResponse();
            captchaValid = captchaResponse.length > 0;
        } catch (e) {
            captchaValid = false;
        }
    }

    if (emailFilled && passwordFilled && captchaValid) {
        loginButton.disabled = false;
        loginButton.style.opacity = '1';
        loginButton.style.cursor = 'pointer';
    } else {
        loginButton.disabled = true;
        loginButton.style.opacity = '0.5';
        loginButton.style.cursor = 'not-allowed';
    }
}

window.onCaptchaSuccess = function() {
    checkFormValidity();
};

emailInput.addEventListener('blur', function() {
    emailError.classList.remove('show');
    this.style.borderColor = '#e5e7eb';
});

// Password validation
passwordInput.addEventListener('blur', function() {
    if (this.value) {
        const validation = validatePassword(this.value);
        if (validation !== true) {
            passwordError.textContent = validation;
            passwordError.classList.add('show');
            this.style.borderColor = '#b44040ff';
        } else {
            passwordError.classList.remove('show');
            this.style.borderColor = '#e5e7eb';
        }
    }
});

emailInput.addEventListener('input', function() {
    emailError.classList.remove('show');
    this.style.borderColor = '#e5e7eb';
    checkFormValidity();
});

passwordInput.addEventListener('input', function() {
    passwordError.classList.remove('show');
    this.style.borderColor = '#e5e7eb';
    checkFormValidity();
});

setInterval(checkFormValidity, 500);

form.addEventListener('submit', function(e) {
    let isValid = true;

    emailError.classList.remove('show');
    passwordError.classList.remove('show');
    captchaError.classList.remove('show');

    if (!emailInput.value) {
        emailError.textContent = 'Please enter your email';
        emailError.classList.add('show');
        emailInput.style.borderColor = '#dc2626';
        isValid = false;
    }

    if (!passwordInput.value || passwordInput.value.length < 6) {
        passwordError.textContent = 'Please enter your password';
        passwordError.classList.add('show');
        passwordInput.style.borderColor = '#dc2626';
        isValid = false;
    }

    if (typeof grecaptcha !== 'undefined') {
        const captchaResponse = grecaptcha.getResponse();
        if (captchaResponse.length === 0) {
            captchaError.textContent = 'Please complete the reCAPTCHA';
            captchaError.classList.add('show');
            isValid = false;
        }
    }

    if (!isValid) {
        e.preventDefault();
        return false;
    }

    loginButton.textContent = 'Signing In...';
    loginButton.disabled = true;
});
