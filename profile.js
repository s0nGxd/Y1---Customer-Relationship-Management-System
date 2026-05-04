document.addEventListener('DOMContentLoaded', function() {
    // ==================== SIDEBAR NAVIGATION ====================
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            navLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            
            if (this.getAttribute('href') !== '#') return;
            e.preventDefault();
        });
    });

    function setActiveNavItem() {
        const currentPage = window.location.pathname.split('/').pop() || 'admin.php';
        navLinks.forEach(link => {
            const linkPage = link.getAttribute('href').split('/').pop();
            if (linkPage === currentPage || (currentPage === '' && linkPage === 'admin.php')) {
                link.classList.add('active');
            }
        });
    }
    setActiveNavItem();

    // ==================== PROFILE TABS ====================
    const profileNavItems = document.querySelectorAll('.profile-nav-item');
    const profileTabs = document.querySelectorAll('.profile-tab');
    
    profileNavItems.forEach(item => {
        item.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            profileNavItems.forEach(navItem => navItem.classList.remove('active'));
            profileTabs.forEach(tab => tab.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // ==================== PERSONAL INFO FORM ====================
    const editPersonalBtn = document.getElementById('editPersonalBtn');
    const cancelPersonalBtn = document.getElementById('cancelPersonalBtn');
    const personalInfoInputs = document.querySelectorAll('#personal-info input:not(#role)');
    const personalInfoActions = document.getElementById('personalInfoActions');
    let originalFormValues = {};
    
    if (editPersonalBtn && cancelPersonalBtn) {
        editPersonalBtn.addEventListener('click', function() {
            personalInfoInputs.forEach(input => {
                originalFormValues[input.id] = input.value;
                input.disabled = false;
            });
            personalInfoActions.style.display = 'flex';
            this.style.display = 'none';
        });

        cancelPersonalBtn.addEventListener('click', function() {
            personalInfoInputs.forEach(input => {
                input.value = originalFormValues[input.id];
                input.disabled = true;
            });
            personalInfoActions.style.display = 'none';
            editPersonalBtn.style.display = 'block';
        });
    }

    // ==================== PASSWORD MANAGEMENT ====================
    const changePasswordForm = document.getElementById('changePasswordForm');
    const newPasswordInput = document.getElementById('newPassword');
    const currentPasswordInput = document.getElementById('currentPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // Password strength indicator
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.querySelectorAll('.strength-segment');
            const strengthText = document.getElementById('strengthText');
            
            // Reset
            strengthMeter.forEach(seg => seg.className = 'strength-segment');
            
            if (!password) {
                strengthText.textContent = 'None';
                return;
            }

            // Calculate strength (0-4)
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update UI
            const strengthClass = strength <= 2 ? 'weak' : strength === 3 ? 'fair' : 'strong';
            for (let i = 0; i < strength; i++) {
                strengthMeter[i].classList.add(strengthClass);
            }
            
            strengthText.textContent = 
                strength < 1 ? 'Very Weak' :
                strength < 3 ? 'Weak' :
                strength === 3 ? 'Strong' : 'Very Strong';
        });
    }

    // Password form submission
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Client-side validation
            if (newPasswordInput.value !== confirmPasswordInput.value) {
                e.preventDefault();
                showAlert('New passwords do not match', 'error');
                return;
            }
            
            if (newPasswordInput.value.length < 8) {
                e.preventDefault();
                showAlert('Password must be at least 8 characters', 'error');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            // Form will submit normally, let it proceed
        });
    }

    // ==================== NOTIFICATION TOGGLES ====================
    document.querySelectorAll('.toggle-switch input').forEach(toggle => {
        toggle.addEventListener('change', function() {
            // In a real app, you would save this preference to the server
            console.log(`Setting ${this.id} to ${this.checked}`);
        });
    });

    // ==================== UTILITY FUNCTIONS ====================
    function showAlert(message, type) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(el => el.remove());
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        document.body.appendChild(alert);
        
        setTimeout(() => alert.remove(), 3000);
    }

    function getInitials(name) {
        return name.split(' ').map(n => n[0].toUpperCase()).join('').substring(0, 2);
    }

    // Show session messages on page load
    const successMessage = document.querySelector('.alert-success');
    const errorMessage = document.querySelector('.alert-error');
    
    if (successMessage) setTimeout(() => successMessage.remove(), 3000);
    if (errorMessage) setTimeout(() => errorMessage.remove(), 3000);

    // Scroll to hash if present (for redirects after form submission)
    if (window.location.hash) {
        const targetTab = document.querySelector(`.profile-nav-item[data-tab="${window.location.hash.substring(1)}"]`);
        if (targetTab) targetTab.click();
    }
});