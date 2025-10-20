const form = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const emailError = document.getElementById('emailError');
const passwordError = document.getElementById('passwordError');
const captchaError = document.getElementById('captchaError');

// Email validation for role-based format
function validateEmail(email) {
    const pattern = /^(aq|op|sa)\.[a-zA-Z]+@carmax\.com$/;
    return pattern.test(email);
}

// Password validation
function validatePassword(password) {
    if (password.length < 8) {
        return "Password must be at least 8 characters";
    }
    if (!/[A-Z]/.test(password)) {
        return "Password must contain at least one uppercase letter";
    }
    if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        return "Password must contain at least one special character";
    }
    return true;
}

// Real-time email validation
emailInput.addEventListener('blur', function() {
    if (this.value && !validateEmail(this.value)) {
        emailError.textContent = 'Email must be in format: aq/op/sa.lastname@carmax.com';
        emailError.classList.add('show');
        this.style.borderColor = '#dc2626';
    } else {
        emailError.classList.remove('show');
        this.style.borderColor = '#e5e7eb';
    }
});

// Real-time password validation
passwordInput.addEventListener('blur', function() {
    if (this.value) {
        const validation = validatePassword(this.value);
        if (validation !== true) {
            passwordError.textContent = validation;
            passwordError.classList.add('show');
            this.style.borderColor = '#dc2626';
        } else {
            passwordError.classList.remove('show');
            this.style.borderColor = '#e5e7eb';
        }
    }
});

// Clear errors on input
emailInput.addEventListener('input', function() {
    emailError.classList.remove('show');
    this.style.borderColor = '#e5e7eb';
});

passwordInput.addEventListener('input', function() {
    passwordError.classList.remove('show');
    this.style.borderColor = '#e5e7eb';
});

// Form validation before submit
form.addEventListener('submit', function(e) {
    let isValid = true;

    // Clear previous errors
    emailError.classList.remove('show');
    passwordError.classList.remove('show');
    captchaError.classList.remove('show');

    
    if (!emailInput.value || !emailInput.value.includes('@carmax.com')) {
        emailError.textContent = 'Please enter a valid CarMax email';
        emailError.classList.add('show');
        emailInput.style.borderColor = '#dc2626';
        isValid = false;
    }

    // Validate password (basic check)
    if (!passwordInput.value || passwordInput.value.length < 6) {
        passwordError.textContent = 'Please enter your password';
        passwordError.classList.add('show');
        passwordInput.style.borderColor = '#dc2626';
        isValid = false;
    }

    // Validate reCAPTCHA
    if (typeof grecaptcha !== 'undefined') {
        const captchaResponse = grecaptcha.getResponse();
        if (captchaResponse.length === 0) {
            captchaError.textContent = 'Please complete the reCAPTCHA';
            captchaError.classList.add('show');
            isValid = false;
        }
    }

    // If validation fails, prevent form submission
    if (!isValid) {
        e.preventDefault();
        return false;
    }

    const loginButton = document.getElementById('loginButton');
    loginButton.textContent = 'Signing In...';
    loginButton.disabled = true;
});