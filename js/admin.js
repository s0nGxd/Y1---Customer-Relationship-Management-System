// JavaScript for ADMIN DASHBOARD
document.addEventListener('DOMContentLoaded', function() {
    // Elements variable
    const addSalesRepButtons = document.querySelectorAll('.action-card');
    const addSalesRepModal = document.getElementById('addSalesRepModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelAddBtn = document.getElementById('cancelAddBtn');
    const addSalesRepForm = document.getElementById('addSalesRepForm');

    // Open modal when clicking on "Add Sales Rep" action card
    addSalesRepButtons.forEach(button => {
        button.addEventListener('click', function() {
            if(this.querySelector('.action-title').textContent === 'Add Sales Rep') {
                openModal();
            }
        });
    });

    // Modal functions
    function openModal() {
        addSalesRepModal.classList.add('active');
    }

    function closeModal() {
        addSalesRepModal.classList.remove('active');
        addSalesRepForm.reset();
    }

    // Close modal events
    closeModalBtn.addEventListener('click', closeModal);
    cancelAddBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside
    addSalesRepModal.addEventListener('click', function(e) {
        if (e.target === addSalesRepModal) {
            closeModal();
        }
    });

    // Form Submission
    addSalesRepForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('add_salesrep.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {  // Handle HTTP error status codes
                return response.text().then(text => {
                    throw new Error(text || 'Unknown error occurred');
                });
            }
            return response.text();
        })
        .then(data => {
            alert(data);
            closeModal();
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert(`Error: ${error.message}`);
        });
    });

    // Global search functionality
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keyup', function(e) {
            if(e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if(searchTerm) {
                    console.log('Searching for:', searchTerm);
                    // Implement search functionality
                }
            }
        });
    }

    // Initialize Quick Actions
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            const actionTitle = this.querySelector('.action-title').textContent;
            switch(actionTitle) {
                case 'Generate Reports':
                    alert('Report generation feature would open here.');
                    break;
                case 'System Settings':
                    alert('System settings would open here.');
                    break;
            }
        });
    });
});