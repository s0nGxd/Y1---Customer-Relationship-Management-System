// Sales Team Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Modal Elements
    const addSalesRepModal = document.getElementById('addSalesRepModal');
    const editSalesRepModal = document.getElementById('editSalesRepModal');
    const viewSalesRepModal = document.getElementById('viewSalesRepModal');
    const assignLeadsModal = document.getElementById('assignLeadsModal');
    
    // Modal Open Buttons
    const addSalesRepBtn = document.getElementById('addSalesRepBtn');
    const editSalesRepBtn = document.getElementById('editSalesRepBtn');
    const assignLeadsBtn = document.getElementById('assignLeadsBtn');
    
    // Modal Close Buttons
    const closeModalBtn = document.getElementById('closeModalBtn');
    const closeEditModalBtn = document.getElementById('closeEditModalBtn');
    const closeViewModalBtn = document.getElementById('closeViewModalBtn');
    const closeAssignModalBtn = document.getElementById('closeAssignModalBtn');
    
    // Modal Cancel Buttons
    const cancelAddBtn = document.getElementById('cancelAddBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const cancelAssignBtn = document.getElementById('cancelAssignBtn');
    const closeViewDetailsBtn = document.getElementById('closeViewDetailsBtn');
    
    // View Profile Actions
    const viewEditBtn = document.getElementById('viewEditBtn');
    
    // Forms
    const addSalesRepForm = document.getElementById('addSalesRepForm');
    const editSalesRepForm = document.getElementById('editSalesRepForm');
    const assignLeadsForm = document.getElementById('assignLeadsForm');
    
    // Table elements
    const salesRepsTable = document.getElementById('salesRepsTable');

    // Helper Functions
    function openModal(modal) {
        modal.classList.add('active');
    }
    
    function closeModal(modal) {
        modal.classList.remove('active');
    }
    
    function closeAllModals() {
        closeModal(addSalesRepModal);
        closeModal(editSalesRepModal);
        closeModal(viewSalesRepModal);
        closeModal(assignLeadsModal);
    }
    
    function getRepById(repId) {
        // Find the row with this repId
        const row = document.querySelector(`#salesRepsTable tbody tr[data-repid="${repId}"]`);
        if (!row) {
            // Try getting the first row with this ID in the first column
            const rows = document.querySelectorAll('#salesRepsTable tbody tr');
            for (let i = 0; i < rows.length; i++) {
                if (rows[i].cells[0].textContent.trim() === repId) {
                    return extractRepDataFromRow(rows[i]);
                }
            }
            return null;
        }
        
        return extractRepDataFromRow(row);
    }
    
    function extractRepDataFromRow(row) {
        // Extract data from the row
        const cells = row.querySelectorAll('td');
        if (cells.length < 5) return null;
        
        const id = cells[0].textContent.trim();
        const nameElement = cells[1].querySelector('.user-info-row div:not(.user-avatar)');
        const name = nameElement ? nameElement.textContent.trim() : '';
        const username = cells[2].textContent.trim();
        const email = cells[3].textContent.trim();
        const phone = cells[4].textContent.trim();
        
        // Split name into first and last name
        const nameParts = name.split(' ');
        const firstName = nameParts[0] || '';
        const lastName = nameParts.slice(1).join(' ') || '';
        
        return {
            id: id,
            firstName: firstName,
            lastName: lastName,
            username: username,
            email: email,
            phone: phone,
        };
    }
    
    function getInitials(firstName, lastName) {
        return (firstName.charAt(0) + (lastName ? lastName.charAt(0) : '')).toUpperCase();
    }
    
    function showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Remove after delay
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Setup event listeners for table rows
    function setupTableRows() {
        const rows = document.querySelectorAll('#salesRepsTable tbody tr');
        rows.forEach(row => {
            // Skip rows that don't have data (like "no data found" rows)
            if (row.cells.length < 2) return;

            // Extract rep ID from first column 
            const repId = row.cells[0].textContent.trim();
            row.setAttribute('data-repid', repId);

            // Add click event to show rep details
            row.addEventListener('click', function() {
                populateViewModal(repId);
                openModal(viewSalesRepModal);
            });
        });
    }
    
    // Function to populate edit form with sales rep data
    function populateEditForm(repId) {
        const rep = getRepById(repId);
        if (!rep) {
            showNotification('Could not find representative data', 'error');
            return;
        }
        
        document.getElementById('editRepId').value = rep.id;
        document.getElementById('editFirstName').value = rep.firstName;
        document.getElementById('editLastName').value = rep.lastName;
        document.getElementById('editEmail').value = rep.email;
        document.getElementById('editPhone').value = rep.phone;
        document.getElementById('editUsername').value = rep.username;
        document.getElementById('editPassword').value = ''; // Clear password field
    }
    
    // Function to populate view modal with sales rep data
    function populateViewModal(repId) {
        const rep = getRepById(repId);
        if (!rep) {
            showNotification('Could not find representative data', 'error');
            return;
        }
        
        // Update the fields in the view modal
        document.getElementById('viewRepAvatar').textContent = getInitials(rep.firstName, rep.lastName);
        document.getElementById('viewRepName').textContent = `${rep.firstName} ${rep.lastName}`;
        document.getElementById('viewRepId').textContent = rep.id;
        document.getElementById('viewRepUsername').textContent = rep.username;
        document.getElementById('viewRepEmail').textContent = rep.email;
        document.getElementById('viewRepPhone').textContent = rep.phone;
    }

    // Set up table row event listeners after DOM is loaded
    setupTableRows();

    // Open Add Sales Rep Modal
    if (addSalesRepBtn) {
        addSalesRepBtn.addEventListener('click', function() {
            openModal(addSalesRepModal);
        });
    }

    // Open Edit Sales Rep Modal from main screen
    if (editSalesRepBtn) {
        editSalesRepBtn.addEventListener('click', function() {
            // Get the table rows
            const rows = document.querySelectorAll('#salesRepsTable tbody tr');
            
            // Show error if no rep is found
            if (rows.length === 0) {
                showNotification('No sales representatives found.', 'error');
                return;
            }
            
            // For simplicity, just edit the first rep in the table
            const firstRow = rows[0];
            if (firstRow.cells.length < 1) {
                showNotification('No valid sales representative data found.', 'error');
                return;
            }
            
            const repId = firstRow.getAttribute('data-repid') || firstRow.querySelector('td:first-child').textContent.trim();
            
            populateEditForm(repId);
            openModal(editSalesRepModal);
        });
    }

    // Open Assign Leads Modal
    if (assignLeadsBtn) {
        assignLeadsBtn.addEventListener('click', function() {
            // The PHP code already populates the select dropdown
            openModal(assignLeadsModal);
        });
    }
    
    // Close Add Modal
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            closeModal(addSalesRepModal);
        });
    }
    
    // Close Edit Modal
    if (closeEditModalBtn) {
        closeEditModalBtn.addEventListener('click', function() {
            closeModal(editSalesRepModal);
        });
    }
    
    // Close View Modal
    if (closeViewModalBtn) {
        closeViewModalBtn.addEventListener('click', function() {
            closeModal(viewSalesRepModal);
        });
    }
    
    // Close Assign Modal
    if (closeAssignModalBtn) {
        closeAssignModalBtn.addEventListener('click', function() {
            closeModal(assignLeadsModal);
        });
    }
    
    // Cancel Add
    if (cancelAddBtn) {
        cancelAddBtn.addEventListener('click', function() {
            closeModal(addSalesRepModal);
        });
    }
    
    // Cancel Edit
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            closeModal(editSalesRepModal);
        });
    }
    
    // Cancel Assign
    if (cancelAssignBtn) {
        cancelAssignBtn.addEventListener('click', function() {
            closeModal(assignLeadsModal);
        });
    }
    
    // Close View Details
    if (closeViewDetailsBtn) {
        closeViewDetailsBtn.addEventListener('click', function() {
            closeModal(viewSalesRepModal);
        });
    }
    
    // View Edit Button - Opens Edit Modal from View Modal
    if (viewEditBtn) {
        viewEditBtn.addEventListener('click', function() {
            const repId = document.getElementById('viewRepId').textContent;
            
            closeModal(viewSalesRepModal);
            populateEditForm(repId);
            openModal(editSalesRepModal);
        });
    }
    
    // Add Sales Rep Form Submit
    if (addSalesRepForm) {
        addSalesRepForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // Create FormData object 
            const formData = new FormData();
            formData.append('action', 'add_sales_rep');
            formData.append('firstName', firstName);
            formData.append('lastName', lastName);  // Fixed typo in lastName
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('username', username);
            formData.append('password', password);

            // Send Request
            fetch('save_salesrep.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification('Sales representative added successfully!');

                    // Reload the page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error notification
                    showNotification(data.message || 'Error adding sales representative', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });

            // Close modal and reset form
            closeModal(addSalesRepModal);
            addSalesRepForm.reset();
        });
    }
    
    // Edit Sales Rep Form Submit
    if (editSalesRepForm) {
        editSalesRepForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const repId = document.getElementById('editRepId').value;
            const firstName = document.getElementById('editFirstName').value;
            const lastName = document.getElementById('editLastName').value;
            const email = document.getElementById('editEmail').value;
            const phone = document.getElementById('editPhone').value;
            const username = document.getElementById('editUsername').value;
            const password = document.getElementById('editPassword').value;
            
            // Create FormData object 
            const formData = new FormData();
            formData.append('action', 'update_sales_rep');
            formData.append('repId', repId);  // Added missing repId
            formData.append('firstName', firstName);
            formData.append('lastName', lastName);  // Fixed typo in lastName
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('username', username);
            if (password) {
                formData.append('password', password);
            }
            
            // Send Request
            fetch('save_salesrep.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification('Sales representative updated successfully!');

                    // Reload the page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error notification
                    showNotification(data.message || 'Error updating sales representative', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });
            
            // Close modal
            closeModal(editSalesRepModal);
        });
    }
    
    // Assign Leads Form Submit - AJAX version
    if (assignLeadsForm) {
        assignLeadsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const repId = document.getElementById('selectRep').value;
            const leadCount = document.getElementById('leadCount').value;
            const leadPriority = document.getElementById('leadPriority').value;
            
            if (!repId) {
                showNotification('Please select a sales representative.', 'error');
                return;
            }
            
            // Create FormData object for AJAX
            const formData = new FormData();
            formData.append('action', 'assign_leads');
            formData.append('repId', repId);
            formData.append('leadCount', leadCount);
            formData.append('leadPriority', leadPriority);
            
            // Send AJAX request
            fetch('assign_leads.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification(`${leadCount} leads assigned successfully!`);
                } else {
                    // Show error notification
                    showNotification(data.message || 'Error assigning leads', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });
            
            // Close modal and reset form
            closeModal(assignLeadsModal);
            assignLeadsForm.reset();
        });
    }

    // Add CSS for notification if not already in your CSS file
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s, transform 0.3s;
            z-index: 9999;
        }
        
        .notification.success {
            background-color: #4CAF50;
        }
        
        .notification.error {
            background-color: #F44336;
        }
        
        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .modal.active {
            display: flex;
        }
    `;
    document.head.appendChild(style);
});