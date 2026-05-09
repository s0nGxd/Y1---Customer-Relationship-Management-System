// customer.js - Customer Relationship Management System

// Sample customer data - In a real application, this would come from a database
const customers = [
    {
        id: 1,
        company: "Acme Corporation",
        contactFirst: "John",
        contactLast: "Doe",
        email: "john.doe@acme.com",
        phone: "(555) 123-4567",
        address: "123 Business Ave, Suite 100, New York, NY 10001",
        status: "active",
        lastContact: new Date(2025, 3, 18), // April 18, 2025
        nextFollowUp: new Date(2025, 3, 25), // April 25, 2025
        value: 87500,
        customerSince: new Date(2023, 2, 15), // March 15, 2023
        notes: "Key enterprise client with multiple departments using our services",
        salesRepId: "SR001",
        interactions: [
            {
                type: "Meeting",
                date: new Date(2025, 3, 18),
                content: "Quarterly review meeting. Discussed expansion to international markets."
            },
            {
                type: "Email",
                date: new Date(2025, 3, 10),
                content: "Sent proposal for new project with marketing department."
            },
            {
                type: "Call",
                date: new Date(2025, 3, 5),
                content: "Discussed ongoing implementation issues. Resolved server configuration problem."
            },
            {
                type: "Meeting",
                date: new Date(2025, 2, 20),
                content: "Product demo for new features. Client was very impressed with automation capabilities."
            }
        ]
    },
    // ... other customer data ...
];

// DOM elements
const customerTableBody = document.getElementById('customerTableBody');
const addCustomerBtn = document.getElementById('addCustomerBtn');
const addCustomerModal = document.getElementById('addCustomerModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelAddBtn = document.getElementById('cancelAddBtn');
const addCustomerForm = document.getElementById('addCustomerForm');
const customerSearch = document.getElementById('customerSearch');
const customerStatusFilter = document.getElementById('customerStatusFilter');
const assignedToFilter = document.getElementById('assignedToFilter');

// Initialize the application
function init() {
    renderCustomerTable(customers);
    setupEventListeners();
}

// Setup event listeners
function setupEventListeners() {
    // Open add customer modal
    addCustomerBtn.addEventListener('click', () => {
        addCustomerModal.classList.add('active');
    });

    // Close add customer modal
    closeModalBtn.addEventListener('click', () => {
        addCustomerModal.classList.remove('active');
    });

    cancelAddBtn.addEventListener('click', () => {
        addCustomerModal.classList.remove('active');
    });

    // Submit add customer form
    addCustomerForm.addEventListener('submit', handleAddCustomer);

    // Filter customers on search
    customerSearch.addEventListener('input', filterCustomers);
    customerStatusFilter.addEventListener('change', filterCustomers);
    assignedToFilter.addEventListener('change', filterCustomers);

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === addCustomerModal) {
            addCustomerModal.classList.remove('active');
        }
    });
}

// Render customer table with provided data
function renderCustomerTable(customersData) {
    customerTableBody.innerHTML = '';
    
    if (customersData.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="7" style="text-align: center; padding: 30px;">
                No customers found matching your criteria
            </td>
        `;
        customerTableBody.appendChild(emptyRow);
        return;
    }
    
    customersData.forEach(customer => {
        const row = document.createElement('tr');
        
        // Create row based on HTML table column structure
        row.innerHTML = `
            <td>${customer.id}</td>
            <td>
                <div class="customer-name">
                    <div class="company-avatar">${getInitials(customer.company)}</div>
                    <div>
                        ${customer.contactFirst} ${customer.contactLast}
                    </div>
                </div>
            </td>
            <td>${customer.salesRepId}</td>
            <td>${customer.company}</td>
            <td>${customer.email}</td>
            <td>${customer.phone}</td>
            <td>${customer.address}</td>
        `;
        
        customerTableBody.appendChild(row);
        
        // Make rows clickable to view details (in a real app)
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => {
            alert(`You clicked on ${customer.company}.\nIn a complete implementation, this would show detailed customer information.`);
        });
    });
}

// Handle adding a new customer
function handleAddCustomer(e) {
    e.preventDefault();
    
    // Create new customer object from form values
    const newCustomer = {
        id: customers.length + 1,
        company: document.getElementById('companyName').value,
        contactFirst: document.getElementById('contactFirstName').value,
        contactLast: document.getElementById('contactLastName').value,
        email: document.getElementById('contactEmail').value,
        phone: document.getElementById('contactPhone').value,
        address: document.getElementById('customerAddress').value,
        status: 'prospect', // Default for new customers
        salesRepId: 'SR001', // Default sales rep
        lastContact: new Date(),
        nextFollowUp: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // 7 days from now
        value: 0, // Initial value
        customerSince: null,
        notes: "",
        interactions: [
            {
                type: "System",
                date: new Date(),
                content: "Customer created in system"
            }
        ]
    };
    
    // Add to customers array (in a real app, this would be a database operation)
    customers.unshift(newCustomer);
    
    // Update table
    renderCustomerTable(customers);
    
    // Close modal and reset form
    addCustomerModal.classList.remove('active');
    addCustomerForm.reset();
    
    // Show a success message
    alert('Customer added successfully!');
}

// Filter customers based on search and filter values
function filterCustomers() {
    const searchTerm = customerSearch.value.toLowerCase();
    const statusFilter = customerStatusFilter.value;
    const assignedFilter = assignedToFilter.value;
    
    const filteredCustomers = customers.filter(customer => {
        // Search filter
        const matchesSearch = 
            customer.company.toLowerCase().includes(searchTerm) ||
            customer.contactFirst.toLowerCase().includes(searchTerm) ||
            customer.contactLast.toLowerCase().includes(searchTerm) ||
            customer.email.toLowerCase().includes(searchTerm) ||
            customer.phone.includes(searchTerm);
        
        // Status filter
        const matchesStatus = statusFilter === 'all' || customer.status === statusFilter;
        
        // Assigned filter
        const matchesAssigned = assignedFilter === 'all' || 
            (assignedFilter === 'current' && customer.salesRepId === 'SR001') || // Current user's customers
            (assignedFilter === 'unassigned' && !customer.salesRepId); // Unassigned customers
        
        return matchesSearch && matchesStatus && matchesAssigned;
    });
    
    renderCustomerTable(filteredCustomers);
}

// Helper functions
function getInitials(name) {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
}

function capitalizeFirst(string) {
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

function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0
    }).format(value);
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', init);