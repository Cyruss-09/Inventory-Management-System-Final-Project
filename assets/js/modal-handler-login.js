window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const overlay = document.getElementById('modalOverlay');
    const title = document.getElementById('modalTitle');
    const msg = document.getElementById('modalMessage');
    const icon = document.getElementById('modalIcon');

    if (status === 'success') {
        overlay.style.display = 'flex';
        icon.innerHTML = '✔';
        icon.className = 'modal-icon success-icon';
        title.innerText = 'Success!';
        msg.innerText = 'Your account has been created.';
    } else if (status === 'exists') {
        overlay.style.display = 'flex';
        icon.innerHTML = '✖';
        icon.className = 'modal-icon error-icon';
        title.innerText = 'Oops!';
        msg.innerText = 'Username or Email already exists.';
    } else if (status === 'invalid') {
        // New case for login failure
        overlay.style.display = 'flex';
        icon.innerHTML = '⚠';
        icon.className = 'modal-icon error-icon'; // Using red color for warnings
        title.innerText = 'Login Failed';
        msg.innerText = 'Invalid username or password. Please try again.';
    }
}

function closeModal() {
    document.getElementById('modalOverlay').style.display = 'none';
    window.history.replaceState({}, document.title, window.location.pathname);
}