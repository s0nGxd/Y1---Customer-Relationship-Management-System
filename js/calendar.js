// Calendar variables
let currentDate = new Date();
let currentView = 'month'; // Default view


document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const modal = document.getElementById('interaction-modal');
    const detailsModal = document.getElementById('interaction-details-modal');
    const notificationPanel = document.getElementById('notification-panel');
    
    // Buttons
    const newBtn = document.getElementById('new-interaction-btn');
    const closeBtns = document.querySelectorAll('.close, .close-details, #cancel-btn');
    const notificationBell = document.querySelector('.notification-bell');
    const closeNotifications = document.getElementById('close-notifications');
    const viewButtons = document.querySelectorAll('.view-toggle .btn');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    
    // Form elements
    const setReminder = document.getElementById('set-reminder');
    const reminderDetails = document.getElementById('reminder-details');
    const filterSelect = document.getElementById('type-filter-select');
    
    // Initialize calendar
    updateCalendarHeader();
    
    // Show new interaction modal
    newBtn.addEventListener('click', function() {
        document.getElementById('form-action').value = 'create';
        document.getElementById('interaction-form').reset();
        document.getElementById('interaction-id').value = '';
        document.getElementById('interaction-date').valueAsDate = new Date();
        document.getElementById('interaction-time').value = '09:00';
        
        // Set follow-up date to next week by default
        const followUpDate = new Date();
        followUpDate.setDate(followUpDate.getDate() + 7);
        document.getElementById('follow-up-date').valueAsDate = followUpDate;
        
        modal.style.display = 'block';
    });
    
    // Close modals
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modal.style.display = 'none';
            detailsModal.style.display = 'none';
        });
    });
    
    // Toggle notification panel
    notificationBell.addEventListener('click', function() {
        notificationPanel.style.display = notificationPanel.style.display === 'block' ? 'none' : 'block';
    });
    
    closeNotifications.addEventListener('click', function() {
        notificationPanel.style.display = 'none';
    });
    
    // Toggle reminder details
    setReminder.addEventListener('change', function() {
        reminderDetails.style.display = this.checked ? 'block' : 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) modal.style.display = 'none';
        if (event.target === detailsModal) detailsModal.style.display = 'none';
    });
    
    // Toggle view buttons
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            currentView = this.id.replace('-view', '');
            
            // Here we would refresh the calendar view
            // For now, just update the header text
            updateCalendarHeader();
        });
    });
    
    // Previous and Next buttons
    prevBtn.addEventListener('click', function() {
        if (currentView === 'month') {
            currentDate.setMonth(currentDate.getMonth() - 1);
        } else if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() - 7);
        } else if (currentView === 'day') {
            currentDate.setDate(currentDate.getDate() - 1);
        }
        updateCalendarHeader();
    });
    
    nextBtn.addEventListener('click', function() {
        if (currentView === 'month') {
            currentDate.setMonth(currentDate.getMonth() + 1);
        } else if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() + 7);
        } else if (currentView === 'day') {
            currentDate.setDate(currentDate.getDate() + 1);
        }
        updateCalendarHeader();
    });
    
    // Filter interactions by type
    filterSelect.addEventListener('change', function() {
        const filterValue = this.value;
        const interactions = document.querySelectorAll('.interaction');
        
        interactions.forEach(interaction => {
            if (filterValue === 'all' || interaction.classList.contains('category-' + filterValue)) {
                interaction.style.display = '';
            } else {
                interaction.style.display = 'none';
            }
        });
    });
    
    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // Update calendar header based on current view and date
    function updateCalendarHeader() {
        const header = document.getElementById('current-period');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
        
        // Remove previous calendar grid
        const calendarGrid = document.getElementById('calendar-grid');
        calendarGrid.innerHTML = '';
        
        // Generate appropriate view based on currentView
        if (currentView === 'month') {
            header.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
            generateMonthView(currentDate);
        } else if (currentView === 'week') {
            // Calculate week start/end
            const weekStart = new Date(currentDate);
            weekStart.setDate(currentDate.getDate() - currentDate.getDay());
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 6);
            
            header.textContent = 
                `${formatDate(weekStart)} - ${formatDate(weekEnd)}, ${weekStart.getFullYear()}`;
            generateWeekView(weekStart);
        } else if (currentView === 'day') {
            header.textContent = 
                `${monthNames[currentDate.getMonth()]} ${currentDate.getDate()}, ${currentDate.getFullYear()}`;
            generateDayView(currentDate);
        }
    }

    function getInteractionsForDate(date) {
        // Create a date string in YYYY-MM-DD format, accounting for timezone
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const dateStr = `${year}-${month}-${day}`;
        
        let html = '<div class="interactions-container">';
        
        serverInteractions.forEach(interaction => {
            // Compare with the formatted date string
            if (interaction.interaction_date === dateStr) {
                html += `
                    <div class="interaction category-${interaction.interaction_type}" 
                        onclick="showInteractionDetails(${interaction.interaction_id}); event.stopPropagation();">
                        <span class="interaction-time">${interaction.interaction_time.substring(0,5)}</span>
                        <span class="interaction-title">${interaction.customer_name}</span>
                    </div>
                `;
            }
        });
        
        html += '</div>';
        return html;
    }

    function handleCellClick(date) {
        // Open modal with selected date
        document.getElementById('form-action').value = 'create';
        document.getElementById('interaction-form').reset();
        document.getElementById('interaction-id').value = '';
        
        // Ensure we're setting the date correctly with local timezone adjustment
        const localDate = new Date(date);
        document.getElementById('interaction-date').valueAsDate = localDate;
        document.getElementById('interaction-time').value = '09:00';
        
        // Set follow-up date to next week by default
        const followUpDate = new Date(localDate);
        followUpDate.setDate(followUpDate.getDate() + 7);
        document.getElementById('follow-up-date').valueAsDate = followUpDate;
        
        document.getElementById('interaction-modal').style.display = 'block';
    }

    // Fix the calendar cell click event handler
    document
    .getElementById('calendar-grid')
    .addEventListener('click', function (event) {
        const cell = event.target.closest('.calendar-cell');
        if (cell && !event.target.closest('.interaction')) {
        // Extract date from the cell
        const dateNumber = cell.querySelector('.date-number').textContent;
        const monthYear = document.getElementById('current-period').textContent;
        
        // Parse the date
        const date = parseCalendarCellDate(dateNumber, monthYear);
        if (date) {
            handleCellClick(date);
        }
        }
    });

    // Add this helper function to parse dates from cells
    function parseCalendarCellDate(dateNumber, monthYear) {
        try {
            const day = parseInt(dateNumber);
            const monthNames = {
            'January': 0, 'February': 1, 'March': 2, 'April': 3, 'May': 4, 'June': 5,
            'July': 6, 'August': 7, 'September': 8, 'October': 9, 'November': 10, 'December': 11
            };
            
            let month, year;
            
            // Extract month and year from different view formats
            if (monthYear.includes('-')) {
                // Week view format
                const yearPart = monthYear.split(',')[1].trim();
                year = parseInt(yearPart);
                month = new Date().getMonth(); // Default to current month if not specified
            } else {
                // Month view format
                const parts = monthYear.split(' ');
                month = monthNames[parts[0]];
                year = parseInt(parts[1]);
            }
            
            // Create date with no time component to avoid timezone issues
            return new Date(Date.UTC(year, month, day, 12, 0, 0));
        } catch (e) {
            console.error('Error parsing date:', e);
            return null;
        }
    }
    
    // Helper function to format dates
    function formatDate(date) {
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${monthNames[date.getMonth()]} ${date.getDate()}`;
    }
    
    // Generate month view grid
    function generateMonthView(date) {
        const start = new Date(date.getFullYear(), date.getMonth(), 1);
        const end = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        const startDay = start.getDay(); // 0 (Sunday) - 6 (Saturday)
        
        const calendarGrid = document.getElementById('calendar-grid');
        const headerRow = document.createElement('div');
        headerRow.className = 'weekday-header';
        headerRow.innerHTML = `<div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
                                <div>Thu</div><div>Fri</div><div>Sat</div>`;
        calendarGrid.appendChild(headerRow);
    
        let current = new Date(start);
        current.setDate(current.getDate() - startDay); // Start from first Sunday
    
        for (let week = 0; week < 6; week++) {
            const row = document.createElement('div');
            row.className = 'calendar-row';
            
            for (let day = 0; day < 7; day++) {
                const cellDate = new Date(current);
                const isCurrentMonth = cellDate.getMonth() === date.getMonth();
                const isToday = cellDate.toDateString() === new Date().toDateString();
                
                const cell = document.createElement('div');
                cell.className = `calendar-cell ${isToday ? 'today' : ''} ${!isCurrentMonth ? 'other-month' : ''}`;
                cell.innerHTML = `
                    <div class="date-number">${cellDate.getDate()}</div>
                    ${getInteractionsForDate(cellDate)}
                `;
                
                row.appendChild(cell);
                current.setDate(current.getDate() + 1);
            }
            
            calendarGrid.appendChild(row);
            
            // Stop if we've passed the end of the month
            if (current > end && current.getMonth() !== date.getMonth()) break;
        }
    }
    
    // Generate week view grid
    function generateWeekView(startDate) {
        const calendarGrid = document.getElementById('calendar-grid');
        const headerRow = document.createElement('div');
        headerRow.className = 'weekday-header';
        headerRow.innerHTML = `<div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
                                <div>Thu</div><div>Fri</div><div>Sat</div>`;
        calendarGrid.appendChild(headerRow);
    
        const row = document.createElement('div');
        row.className = 'calendar-row';
        
        let current = new Date(startDate);
        
        for (let day = 0; day < 7; day++) {
            const cellDate = new Date(current);
            const isToday = cellDate.toDateString() === new Date().toDateString();
            
            const cell = document.createElement('div');
            cell.className = `calendar-cell ${isToday ? 'today' : ''}`;
            cell.innerHTML = `
                <div class="date-number">${cellDate.getDate()}</div>
                ${getInteractionsForDate(cellDate)}
            `;
            
            row.appendChild(cell);
            current.setDate(current.getDate() + 1);
        }
        
        calendarGrid.appendChild(row);
    }
    
    // Generate day view grid
    function generateDayView(date) {
        const calendarGrid = document.getElementById('calendar-grid');
        const row = document.createElement('div');
        row.className = 'calendar-row';
        
        const isToday = date.toDateString() === new Date().toDateString();
        
        const cell = document.createElement('div');
        cell.className = `calendar-cell ${isToday ? 'today' : ''}`;
        cell.innerHTML = `
            <div class="date-number">${date.getDate()}</div>
            ${getInteractionsForDate(date)}
        `;
        
        row.appendChild(cell);
        calendarGrid.appendChild(row);
    }
});

// Show interaction details
function showInteractionDetails(interactionId) {
    fetch('calendar.php?interaction_id=' + interactionId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('interaction-details-content').innerHTML = html;
            document.getElementById('delete-interaction-id').value = interactionId;
            document.getElementById('interaction-details-modal').style.display = 'block';
            
            // Set up edit button to load interaction data
            setupEditButton(interactionId);
        })
        .catch(error => {
            console.error('Error fetching interaction details:', error);
            alert('Failed to load interaction details. Please try again.');
        });
}

// Edit interaction
function setupEditButton(interactionId) {
    document.getElementById('edit-interaction-btn').addEventListener('click', function() {
        const detailsModal = document.getElementById('interaction-details-modal');
        
        // Close details modal
        detailsModal.style.display = 'none';
        
        // Extract data from the details view
        const detailsContent = document.getElementById('interaction-details-content');
        
        // Set form to edit mode
        document.getElementById('form-action').value = 'update';
        document.getElementById('interaction-id').value = interactionId;
        
        // Extract and populate data from the details view
        const form = document.getElementById('interaction-form');
        
        // Get the customer name and company from the details
        const titleText = detailsContent.querySelector('h3').textContent;
        const typeText = titleText.split(' with ')[0].toLowerCase();
        const customerText = titleText.split(' with ')[1];
        
        // Get date and time
        const dateText = detailsContent.querySelector('p:nth-child(2)').textContent;
        const dateMatch = dateText.match(/Date: (.*) at (.*)/);
        const dateValue = dateMatch ? new Date(dateMatch[1]) : new Date();
        const timeValue = dateMatch ? dateMatch[2] : '09:00';
        
        // Get description
        const descriptionText = detailsContent.querySelector('p:nth-child(4)').textContent;
        const descriptionValue = descriptionText.replace('Description: ', '');
        
        // Get follow-up date
        const followUpText = detailsContent.querySelector('p:nth-child(5)').textContent;
        const followUpValue = followUpText.replace('Follow-up Date: ', '');
        
        // Find the correct lead in the dropdown
        const leadSelect = document.getElementById('lead-select');
        const options = Array.from(leadSelect.options);
        
        // Try to find match by name
        let selectedIndex = 0;
        options.forEach((option, index) => {
            if (option.text.includes(customerText)) {
                selectedIndex = index;
            }
        });
        
        // Set form values
        leadSelect.selectedIndex = selectedIndex;
        document.getElementById('interaction-type').value = typeText;
        document.getElementById('interaction-description').value = descriptionValue;
        
        // Format the date for the input field (YYYY-MM-DD)
        const formatDate = (date) => {
            const d = new Date(date);
            let month = '' + (d.getMonth() + 1);
            let day = '' + d.getDate();
            const year = d.getFullYear();
            
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            
            return [year, month, day].join('-');
        };
        
        document.getElementById('interaction-date').value = formatDate(dateValue);
        document.getElementById('interaction-time').value = timeValue;
        
        // Set reminder checkbox and follow-up date
        const hasFollowUp = followUpValue !== 'None';
        document.getElementById('set-reminder').checked = hasFollowUp;
        document.getElementById('reminder-details').style.display = hasFollowUp ? 'block' : 'none';
        
        if (hasFollowUp) {
            document.getElementById('follow-up-date').value = formatDate(followUpValue);
        }
        
        // Show the form modal
        document.getElementById('interaction-modal').style.display = 'block';
    }, { once: true }); // Use once to prevent multiple bindings
}

// Add interaction by clicking on a date
document.addEventListener('DOMContentLoaded', function() {
    // Fix the calendar cell click event handler
    document
    .getElementById('calendar-grid')
    .addEventListener('click', function (event) {
        const cell = event.target.closest('.calendar-cell');
        if (cell && !event.target.closest('.interaction')) {
            // Extract date from the cell
            const dateNumber = cell.querySelector('.date-number').textContent;
            const monthYear = document.getElementById('current-period').textContent;
            
            // Parse the date
            const date = parseCalendarCellDate(dateNumber, monthYear);
            if (date) {
                handleCellClick(date);
            }
        }
    });

    // Helper function to parse dates from cells
    function parseCalendarCellDate(dateNumber, monthYear) {
        try {
            const day = parseInt(dateNumber);
            const monthNames = {
                'January': 0, 'February': 1, 'March': 2, 'April': 3, 'May': 4, 'June': 5,
                'July': 6, 'August': 7, 'September': 8, 'October': 9, 'November': 10, 'December': 11
            };
            
            let month, year;
            
            // Extract month and year from different view formats
            if (monthYear.includes('-')) {
                // Week view format
                const yearPart = monthYear.split(',')[1].trim();
                year = parseInt(yearPart);
                month = new Date().getMonth(); // Default to current month if not specified
            } else {
                // Month view format
                const parts = monthYear.split(' ');
                month = monthNames[parts[0]];
                year = parseInt(parts[1]);
            }
            
            // Create date with no time component to avoid timezone issues
            return new Date(Date.UTC(year, month, day, 12, 0, 0));
        } catch (e) {
            console.error('Error parsing date:', e);
            return null;
        }
    }
    
    function handleCellClick(date) {
        // Open modal with selected date
        document.getElementById('form-action').value = 'create';
        document.getElementById('interaction-form').reset();
        document.getElementById('interaction-id').value = '';
        
        // Ensure we're setting the date correctly with local timezone adjustment
        const localDate = new Date(date);
        document.getElementById('interaction-date').valueAsDate = localDate;
        document.getElementById('interaction-time').value = '09:00';
        
        // Set follow-up date to next week by default
        const followUpDate = new Date(localDate);
        followUpDate.setDate(followUpDate.getDate() + 7);
        document.getElementById('follow-up-date').valueAsDate = followUpDate;
        
        document.getElementById('interaction-modal').style.display = 'block';
    }
    
    // Prevent delete action without confirmation
    const deleteForm = document.getElementById('delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            if (!confirm('Are you sure you want to delete this interaction?')) {
                event.preventDefault();
            }
        });
    }
});