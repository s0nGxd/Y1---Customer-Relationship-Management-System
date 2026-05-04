// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Modal handling
    initializeModals();
    
    // Form event listeners
    initializeFormListeners();
    
    // Notification handling
    initializeNotificationHandlers();
    
    // Setup follow-up toggle
    setupFollowUpToggle();

    // Initialize customer selection in add interaction form
    populateCustomerSelect();
});

// Initialize all modals
function initializeModals() {
    // Add Interaction Modal
    const addInteractionModal = document.getElementById('addInteractionModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelInteractionBtn = document.getElementById('cancelInteractionBtn');
    
    // Quick Lead Modal
    const quickLeadModal = document.getElementById('quickLeadModal');
    const closeQuickLeadBtn = document.getElementById('closeQuickLeadBtn');
    const cancelLeadBtn = document.getElementById('cancelLeadBtn');
    
    // Create functions to open modals
    window.openAddInteractionModal = function() {
        addInteractionModal.style.display = 'flex';
        document.body.classList.add('modal-open');
    };
    
    window.openQuickLeadModal = function() {
        quickLeadModal.style.display = 'flex';
        document.body.classList.add('modal-open');
    };

    // Close modals
    function closeModal(modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
    
    // Event listeners for closing modals
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => closeModal(addInteractionModal));
    }
    
    if (cancelInteractionBtn) {
        cancelInteractionBtn.addEventListener('click', () => closeModal(addInteractionModal));
    }
    
    if (closeQuickLeadBtn) {
        closeQuickLeadBtn.addEventListener('click', () => closeModal(quickLeadModal));
    }
    
    if (cancelLeadBtn) {
        cancelLeadBtn.addEventListener('click', () => closeModal(quickLeadModal));
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === addInteractionModal) {
            closeModal(addInteractionModal);
        }
        if (event.target === quickLeadModal) {
            closeModal(quickLeadModal);
        }
    });
}

// Populate customer select dropdown
function populateCustomerSelect() {
    const customerSelect = document.getElementById('customerSelect');
    
    if (customerSelect) {
        // Fetch customers assigned to the current sales rep
        fetch('get_customers.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear existing options
                    customerSelect.innerHTML = '<option value="">Select a customer</option>';
                    
                    // Add options for each customer
                    data.customers.forEach(customer => {
                        const option = document.createElement('option');
                        option.value = customer.customer_id;
                        option.textContent = `${customer.name} - ${customer.company || ''}`;
                        customerSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading customers:', error));
    }
}

// Initialize form listeners
function initializeFormListeners() {
    const addInteractionForm = document.getElementById('addInteractionForm');
    const quickLeadForm = document.getElementById('quickLeadForm');
    
    // Handle interaction form submission
    if (addInteractionForm) {
        addInteractionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Saving...';
            submitBtn.disabled = true;
            
            // Collect form data
            const formData = {
                lead_id: document.getElementById('leadId').value,
                customer_id: document.getElementById('customerSelect').value,
                interaction_type: document.getElementById('interactionType').value,
                interaction_date: document.getElementById('interactionDate').value,
                interaction_time: document.getElementById('interactionTime').value,
                description: document.getElementById('interactionNotes').value,
                follow_up_needed: document.getElementById('followUpNeeded').checked,
                follow_up_date: document.getElementById('followUpDate').value
            };
            
            // Submit the interaction data
            submitInteraction(formData)
                .then(response => {
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    // Close the modal
                    document.getElementById('addInteractionModal').style.display = 'none';
                    document.body.classList.remove('modal-open');
                    
                    // Reset the form
                    addInteractionForm.reset();
                    
                    // Show success message and update UI
                    if (response.success) {
                        showToast(response.message);
                        updateSchedule(response.data);
                        
                        // If a notification was created, update notification count
                        if (response.notification_created) {
                            updateNotificationCount(1);
                        }
                    } else {
                        showToast('Error: ' + response.message, 'error');
                    }
                })
                .catch(error => {
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    showToast('Error: ' + error.message, 'error');
                });
        });
        
        // When customer is selected, populate lead dropdown
        const customerSelect = document.getElementById('customerSelect');
        if (customerSelect) {
            customerSelect.addEventListener('change', function() {
                const selectedCustomerId = this.value;
                if (selectedCustomerId) {
                    populateLeadSelect(selectedCustomerId);
                }
            });
        }
    }
    
    // Populate lead select dropdown based on customer
    function populateLeadSelect(customerId) {
        const leadSelect = document.getElementById('leadId');
        
        if (leadSelect) {
            fetch(`get_leads.php?customer_id=${customerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear existing options
                        leadSelect.innerHTML = '<option value="">Select a lead</option>';
                        
                        // Add options for each lead
                        data.leads.forEach(lead => {
                            const option = document.createElement('option');
                            option.value = lead.lead_id;
                            option.textContent = `${lead.lead_status} (${lead.followed_up_date})`;
                            leadSelect.appendChild(option);
                        });
                        
                        // If no leads, create a new option
                        if (data.leads.length === 0) {
                            const option = document.createElement('option');
                            option.value = "new";
                            option.textContent = "Create New Lead";
                            leadSelect.appendChild(option);
                        }
                    }
                })
                .catch(error => console.error('Error loading leads:', error));
        }
    }
    
    // Handle quick lead form submission
    if (quickLeadForm) {
        quickLeadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Saving...';
            submitBtn.disabled = true;
            
            // Collect form data
            const formData = {
                company: document.getElementById('companyName').value,
                name: document.getElementById('contactFirstName').value + ' ' + document.getElementById('contactLastName').value,
                email: document.getElementById('contactEmail').value,
                phone_number: document.getElementById('contactPhone').value,
                address: document.getElementById('contactAddress')?.value || '',
                lead_source: document.getElementById('leadSource').value,
                notes: document.getElementById('leadNotes').value
            };
            
            // Submit the lead data
            submitLead(formData)
                .then(response => {
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    // Close the modal
                    document.getElementById('quickLeadModal').style.display = 'none';
                    document.body.classList.remove('modal-open');
                    
                    // Reset the form
                    quickLeadForm.reset();
                    
                    // Show success message and update UI
                    if (response.success) {
                        showToast(response.message);
                        updateLeadsTable(response.data);
                        // Update the stats (new leads count)
                        updateLeadsCount();
                    } else {
                        showToast('Error: ' + response.message, 'error');
                    }
                })
                .catch(error => {
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    showToast('Error: ' + error.message, 'error');
                });
        });
    }
}

// Setup follow-up toggle functionality
function setupFollowUpToggle() {
    const followUpNeeded = document.getElementById('followUpNeeded');
    const followUpDateGroup = document.getElementById('followUpDateGroup');
    
    if (followUpNeeded && followUpDateGroup) {
        followUpNeeded.addEventListener('change', function() {
            followUpDateGroup.style.display = this.checked ? 'block' : 'none';
        });
        
        // Set default follow-up date to 3 days from now
        const followUpDate = document.getElementById('followUpDate');
        if (followUpDate) {
            const date = new Date();
            date.setDate(date.getDate() + 3);
            followUpDate.value = date.toISOString().substr(0, 10);
        }
    }
}

// Handle notification interactions
function initializeNotificationHandlers() {
    // Mark notifications as read
    const checkButtons = document.querySelectorAll('.notification-actions .btn-icon');
    
    checkButtons.forEach(button => {
        button.addEventListener('click', function() {
            const notificationItem = this.closest('.notification-item');
            if (notificationItem) {
                notificationItem.classList.remove('new');
                
                // Update notification count
                updateNotificationCount(-1);
                
                // In a real application, you would send a request to mark the notification as read
                const reminderId = notificationItem.dataset.reminderId;
                if (reminderId) {
                    markNotificationAsRead(reminderId);
                }
            }
        });
    });
}

// Mark notification as read in the database
function markNotificationAsRead(reminderId) {
    // This would be a real API call to mark the notification as read
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ reminder_id: reminderId })
    })
    .then(response => response.json())
    .catch(error => console.error('Error:', error));
}

// Update notification count in the header
function updateNotificationCount(change = 0) {
    const notificationCount = document.querySelector('.notification-count');
    
    if (notificationCount) {
        let count = parseInt(notificationCount.textContent || '0', 10);
        
        if (change !== 0) {
            // Add the change (positive or negative)
            count += change;
            
            // Ensure count doesn't go below zero
            count = Math.max(0, count);
            
            // Update the displayed count
            notificationCount.textContent = count;
        } else {
            // If no change specified, count new notifications
            const newNotifications = document.querySelectorAll('.notification-item.new');
            count = newNotifications.length;
            notificationCount.textContent = count;
        }
        
        // Hide the count if zero
        if (count === 0) {
            notificationCount.style.display = 'none';
        } else {
            notificationCount.style.display = 'flex';
        }
    }
}

// Update lead count in the header stats
function updateLeadsCount() {
    const newLeadsElement = document.querySelector('.stats-section .stat-card:nth-child(2) .stat-value');
    if (newLeadsElement) {
        // Get current count and increment it
        let count = parseInt(newLeadsElement.textContent, 10);
        count++;
        newLeadsElement.textContent = count;
    }
}

// Submit interaction data to the server
async function submitInteraction(formData) {
    try {
        const response = await fetch('add_interaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        return { success: false, message: error.message };
    }
}

// Submit lead data to the server
async function submitLead(formData) {
    try {
        const response = await fetch('add_lead.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        return { success: false, message: error.message };
    }
}

// Update the schedule timeline with new interaction
function updateSchedule(interactionData) {
    const scheduleTimeline = document.querySelector('.schedule-timeline');
    
    if (scheduleTimeline) {
        // Create a new timeline item
        const newItem = document.createElement('div');
        newItem.className = 'timeline-item';
        
        // Format time
        let timeDisplay = "-";
        if (interactionData.interaction_time) {
            const date = new Date(`2000-01-01T${interactionData.interaction_time}`);
            timeDisplay = date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }
        
        // Get interaction title
        let title = 'Follow-up';
        switch (interactionData.interaction_type) {
            case 'call':
                title = 'Client Call';
                break;
            case 'meeting':
                title = 'Meeting';
                break;
            case 'email':
                title = 'Send Email';
                break;
        }
        
        newItem.innerHTML = `
            <div class="timeline-time">${timeDisplay}</div>
            <div class="timeline-content">
                <div class="timeline-title">${title}</div>
                <div class="timeline-details">
                    ${interactionData.customer_name} - ${interactionData.company || ''}
                    ${interactionData.description ? `<br>${interactionData.description}` : ''}
                </div>
            </div>
        `;
        
        // Add the new item to the timeline
        const emptyMessage = scheduleTimeline.querySelector('.empty-message');
        if (emptyMessage) {
            scheduleTimeline.removeChild(emptyMessage);
        }
        
        scheduleTimeline.appendChild(newItem);
    }
}

// Update the leads table with a new lead
function updateLeadsTable(leadData) {
    const leadsTable = document.querySelector('.data-table tbody');
    
    if (leadsTable) {
        // Create a new row for the lead
        const newRow = document.createElement('tr');
        
        // Format the date
        const today = new Date();
        const formattedDate = `${today.toLocaleString('default', { month: 'short' })} ${today.getDate()}, ${today.getFullYear()}`;
        
        newRow.innerHTML = `
            <td>
                <div class="lead-info">
                    <div class="lead-contact">${leadData.name}</div>
                </div>
            </td>
            <td>${leadData.company || 'N/A'}</td>
            <td>${formattedDate}</td>
            <td><span class="status-badge status-lead">${leadData.lead_status || 'New'}</span></td>
        `;
        
        // Add the new row to the top of the table
        const emptyMessage = leadsTable.querySelector('.empty-message');
        if (emptyMessage) {
            leadsTable.removeChild(emptyMessage);
        }
        
        if (leadsTable.firstChild) {
            leadsTable.insertBefore(newRow, leadsTable.firstChild);
        } else {
            leadsTable.appendChild(newRow);
        }
    }
}

// Helper function to capitalize the first letter
function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Show toast notification
function showToast(message, type = 'success') {
    // Create toast element if it doesn't exist
    let toast = document.getElementById('toast');
    
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    
    // Set toast message and show it
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}