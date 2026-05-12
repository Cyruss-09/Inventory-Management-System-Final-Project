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
    }
}

function closeModal() {
    document.getElementById('modalOverlay').style.display = 'none';
    // Optional: Clean the URL so the modal doesn't pop up again on refresh
    window.history.replaceState({}, document.title, window.location.pathname);
}