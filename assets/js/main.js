// Modal Management
function openModal(modalId) {
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function closeModal(modalId) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) {
        modal.hide();
    }
}

// Form Submission
async function submitForm(formId, endpoint) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    // Add CSRF token if available
    if (typeof CSRF !== 'undefined') {
        formData.append('csrf_token', CSRF);
    }
    
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('عملیات با موفقیت انجام شد', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(result.message || 'خطا در انجام عملیات', 'danger');
        }
    } catch (error) {
        showAlert('خطا در ارتباط با سرور', 'danger');
    }
}

// Alert System
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <span class="alert-inner--text">${message}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at top of main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
    } else {
        document.body.insertBefore(alertDiv, document.body.firstChild);
    }
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Delete Confirmation
function confirmDelete(id, endpoint, itemName) {
    if (confirm(`آیا از حذف ${itemName} اطمینان دارید؟`)) {
        deleteItem(id, endpoint);
    }
}

async function deleteItem(id, endpoint) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        
        // Add CSRF token if available
        if (typeof CSRF !== 'undefined') {
            formData.append('csrf_token', CSRF);
        }
        
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('حذف با موفقیت انجام شد', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(result.message || 'خطا در حذف', 'danger');
        }
    } catch (error) {
        showAlert('خطا در ارتباط با سرور', 'danger');
    }
}

// Search Functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    const rows = table.getElementsByTagName('tr');
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length; j++) {
                if (cells[j].textContent.toLowerCase().includes(filter)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    });
}

// Invoice Calculation
function calculateInvoice() {
    const rows = document.querySelectorAll('#invoiceItems tr');
    let total = 0;
    
    rows.forEach(row => {
        const quantityInput = row.querySelector('.quantity');
        const priceInput = row.querySelector('.price');
        const subtotalElement = row.querySelector('.subtotal');
        
        if (quantityInput && priceInput && subtotalElement) {
            const quantity = parseFloat(quantityInput.value || 0);
            const price = parseFloat(priceInput.value || 0);
            const subtotal = quantity * price;
            
            subtotalElement.textContent = subtotal.toLocaleString();
            total += subtotal;
        }
    });
    
    const discountInput = document.getElementById('discount');
    const discount = discountInput ? parseFloat(discountInput.value || 0) : 0;
    const finalAmount = total - discount;
    
    const totalAmountElement = document.getElementById('totalAmount');
    const finalAmountElement = document.getElementById('finalAmount');
    
    if (totalAmountElement) {
        totalAmountElement.textContent = total.toLocaleString();
    }
    
    if (finalAmountElement) {
        finalAmountElement.textContent = finalAmount.toLocaleString();
    }
}

// Print Invoice
function printInvoice(invoiceId) {
    window.open(`print_invoice.php?id=${invoiceId}`, '_blank');
}

// Mobile Menu Toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.navbar-vertical');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Initialize tooltips and popovers when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Close mobile sidebar when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.navbar-vertical');
        const toggleButton = document.querySelector('[data-bs-toggle="collapse"]');
        
        if (sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(event.target) && !toggleButton?.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
});

// Format numbers in Persian/Farsi
function formatNumber(num) {
    return new Intl.NumberFormat('fa-IR').format(num);
}

// Validate form inputs
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredInputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}