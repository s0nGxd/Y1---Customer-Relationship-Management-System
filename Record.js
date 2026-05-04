// Customer Record Management System

document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const recordTableBody = document.getElementById('recordTableBody');
    const recordTable = document.getElementById('RecordTable');
    const recordDetailsModal = document.getElementById('recordDetailsModal');
    const recordDetailsContent = document.getElementById('recordDetailsContent');

    // Modal Elements
    const addRecordModal = document.getElementById('addRecordModal');
    const editRecordModal = document.getElementById('editRecordModal');
    const viewRecordModal = document.getElementById('viewRecordModal');
    const viewRecordContent = document.getElementById('viewRecordContent');

    // Modal Open Buttons
    const addRecordBtn = document.getElementById('addRecordBtn');

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
    const addRecordForm = document.getElementById('addRecordForm');
    const editRecordForm = document.getElementById('editRecordForm');

    // Filter Elements
    const customerFilter = document.getElementById('customerFilter');
    const timeFilter = document.getElementById('timeFilter');
    const productFilter = document.getElementById('productFilter');

    // Helper Functions
    function openModal(modal) {
        if (modal) modal.classList.add('active');
    }

    function closeModal(modal) {
        if (modal) modal.classList.remove('active');
    }

    function closeAllModals() {
        closeModal(addRecordModal);
        closeModal(editRecordModal);
        closeModal(viewRecordModal);
    }

    // Initialize the application
    function init() {
        initializeTableRows();
        setupEventListeners();
    }

    // Setup event listeners for buttons, modals, etc.
    function setupEventListeners() {
        // Add Record Button
        if (addRecordBtn) {
            addRecordBtn.addEventListener('click', function() {
                openModal(addRecordModal);
            });
        }

        // Close Modal Buttons
        if (closeModalBtn) closeModalBtn.addEventListener('click', () => closeModal(addRecordModal));
        if (closeEditModalBtn) closeEditModalBtn.addEventListener('click', () => closeModal(editRecordModal));
        if (closeViewModalBtn) closeViewModalBtn.addEventListener('click', () => closeModal(viewRecordModal));

        // Cancel Buttons
        if (cancelAddBtn) cancelAddBtn.addEventListener('click', () => closeModal(addRecordModal));
        if (cancelEditBtn) cancelEditBtn.addEventListener('click', () => closeModal(editRecordModal));
        if (closeViewDetailsBtn) closeViewDetailsBtn.addEventListener('click', () => closeModal(viewRecordModal));

        // Edit Button in View Modal
        if (viewEditBtn) {
            viewEditBtn.addEventListener('click', function() {
                // Get the record ID from the view modal
                const recordId = viewRecordModal.getAttribute('data-record-id');
                if (recordId) {
                    // Close view modal
                    closeModal(viewRecordModal);
                    // Open edit modal with the same record
                    openEditModal(recordId);
                }
            });
        }

        // Submit Handlers for forms
        if (addRecordForm) {
            addRecordForm.addEventListener('submit', handleAddRecord);
        }

        if (editRecordForm) {
            editRecordForm.addEventListener('submit', handleEditRecord);
        }

        // Filter handlers
        if (customerFilter) customerFilter.addEventListener('change', applyFilters);
        if (timeFilter) timeFilter.addEventListener('change', applyFilters);
        if (productFilter) productFilter.addEventListener('change', applyFilters);

        // Table row click for viewing record details
        if (recordTableBody) {
            recordTableBody.addEventListener('click', function(e) {
                // Find closest TR element (table row)
                const row = e.target.closest('tr');
                if (row && row.hasAttribute('data-record-id')) {
                    const recordId = row.getAttribute('data-record-id');
                    openViewModal(recordId);
                }
            });
        }
    }

    function handleAddRecord(e) {
        e.preventDefault();
        
        const formData = new FormData(addRecordForm);
        formData.append('action', 'add_record');
        
        fetch('save_record.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Record added successfully!');
                closeModal(addRecordModal);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification(data.message || 'Error adding record', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
    
    function handleEditRecord(e) {
        e.preventDefault();
        
        const formData = new FormData(editRecordForm);
        formData.append('action', 'update_customer');
        
        fetch('save_record.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Record updated successfully!');
                closeModal(editRecordModal);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification(data.message || 'Error updating record', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
    
    // Add this notification function (same as customer.js)
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Add this to handle PHP-generated notifications on page load
    document.addEventListener('DOMContentLoaded', function() {
        const phpNotification = document.getElementById('php-notification');
        if (phpNotification) {
            const type = phpNotification.classList.contains('error') ? 'error' : 'success';
            showNotification(phpNotification.textContent, type);
            phpNotification.remove();
        }
    });

    // Open edit modal with record data
    function openEditModal(recordId) {
        // Find record in recordsData
        const record = findRecordById(recordId);
        
        if (record) {
            // Populate form with record data
            document.getElementById('editRecordId').value = record.record_id;
            document.getElementById('editCustomer').value = record.customer_id;
            document.getElementById('editInvoice_number').value = record.invoice_number;
            document.getElementById('editProduct').value = record.product;
            document.getElementById('editPurchase_date').value = record.purchase_date.split(' ')[0]; // Get only the date part
            document.getElementById('editAmount_purchase').value = record.amount_spent;
            document.getElementById('editNotes').value = record.notes || '';
            
            // Open modal
            openModal(editRecordModal);
        } else {
            alert('Record not found');
        }
    }

    // Open view modal with record details
    function openViewModal(recordId) {
        // Find record in recordsData
        const record = findRecordById(recordId);
        
        if (record) {
            // Set record ID to modal for reference
            viewRecordModal.setAttribute('data-record-id', recordId);
            
            // Create HTML content for the view modal
            const content = `
                <div class="record-details">
                    <div class="detail-row">
                        <div class="detail-label">Customer:</div>
                        <div class="detail-value">${record.name}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Invoice Number:</div>
                        <div class="detail-value">${record.invoice_number}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Product:</div>
                        <div class="detail-value">${record.product}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Purchase Date:</div>
                        <div class="detail-value">${formatDate(record.purchase_date)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Amount:</div>
                        <div class="detail-value">$${parseFloat(record.amount_spent).toFixed(2)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Sales Rep:</div>
                        <div class="detail-value">${record.sales_rep_name || 'N/A'}</div>
                    </div>
                    ${record.notes ? `
                    <div class="detail-row notes">
                        <div class="detail-label">Notes:</div>
                        <div class="detail-value">${record.notes}</div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            // Set content and open modal
            viewRecordContent.innerHTML = content;
            openModal(viewRecordModal);
        } else {
            alert('Record not found');
        }
    }

    // Format date for display
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    // Find record by ID in recordsData array
    function findRecordById(id) {
        if (!recordsData || !Array.isArray(recordsData)) return null;
        
        return recordsData.find(record => record.record_id == id) || null;
    }

    // Initialize table rows with event listeners
    function initializeTableRows() {
        // Add any specific initialization for table rows if needed
    }

    // Apply filters to the records table
    function applyFilters() {
        const customerValue = customerFilter.value;
        const timeValue = timeFilter.value;
        const productValue = productFilter.value;
        
        // Get all rows
        const rows = recordTableBody.querySelectorAll('tr');
        
        // Iterate through rows and hide/show based on filters
        rows.forEach(row => {
            const recordId = row.getAttribute('data-record-id');
            if (!recordId) return; // Skip rows without record ID
            
            const record = findRecordById(recordId);
            if (!record) return; // Skip if record not found
            
            let showRow = true;
            
            // Apply customer filter
            if (customerValue !== 'all' && record.customer_id != customerValue) {
                showRow = false;
            }
            
            // Apply product filter
            if (productValue !== 'all' && record.product !== productValue) {
                showRow = false;
            }
            
            // Apply time filter
            if (timeValue !== 'all') {
                const recordDate = new Date(record.purchase_date);
                const now = new Date();
                
                if (timeValue === 'week') {
                    // Check if within the last 7 days
                    const weekAgo = new Date();
                    weekAgo.setDate(now.getDate() - 7);
                    if (recordDate < weekAgo) {
                        showRow = false;
                    }
                } else if (timeValue === 'month') {
                    // Check if within the current month
                    if (recordDate.getMonth() !== now.getMonth() || 
                        recordDate.getFullYear() !== now.getFullYear()) {
                        showRow = false;
                    }
                } else if (timeValue === 'year') {
                    // Check if within the current year
                    if (recordDate.getFullYear() !== now.getFullYear()) {
                        showRow = false;
                    }
                }
            }
            
            // Show or hide row
            row.style.display = showRow ? '' : 'none';
        });
        
        // Check if there are any visible rows
        let visibleRows = 0;
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                visibleRows++;
            }
        });
        
        // Show "No records found" if all rows are filtered out
        if (visibleRows === 0 && rows.length > 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.id = 'no-records-row';
            emptyRow.innerHTML = '<td colspan="7" style="text-align: center; padding: 30px;">No records found with current filters</td>';
            
            // Remove existing no-records-row if it exists
            const existingEmptyRow = document.getElementById('no-records-row');
            if (existingEmptyRow) {
                existingEmptyRow.remove();
            }
            
            recordTableBody.appendChild(emptyRow);
        } else {
            // Remove no-records-row if it exists and there are visible rows
            const existingEmptyRow = document.getElementById('no-records-row');
            if (existingEmptyRow) {
                existingEmptyRow.remove();
            }
        }
    }

    // Initialize the application
    init();
});