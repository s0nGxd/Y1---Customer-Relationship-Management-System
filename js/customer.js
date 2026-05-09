document.addEventListener('DOMContentLoaded', function() {
    // Modal Elements
    const addCustomerModal = document.getElementById('addCustomerModal');
    const editCustomerModal = document.getElementById('editCustomerModal');
    const viewCustomerModal = document.getElementById('viewCustomerModal');
    
    // Modal Open Buttons
    const addCustomerBtn = document.getElementById('addCustomerBtn');
    
    // Modal Close Buttons
    const closeModalBtn = document.getElementById('closeModalBtn');
    const closeEditModalBtn = document.getElementById('closeEditModalBtn');
    const closeViewModalBtn = document.getElementById('closeViewModalBtn');
    
    // Modal Cancel Buttons
    const cancelAddBtn = document.getElementById('cancelAddBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const closeViewDetailsBtn = document.getElementById('closeViewDetailsBtn');
    
    // View Profile Actions
    const viewEditBtn = document.getElementById('viewEditBtn');
    
    // Forms
    const addCustomerForm = document.getElementById('addCustomerForm');
    const editCustomerForm = document.getElementById('editCustomerForm');
    
    // Table elements
    const customersTable = document.getElementById('CustomersTable');


    // Check if user is admin by looking for admin-specific elements
    const isAdmin = document.querySelector('.sidebar-header .logo span')?.textContent.includes('Admin') || false;
    
    // Debug: Log to see if elements are found
    console.log('Add Customer Button:', addCustomerBtn);
    console.log('Add Customer Form:', addCustomerForm);
    console.log('Is Admin:', isAdmin);
    console.log('Status Filter:', customerStatusFilter);
    
    // Load sales reps for admin assignment dropdown
    if (isAdmin) {
        const salesRepSelectContainer = document.getElementById('salesRepSelectContainer');
        if (salesRepSelectContainer) {
            salesRepSelectContainer.style.display = 'block';
            loadSalesReps('salesRepSelect');
        }
        
        // Also load sales reps for edit form if it exists
        const editSalesRepSelectContainer = document.getElementById('editSalesRepSelectContainer');
        if (editSalesRepSelectContainer) {
            editSalesRepSelectContainer.style.display = 'block';
            loadSalesReps('editSalesRepSelect');
        }
    }
    
    // Helper function to load sales reps for admin assignment
    function loadSalesReps(selectElementId) {
        // Check if sales rep selection field exists
        const salesRepSelect = document.getElementById(selectElementId);
        if (!salesRepSelect) {
            console.error('Sales rep select element not found:', selectElementId);
            return;
        }
        
        console.log('Loading sales reps for', selectElementId);
        
        // Send request to get sales reps
        fetch('get_sales_rep.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Sales reps response:', data);
                if (data.success && data.sales_reps) {
                    // Clear existing options
                    salesRepSelect.innerHTML = '';
                    
                    // Add default option
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Select Sales Representative';
                    salesRepSelect.appendChild(defaultOption);
                    
                    // Add options for each sales rep
                    data.sales_reps.forEach(rep => {
                        const option = document.createElement('option');
                        option.value = rep.user_id;
                        option.textContent = rep.name;
                        salesRepSelect.appendChild(option);
                    });
                } else {
                    console.error('Failed to load sales reps:', data.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Error loading sales representatives:', error);
            });
    }

    // Helper Functions
    function openModal(modal) {
        if (modal) {
            modal.classList.add('active');
            console.log('Opening modal:', modal.id);
        } else {
            console.error('Modal not found');
        }
    }
    
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('active');
            console.log('Closing modal:', modal.id);
        } else {
            console.error('Modal not found');
        }
    }
    
    function closeAllModals() {
        closeModal(addCustomerModal);
        closeModal(editCustomerModal);
        closeModal(viewCustomerModal);
    }
    
    function getCustomerById(custId) {
        // Find the row with this custId
        const rows = document.querySelectorAll('#CustomersTable tbody tr');
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].cells && rows[i].cells[0] && rows[i].cells[0].textContent.trim() === custId) {
                return extractCustomerDataFromRow(rows[i]);
            }
        }
        return null;
    }
    
    function extractCustomerDataFromRow(row) {
        // Extract data from the row
        const cells = row.querySelectorAll('td');
        if (cells.length < 5) return null;
        
        const id = cells[0].textContent.trim();
        const nameElement = cells[1].querySelector('.user-info-row div:not(.user-avatar)');
        const name = nameElement ? nameElement.textContent.trim() : '';
        const company = cells[2].textContent.trim();
        const email = cells[3].textContent.trim();
        const phone = cells[4].textContent.trim();
        const address = cells[5] ? cells[5].textContent.trim() : '';
        
        // Split name into first and last name
        const nameParts = name.split(' ');
        const firstName = nameParts[0] || '';
        const lastName = nameParts.slice(1).join(' ') || '';
        
        return {
            id: id,
            firstName: firstName,
            lastName: lastName,
            company: company,
            email: email,
            phone: phone,
            address: address
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

    function filterCustomers(status) {
        const rows = document.querySelectorAll('#CustomersTable tbody tr');
        
        rows.forEach(row => {
            if (row.cells.length < 2) return; // Skip non-data rows
            
            const rowStatus = row.getAttribute('data-status') || '';
            let displayRow = false;
            
            if (status === 'all') {
                displayRow = true;
            } else if (status === 'new') {
                // Only show actual "New" status, not empty ones
                displayRow = rowStatus.toLowerCase() === 'new';
            } else {
                displayRow = rowStatus.toLowerCase() === status.toLowerCase();
            }
            
            row.style.display = displayRow ? '' : 'none';
        });
    }
    
    // Setup event listeners for table rows
    function setupTableRows() {
        const rows = document.querySelectorAll('#CustomersTable tbody tr');
        rows.forEach(row => {
            // Skip rows that don't have data (like "no data found" rows)
            if (row.cells.length < 2) return;
    
            // Extract customer ID from first column 
            const custId = row.cells[0].textContent.trim();
            row.setAttribute('data-custid', custId);
            
            // Get status from the row (assuming it's in a hidden element or data attribute)
            const statusCell = row.querySelector('.status-cell');
            if (statusCell) {
                row.setAttribute('data-status', statusCell.textContent.trim().toLowerCase());
            }
    
            // Remove any existing click event to prevent duplicates
            row.removeEventListener('click', rowClickHandler);
            
            // Add click event to show customer details
            row.addEventListener('click', rowClickHandler);
        });
    }
    
    // Row click handler function
    function rowClickHandler() {
        const custId = this.getAttribute('data-custid');
        if (custId) {
            populateViewModal(custId);
            openModal(viewCustomerModal);
        }
    }
    
    // Function to populate edit form with customer data
    function populateEditForm(custId) {
        console.log('Populating edit form for customer ID:', custId);
        
        const customer = getCustomerById(custId);
        if (!customer) {
            showNotification('Could not find customer data', 'error');
            return;
        }
        
        console.log('Customer data retrieved:', customer);
        
        // Make sure all these elements exist before trying to set values
        if (document.getElementById('editCustId')) document.getElementById('editCustId').value = customer.id;
        if (document.getElementById('editFirstName')) document.getElementById('editFirstName').value = customer.firstName;
        if (document.getElementById('editLastName')) document.getElementById('editLastName').value = customer.lastName;
        if (document.getElementById('editEmail')) document.getElementById('editEmail').value = customer.email;
        if (document.getElementById('editPhone')) document.getElementById('editPhone').value = customer.phone;
        if (document.getElementById('editCompany')) document.getElementById('editCompany').value = customer.company;
        if (document.getElementById('editaddress')) document.getElementById('editaddress').value = customer.address;
        
        // For admin, we need to load the current sales rep assignment
        if (isAdmin && document.getElementById('editSalesRepSelect')) {
            loadSalesReps('editSalesRepSelect');
            
            // Fetch the current sales rep assignment for this customer
            fetch('get_customer_rep.php?customer_id=' + custId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.sales_rep_id) {
                        setTimeout(() => {
                            document.getElementById('editSalesRepSelect').value = data.sales_rep_id;
                        }, 500);
                    }
                })
                .catch(error => console.error('Error fetching customer sales rep:', error));
        }
    }
            
    // Function to populate view modal with customer data
    function populateViewModal(custId) {
        console.log('Populating view modal for customer ID:', custId);
        
        const customer = getCustomerById(custId);
        if (!customer) {
            showNotification('Could not find customer data', 'error');
            return;
        }
        
        console.log('Customer data retrieved for view:', customer);
        
        // Update the fields in the view modal, checking each element exists first
        if (document.getElementById('viewCustAvatar')) 
            document.getElementById('viewCustAvatar').textContent = getInitials(customer.firstName, customer.lastName);
        if (document.getElementById('viewCustName'))
            document.getElementById('viewCustName').textContent = `${customer.firstName} ${customer.lastName}`;
        if (document.getElementById('viewCustId'))
            document.getElementById('viewCustId').textContent = customer.id;
        if (document.getElementById('viewCustUsername'))
            document.getElementById('viewCustUsername').textContent = customer.company;
        if (document.getElementById('viewCustEmail'))
            document.getElementById('viewCustEmail').textContent = customer.email;
        if (document.getElementById('viewCustPhone'))
            document.getElementById('viewCustPhone').textContent = customer.phone;
        if (document.getElementById('viewCustAddress'))
            document.getElementById('viewCustAddress').textContent = customer.address;
    }

    

    // Set up table row event listeners after DOM is loaded
    if (customersTable) {
        setupTableRows();
    }

    // Set up filter event listener
    if (customerStatusFilter) {
        customerStatusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;
            console.log('Filter changed to:', selectedStatus);
            filterCustomers(selectedStatus.toLowerCase());
        });
    }

    // Open Add Customer Modal
    if (addCustomerBtn) {
        addCustomerBtn.addEventListener('click', function() {
            console.log('Add Customer button clicked');
            openModal(addCustomerModal);
        });
    } else {
        console.error('Add Customer button not found');
    }

    // Close Add Modal
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            closeModal(addCustomerModal);
        });
    }
    
    // Close Edit Modal
    if (closeEditModalBtn) {
        closeEditModalBtn.addEventListener('click', function() {
            closeModal(editCustomerModal);
        });
    }
    
    // Close View Modal
    if (closeViewModalBtn) {
        closeViewModalBtn.addEventListener('click', function() {
            closeModal(viewCustomerModal);
        });
    }
    
    // Cancel Add
    if (cancelAddBtn) {
        cancelAddBtn.addEventListener('click', function() {
            closeModal(addCustomerModal);
        });
    }
    
    // Cancel Edit
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            closeModal(editCustomerModal);
        });
    }
    
    // Close View Details
    if (closeViewDetailsBtn) {
        closeViewDetailsBtn.addEventListener('click', function() {
            closeModal(viewCustomerModal);
        });
    }
    
    // View Edit Button - Opens Edit Modal from View Modal
    if (viewEditBtn) {
        viewEditBtn.addEventListener('click', function() {
            const custId = document.getElementById('viewCustId').textContent;
            console.log('Edit button clicked for customer ID:', custId);
            
            closeModal(viewCustomerModal);
            populateEditForm(custId);
            openModal(editCustomerModal);
        });
    }
    
    // Add Customer Form Submit
    if (addCustomerForm) {
        addCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Add Customer form submitted');
            
            // Get form values
            const company = document.getElementById('company').value;
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const phone_number = document.getElementById('phone_number').value;
            const address = document.getElementById('address').value;
            
            console.log('Form data:', { company, firstName, lastName, email, phone_number, address });
            
            // Create FormData object
            const formData = new FormData();
            formData.append('action', 'add_customer');
            formData.append('company', company);
            formData.append('firstName', firstName);
            formData.append('lastName', lastName);
            formData.append('email', email);
            formData.append('phone_number', phone_number);
            formData.append('address', address);
            
            // If admin and sales rep selection exists, include the selected rep
            if (isAdmin) {
                const salesRepSelect = document.getElementById('salesRepSelect');
                if (salesRepSelect) {
                    if (salesRepSelect.value) {
                        formData.append('sales_rep_id', salesRepSelect.value);
                        console.log('Adding sales rep ID:', salesRepSelect.value);
                    } else {
                        showNotification('Please select a sales representative', 'error');
                        return; // Don't submit if no sales rep is selected
                    }
                }
            }
            
            // Send Request
            fetch('save_customer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    // Show success notification
                    showNotification('Customer added successfully!');

                    // Reload the page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error notification
                    showNotification(data.message || 'Error adding customer', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });

            // Close modal and reset form
            closeModal(addCustomerModal);
            addCustomerForm.reset();
        });
    } else {
        console.error('Add Customer form not found');
    }
    
    // Edit Customer Form Submit
    if (editCustomerForm) {
        editCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Edit Customer form submitted');
            
            // Get form values
            const custId = document.getElementById('editCustId').value;
            const company = document.getElementById('editCompany').value;
            const firstName = document.getElementById('editFirstName').value;
            const lastName = document.getElementById('editLastName').value;
            const email = document.getElementById('editEmail').value;
            const phone = document.getElementById('editPhone').value;
            const address = document.getElementById('editaddress').value;
            
            console.log('Edit form data:', { custId, company, firstName, lastName, email, phone, address });
            
            // Create FormData object 
            const formData = new FormData();
            formData.append('action', 'update_customer');
            formData.append('custId', custId);
            formData.append('company', company);
            formData.append('firstName', firstName);
            formData.append('lastName', lastName);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('address', address);
            
            // If admin and editing sales rep exists, include the selected rep
            if (isAdmin) {
                const editSalesRepSelect = document.getElementById('editSalesRepSelect');
                if (editSalesRepSelect && editSalesRepSelect.value) {
                    formData.append('sales_rep_id', editSalesRepSelect.value);
                    console.log('Updating sales rep ID:', editSalesRepSelect.value);
                }
            }
            
            // Send Request
            fetch('save_customer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response for edit:', data);
                if (data.success) {
                    // Show success notification
                    showNotification('Customer updated successfully!');

                    // Reload the page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error notification
                    showNotification(data.message || 'Error updating customer', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });
            
            // Close modal
            closeModal(editCustomerModal);
        });
    } else {
        console.error('Edit Customer form not found');
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
        
        .modal {
            display: none;
        }
        
        .modal.active {
            display: flex;
        }
    `;
    document.head.appendChild(style);
});