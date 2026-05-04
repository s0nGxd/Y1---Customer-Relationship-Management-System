// Shared navigation functionality for all pages
document.addEventListener('DOMContentLoaded', function() {
    // Handle sidebar navigation
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Remove active class from all links
            navLinks.forEach(item => item.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // For non-hash links, let the browser handle navigation
            if (this.getAttribute('href') !== '#') {
                return true;
            }
            
            // For hash links, prevent default and handle the action
            e.preventDefault();
            console.log('Action for:', this.querySelector('span').textContent);
        });
    });

    // Set active link based on current page
    function setActiveNavItem() {
        const currentPage = window.location.pathname.split('/').pop() || 'admin.php';
        navLinks.forEach(link => {
            const linkPage = link.getAttribute('href').split('/').pop();
            if (linkPage === currentPage || 
                (currentPage === '' && linkPage === 'admin.php')) {
                link.classList.add('active');
            }
        });
    }
    setActiveNavItem();
});