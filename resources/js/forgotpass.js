const submitBtn   = document.getElementById('submitBtn');
const errorMsg    = document.getElementById('errorMsg');
const successMsg  = document.getElementById('successMsg');
const stepDesc    = document.getElementById('step-desc');
const emailField     = document.getElementById('email-field');
const codeField      = document.getElementById('code-field');
const passwordFields = document.getElementById('password-fields');
const emailInput  = document.getElementById('email');
const codeInput   = document.getElementById('code');
const newPassword = document.getElementById('new-password');
const confirmPass = document.getElementById('confirm-password');
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

let step = 1;
let userEmail = '';

function showError(msg) { errorMsg.textContent = msg; errorMsg.style.display = 'block'; successMsg.style.display = 'none'; }
function showSuccess(msg) { successMsg.textContent = msg; successMsg.style.display = 'block'; errorMsg.style.display = 'none'; }
function hideMessages() { errorMsg.style.display = 'none'; successMsg.style.display = 'none'; }
function setLoading(loading, label) { submitBtn.disabled = loading; submitBtn.textContent = loading ? 'Please wait...' : label; }

async function sendOtp() {
    const email = emailInput.value.trim();
    if (!email) { showError('Please enter your email address.'); return; }
    setLoading(true, 'Send Reset Code'); hideMessages();
    try {
        const res = await fetch('/forgot-password/send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ email })
        });
        const data = await res.json();
        if (!res.ok) { showError(data.errors?.email?.[0] || data.message || 'Email not found.'); setLoading(false, 'Send Reset Code'); return; }
        userEmail = email; step = 2;
        emailField.style.display = 'none';
        codeField.style.display  = 'block';
        stepDesc.textContent = 'A 6-digit code has been sent to ' + email + '. Check your inbox (and spam folder).';
        setLoading(false, 'Verify Code');
        showSuccess('✓ Code sent! Check your email inbox.');
    } catch (err) { showError('Something went wrong. Please try again.'); setLoading(false, 'Send Reset Code'); }
}

async function verifyOtp() {
    const code = codeInput.value.trim();
    if (!code || code.length !== 6) { showError('Please enter the 6-digit code.'); return; }
    setLoading(true, 'Verify Code'); hideMessages();
    try {
        const res = await fetch('/forgot-password/verify-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ email: userEmail, code })
        });
        const data = await res.json();
        if (!res.ok) { showError(data.error || 'Invalid or expired code.'); setLoading(false, 'Verify Code'); return; }
        step = 3;
        codeField.style.display      = 'none';
        passwordFields.style.display = 'block';
        stepDesc.textContent = 'Code verified! Enter your new password below.';
        setLoading(false, 'Reset Password');
        showSuccess('✓ Code verified! Now set your new password.');
    } catch (err) { showError('Something went wrong. Please try again.'); setLoading(false, 'Verify Code'); }
}

async function resetPassword() {
    const password = newPassword.value;
    const password_confirmation = confirmPass.value;
    if (!password || password.length < 6) { showError('Password must be at least 6 characters.'); return; }
    if (password !== password_confirmation) { showError('Passwords do not match.'); return; }
    setLoading(true, 'Reset Password'); hideMessages();
    try {
        const res = await fetch('/forgot-password/reset', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ email: userEmail, password, password_confirmation })
        });
        const data = await res.json();
        if (!res.ok) { showError(data.message || 'Could not reset password.'); setLoading(false, 'Reset Password'); return; }
        showSuccess('✓ Password reset successful! Redirecting to login...');
        submitBtn.style.display = 'none';
        setTimeout(() => { window.location.href = '/login'; }, 2000);
    } catch (err) { showError('Something went wrong. Please try again.'); setLoading(false, 'Reset Password'); }
}

submitBtn.addEventListener('click', () => {
    if (step === 1) sendOtp();
    else if (step === 2) verifyOtp();
    else if (step === 3) resetPassword();
});