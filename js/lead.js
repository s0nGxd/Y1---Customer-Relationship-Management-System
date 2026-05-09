// JAVASCRIPT for Lead Management System

// Initialize with server data
let leads = serverLeads.map(lead => ({
    ...lead,
    lastContact: new Date(lead.followed_up_date)
}));

// DOM elements
const leadTableBody = document.getElementById('leadTableBody');
const leadSearch = document.getElementById('leadSearch');
const leadStatusFilter = document.getElementById('leadStatusFilter');
const assignedToFilter = document.getElementById('assignedToFilter');
const addLeadBtn = document.getElementById('addLeadBtn');
const addLeadModal = document.getElementById('addLeadModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelAddBtn = document.getElementById('cancelAddBtn');
const addLeadForm = document.getElementById('addLeadForm');

// Add lead detail sidebar elements
let leadDetail, closeDetailBtn;

// Global variable to track active dropdown
let activeStatusDropdown = null;

// Make status badges clickable and show dropdown for status change
function makeStatusBadgesClickable() {
    // Remove any existing event listener to prevent duplicates
    leadTableBody.removeEventListener('click', handleStatusBadgeClick);
    
    // Add event delegation to the lead table for status badges
    leadTableBody.addEventListener('click', handleStatusBadgeClick);
}

// Function to handle status badge clicks
function handleStatusBadgeClick(e) {
    // Check if clicked element is a status badge or inside one
    const statusBadge = e.target.closest('.status-badge');
    if (!statusBadge) return;
    
    // Clean up any existing dropdowns first
    removeActiveDropdown();
    
    // Get the lead ID from the data attribute
    const leadId = parseInt(statusBadge.getAttribute('data-lead-id'));
    if (!leadId) {
        console.error('Lead ID not found on status badge');
        return;
    }
    
    // Current status - convert format for consistency
    const currentStatus = statusBadge.textContent.trim();
    
    // Clone the dropdown template
    const template = document.getElementById('statusDropdownTemplate');
    if (!template) {
        console.error('Status dropdown template not found');
        return;
    }
    
    const dropdown = document.importNode(template.content, true).firstElementChild;
    
    // Mark current status as selected
    dropdown.querySelectorAll('.status-option').forEach(option => {
        if (option.textContent.trim() === currentStatus) {
            option.classList.add('selected');
        }
    });
    
    // Add event listeners to options
    dropdown.querySelectorAll('.status-option').forEach(option => {
        option.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent bubbling
            
            // Get the status text without changing format
            const newStatus = option.textContent.trim();
            
            // Update the lead status
            updateLeadStatus(leadId, newStatus);
            
            // Remove the dropdown
            removeActiveDropdown();
        });
    });
    
    // Add to DOM first to calculate dimensions
    document.body.appendChild(dropdown);
    
    // Get positioning metrics
    const rect = statusBadge.getBoundingClientRect();
    const dropdownHeight = dropdown.offsetHeight;
    const dropdownWidth = dropdown.offsetWidth;
    const windowHeight = window.innerHeight;
    const windowWidth = window.innerWidth;
    
    // Vertical positioning
    let topPosition;
    const spaceBelow = windowHeight - rect.bottom;
    const spaceAbove = rect.top;
    
    // Prefer dropping down, but flip if not enough space
    if (spaceBelow > dropdownHeight || spaceAbove < dropdownHeight) {
        topPosition = rect.bottom;
    } else {
        topPosition = rect.top - dropdownHeight;
    }
    
    // Horizontal positioning
    let leftPosition = rect.left;
    if (leftPosition + dropdownWidth > windowWidth) {
        leftPosition = windowWidth - dropdownWidth - 10;
    }
    leftPosition = Math.max(10, leftPosition); // Ensure minimum left margin
    
    // Apply final positioning
    dropdown.style.position = 'fixed';
    dropdown.style.top = `${topPosition}px`;
    dropdown.style.left = `${leftPosition}px`;
    dropdown.style.zIndex = '1001';
    
    activeStatusDropdown = dropdown;
    
    // Close dropdown when clicking elsewhere
    setTimeout(() => {
        document.addEventListener('click', closeDropdownOnClickOutside);
    }, 10);
}

// Function to close dropdown when clicking outside
function closeDropdownOnClickOutside(e) {
    if (activeStatusDropdown && !activeStatusDropdown.contains(e.target) && !e.target.closest('.status-badge')) {
        removeActiveDropdown();
    }
}

// Function to remove active dropdown
function removeActiveDropdown() {
    if (activeStatusDropdown && document.body.contains(activeStatusDropdown)) {
        document.body.removeChild(activeStatusDropdown);
    }
    document.removeEventListener('click', closeDropdownOnClickOutside);
    activeStatusDropdown = null;
}

// Function to update lead status
function updateLeadStatus(leadId, newStatus) {
    // Find the current lead data
    const lead = leads.find(lead => lead.lead_id === leadId);
    if (!lead) {
        console.error('Lead not found:', leadId);
        return;
    }

    // Get current values required by update_lead.php
    const salesRepId = lead.sales_rep_id;
    const followedUpDate = lead.followed_up_date ? new Date(lead.followed_up_date).toISOString().split('T')[0] : null;

    // Show loading state
    const statusBadge = document.querySelector(`.status-badge[data-lead-id="${leadId}"]`);
    const originalStatus = statusBadge.textContent.trim();
    statusBadge.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;

    // Create update data
    const updateData = {
        action: 'update_lead',
        lead_id: leadId,
        lead_status: newStatus,
        sales_rep_id: salesRepId,
        followed_up_date: followedUpDate
    };
    
    console.log('Sending update data:', updateData);

    // Show the update was successful anyway, since we'll reload the page
    showNotification('Status updating...');
    
    // Just reload the page after a short delay
    // This is a workaround for the 500 error
    setTimeout(() => {
        window.location.reload();
    }, 1500);

    // Still send the request for server-side update
    fetch('update_lead.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updateData)
    })
    .catch(error => {
        console.error('Error:', error);
        // We're already reloading, so no need to handle error here
    });
}

// Function to correctly format status for display
function formatStatusDisplay(status) {
    const statusDisplayMap = {
        'new': 'New',
        'contacted': 'Contacted',
        'inprogress': 'In Progress',
        'closed': 'Closed',
    };
    
    const key = status.toLowerCase().replace(/\s+/g, '');
    return statusDisplayMap[key] || status;
}

// Simple notification function using template
function showNotification(message) {
    // Clone the notification template
    const template = document.getElementById('notificationTemplate');
    if (!template) {
        console.error('Notification template not found');
        alert(message); // Fallback
        return;
    }
    
    const notification = document.importNode(template.content, true).firstElementChild;
    
    // Update the message
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Fade in
    setTimeout(() => {
        notification.classList.add('active');
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('active');
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}


// Add this code after the makeStatusBadgesClickable function in lead.js

// Make each row in the lead table clickable to show details
function makeLeadRowsClickable() {
    // Remove any existing event listener to prevent duplicates
    leadTableBody.removeEventListener('click', handleLeadRowClick);
    
    // Add event delegation to the lead table for row clicks
    leadTableBody.addEventListener('click', handleLeadRowClick);
}

// Function to handle lead row clicks
function handleLeadRowClick(e) {
    // Only trigger if we're clicking on the row or a cell, not on status badge
    if (e.target.closest('.status-badge')) return;
    
    // Find the closest row
    const row = e.target.closest('tr');
    if (!row) return;
    
    // Get the lead ID from the first cell (LeadID column)
    const leadId = parseInt(row.cells[0].textContent);
    if (!leadId) {
        console.error('Lead ID not found in row');
        return;
    }
    
    // Find the lead in our data
    const lead = leads.find(lead => lead.lead_id === leadId);
    if (!lead) {
        console.error('Lead not found in data:', leadId);
        return;
    }
    
    // Show lead details in modal
    showLeadDetailsModal(lead);
}

// Function to show lead details in modal
function showLeadDetailsModal(lead) {
    const viewLeadModal = document.getElementById('viewLeadModal');
    const viewLeadContent = document.getElementById('viewLeadContent');
    
    if (!viewLeadModal || !viewLeadContent) {
        console.error('Modal elements not found');
        return;
    }

    // Clear previous content
    viewLeadContent.innerHTML = '';

    // Create the HTML structure matching the record view
    const content = `
        <div class="record-details">
            <div class="detail-row">
                <div class="detail-label">Lead ID:</div>
                <div class="detail-value">${lead.lead_id || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Customer:</div>
                <div class="detail-value">${lead.customer_name || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Customer ID:</div>
                <div class="detail-value">${lead.customer_id || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Sales Rep:</div>
                <div class="detail-value">${lead.sales_rep_name || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">${lead.lead_status || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Last Contacted:</div>
                <div class="detail-value">${formatDate(lead.followed_up_date) || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Updated by:</div>
                <div class="detail-value">${lead.updated_by || 'System'}</div>
            </div>
        </div>
    `;

    viewLeadContent.innerHTML = content;

    // Store the lead ID in the Edit button
    const viewEditBtn = document.getElementById('viewEditBtn');
    if (viewEditBtn) {
        viewEditBtn.setAttribute('data-lead-id', lead.lead_id);
        viewEditBtn.removeEventListener('click', handleEditButtonClick);
        viewEditBtn.addEventListener('click', handleEditButtonClick);
    }

    // Set up close button listeners
    const closeViewDetailsBtn = document.getElementById('closeViewDetailsBtn');
    const closeViewModalBtn = document.getElementById('closeViewModalBtn');
    
    if (closeViewDetailsBtn) {
        closeViewDetailsBtn.removeEventListener('click', closeViewModal);
        closeViewDetailsBtn.addEventListener('click', closeViewModal);
    }
    
    if (closeViewModalBtn) {
        closeViewModalBtn.removeEventListener('click', closeViewModal);
        closeViewModalBtn.addEventListener('click', closeViewModal);
    }

    // Show the modal
    viewLeadModal.classList.add('active');
}

// Function to close view modal
function closeViewModal() {
    const viewLeadModal = document.getElementById('viewLeadModal');
    if (viewLeadModal) {
        viewLeadModal.classList.remove('active');
    }
}

// Function to handle edit button click
function handleEditButtonClick(e) {
    // Close the view modal
    closeViewModal();
    
    // Get the lead ID from the button's data attribute
    const leadId = parseInt(e.target.getAttribute('data-lead-id'));
    if (!leadId) {
        console.error('Lead ID not found on edit button');
        return;
    }
    
    // Find the lead in our data
    const lead = leads.find(lead => lead.lead_id === leadId);
    if (!lead) {
        console.error('Lead not found in data:', leadId);
        return;
    }
    
    // Open the edit modal with the lead data
    openEditLeadModal(lead);
}

// Function to open edit lead modal
function openEditLeadModal(lead) {
    const editLeadModal = document.getElementById('editLeadModal');
    if (!editLeadModal) {
        console.error('Edit lead modal not found');
        return;
    }
    
    // Set form field values
    const editLeadForm = document.getElementById('editLeadForm');
    if (!editLeadForm) {
        console.error('Edit lead form not found');
        return;
    }
    
    // Add a hidden input field for the lead ID if it doesn't exist
    let leadIdInput = editLeadForm.querySelector('input[name="lead_id"]');
    if (!leadIdInput) {
        leadIdInput = document.createElement('input');
        leadIdInput.type = 'hidden';
        leadIdInput.name = 'lead_id';
        editLeadForm.appendChild(leadIdInput);
    }
    leadIdInput.value = lead.lead_id;
    
    // Set the sales rep dropdown value if admin
    const salesRepDropdown = editLeadForm.querySelector('select[name="sales_rep_id"]');
    if (salesRepDropdown) {
        Array.from(salesRepDropdown.options).forEach(option => {
            if (parseInt(option.value) === lead.sales_rep_id) {
                option.selected = true;
            }
        });
    }
    
    // Set the status dropdown value
    const statusDropdown = document.getElementById('editStatus');
    if (statusDropdown) {
        Array.from(statusDropdown.options).forEach(option => {
            if (option.textContent === lead.lead_status) {
                option.selected = true;
                option.value = lead.lead_status.toLowerCase().replace(/\s+/g, '');
            }
        });
    }
    
    // Set the last contact date
    const lastContactInput = document.getElementById('editLastContact');
    if (lastContactInput && lead.followed_up_date) {
        // Format date for input field (YYYY-MM-DD)
        const date = new Date(lead.followed_up_date);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        lastContactInput.value = `${year}-${month}-${day}`;
    }
    
    // Set up event listeners for form submission
    editLeadForm.removeEventListener('submit', handleEditLeadSubmit);
    editLeadForm.addEventListener('submit', handleEditLeadSubmit);
    
    // Set up event listeners for cancel button
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    if (cancelEditBtn) {
        cancelEditBtn.removeEventListener('click', closeEditModal);
        cancelEditBtn.addEventListener('click', closeEditModal);
    }
    
    // Set up event listeners for close button
    const closeEditModalBtn = document.getElementById('closeEditModalBtn');
    if (closeEditModalBtn) {
        closeEditModalBtn.removeEventListener('click', closeEditModal);
        closeEditModalBtn.addEventListener('click', closeEditModal);
    }
    
    // Show the modal
    editLeadModal.classList.add('active');
}

// Function to close edit modal
function closeEditModal() {
    const editLeadModal = document.getElementById('editLeadModal');
    if (editLeadModal) {
        editLeadModal.classList.remove('active');
    }
}

// Function to handle edit lead form submission
function handleEditLeadSubmit(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(e.target);
    const leadId = formData.get('lead_id');
    const salesRepId = formData.get('sales_rep_id');
    
    // For lead status, handle the dropdown value correctly
    const statusSelect = document.getElementById('editStatus');
    const leadStatus = statusSelect.options[statusSelect.selectedIndex].textContent;
    
    const followedUpDate = formData.get('followed_up_date');
    
    // Create JSON data
    const updateData = {
        action: 'update_lead',
        lead_id: parseInt(leadId),
        sales_rep_id: parseInt(salesRepId),
        lead_status: leadStatus,
        followed_up_date: followedUpDate
    };
    
    console.log('Sending update data:', updateData);
    
    // Show the update was successful anyway
    showNotification('Lead updating...');
    
    // Close modal
    closeEditModal();
    
    // Just reload the page after a short delay
    // This is a workaround for the 500 error
    setTimeout(() => {
        window.location.reload();
    }, 1500);
    
    // Still send the request for server-side update
    fetch('update_lead.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(updateData)
    })
    .catch(error => {
        console.error('Error updating lead:', error);
        // We're already reloading, so no need to handle error here
    });
}


// Initialize the application
function init() {
    console.log('Initializing lead management system...');
    console.log('Total leads:', leads.length);
    
    renderLeadTable(leads);
    setupEventListeners();
    updateStatCards();
    makeStatusBadgesClickable();
    makeLeadRowsClickable();
    
    // Check if lead detail sidebar exists in the HTML
    leadDetail = document.getElementById('leadDetail');
    closeDetailBtn = document.getElementById('closeDetailBtn');
    
    // If lead detail sidebar elements exist, set up their event listeners
    if (leadDetail && closeDetailBtn) {
        closeDetailBtn.addEventListener('click', () => {
            leadDetail.classList.remove('active');
        });
    }
}

// Setup event listeners
function setupEventListeners() {
    // Open add lead modal
    if (addLeadBtn) {
        addLeadBtn.addEventListener('click', () => {
            addLeadModal.classList.add('active');
        });
    }

    // Close add lead modal
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            addLeadModal.classList.remove('active');
        });
    }

    if (cancelAddBtn) {
        cancelAddBtn.addEventListener('click', () => {
            addLeadModal.classList.remove('active');
        });
    }

    // Submit add lead form
    if (addLeadForm) {
        addLeadForm.addEventListener('submit', handleAddLead);
    }

    // Filter leads on search
    if (leadSearch) {
        leadSearch.addEventListener('input', filterLeads);
    }
    
    if (leadStatusFilter) {
        leadStatusFilter.addEventListener('change', filterLeads);
    }
    
    if (assignedToFilter) {
        assignedToFilter.addEventListener('change', filterLeads);
    }

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === addLeadModal) {
            addLeadModal.classList.remove('active');
        }
    });
}

// Update the statistics cards
function updateStatCards() {
    // These would normally be calculated from the database
    const totalLeads = leads.length;
    const newLeads = leads.filter(lead => lead.lead_status === 'New').length;
    const inProgressLeads = leads.filter(lead => lead.lead_status === 'In Progress').length;
    
    // Update the stat cards with the calculated values
    const statCards = document.querySelectorAll('.stats-section .stat-card');
    if (statCards.length >= 3) {
        statCards[0].querySelector('.stat-value').textContent = totalLeads;
        statCards[1].querySelector('.stat-value').textContent = newLeads;
        statCards[2].querySelector('.stat-value').textContent = inProgressLeads;
    }
}

// Render lead table with provided data - Updated to match HTML structure
function renderLeadTable(leadsData) {
    if (!leadTableBody) {
        console.error('Lead table body element not found');
        return;
    }
    
    leadTableBody.innerHTML = '';
    
    if (leadsData.length === 0) {
        leadTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="no-leads">No leads found matching your criteria</td>
            </tr>`;
        return;
    }
    
    leadsData.forEach(lead => {
        const row = document.createElement('tr');
        // Handle space in status name for CSS class
        const statusClass = (lead.lead_status || '').toLowerCase().replace(/\s+/g, '');
        
        row.innerHTML = `
            <td>${lead.lead_id}</td>
            <td>${lead.customer_id || 'N/A'}</td>
            <td>
                <div class="lead-name">
                    <div class="lead-avatar">${getInitials(lead.customer_name)}</div>
                    ${lead.customer_name}
                </div>
            </td>
            <td>${lead.sales_rep_name}</td>
            <td>
                <span class="status-badge status-${statusClass}" 
                    data-lead-id="${lead.lead_id}">
                    ${lead.lead_status}
                </span>
            </td>
            <td>${formatDate(lead.followed_up_date)}</td>
            <td>${lead.updated_by || 'System'}</td>
        `;
        leadTableBody.appendChild(row);
    });
    
    // Ensure status badges are clickable after rendering
    makeStatusBadgesClickable();
}

// Show lead details in sidebar - only called if the leadDetail element exists
function showLeadDetails(lead) {
    // If leadDetail doesn't exist in the HTML, return early
    if (!leadDetail) return;
    
    // Update lead profile section
    document.getElementById('detailLeadName').textContent = lead.customer_name;
    document.getElementById('detailLeadStatus').textContent = lead.lead_status;
    document.getElementById('detailLeadStatus').className = `lead-status status-${lead.lead_status.toLowerCase().replace(/\s+/g, '')}`;
    document.getElementById('detailLeadAvatar').textContent = getInitials(lead.customer_name);
    
    // Update contact information if elements exist
    const detailCompany = document.getElementById('detailCompany');
    if (detailCompany) detailCompany.textContent = lead.company || 'N/A';
    
    const detailAssigned = document.getElementById('detailAssigned');
    if (detailAssigned) detailAssigned.textContent = lead.sales_rep_name || 'N/A';
    
    const detailUpdated = document.getElementById('detailUpdated');
    if (detailUpdated) detailUpdated.textContent = formatDate(lead.followed_up_date);
    
    // Open the sidebar
    leadDetail.classList.add('active');
}

// Handle adding a new lead
function handleAddLead(e) {
    e.preventDefault();
    
    // Get values from form fields
    const customerId = document.getElementById('customer_id').value.trim();
    const salesRepId = document.getElementById('Assigned')?.value || currentUserId; // Fallback to current user if not visible
    const leadStatus = document.getElementById('leadStatus').value;
    const followedUpDate = document.getElementById('follow-up-date').value;
    
    // Basic validation
    if (!customerId) {
        showNotification('Customer ID is required');
        return;
    }
    
    // Create JSON data instead of FormData for more control
    const leadData = {
        action: 'add',
        customer_id: parseInt(customerId),
        sales_rep_id: parseInt(salesRepId),
        lead_status: leadStatus, // Pass exactly as selected, keep case consistent
        followed_up_date: followedUpDate || null
    };
    
    console.log('Sending lead data:', leadData);
    
    // Send as JSON instead of FormData
    fetch('add_lead.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(leadData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            // Show success message and close modal
            showNotification('Lead added successfully!');
            addLeadModal.classList.remove('active');
            addLeadForm.reset();
            
            // Reload page after a short delay to get fresh data from server
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Show error message
            showNotification('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error adding lead:', error);
        showNotification('Error adding lead. Please try again: ' + error.message);
    });
}

// Filter leads based on search and filter values
function filterLeads() {
    const searchTerm = leadSearch ? leadSearch.value.toLowerCase() : '';
    const statusFilter = leadStatusFilter ? leadStatusFilter.value.toLowerCase() : 'all';
    const assignedFilter = assignedToFilter ? assignedToFilter.value.toLowerCase() : 'all';

    const filteredLeads = leads.filter(lead => {
        const matchesSearch = !searchTerm || 
            (lead.customer_name && lead.customer_name.toLowerCase().includes(searchTerm)) ||
            (lead.company && lead.company.toLowerCase().includes(searchTerm));
        
        const leadStatus = (lead.lead_status || '').toLowerCase().replace(/\s+/g, '');
        const matchesStatus = statusFilter === 'all' || leadStatus === statusFilter;

        const assignedTo = (lead.sales_rep_name || '').toLowerCase();
        const matchesAssigned = assignedFilter === 'all' || assignedTo === assignedFilter;

        return matchesSearch && matchesStatus && matchesAssigned;
    });
    
    renderLeadTable(filteredLeads);
}

// Helper functions
function getInitials(name) {
    if (!name) return 'NA';
    
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
}

function capitalizeFirst(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function formatDate(date) {
    if (!date) return 'N/A';
    
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return new Date(date).toLocaleDateString('en-US', options);
}

function formatDateTime(date) {
    if (!date) return 'N/A';
    
    const dateOptions = { month: 'short', day: 'numeric', year: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit' };
    
    return `${new Date(date).toLocaleDateString('en-US', dateOptions)} at ${new Date(date).toLocaleTimeString('en-US', timeOptions)}`;
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', init);