/*!
 * Motor Management System - Unified JavaScript
 * Version: 3.0.0 - Optimized & Minimal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initSidebar();
    initDropdowns();
    initTables();
    initForms();
    initAlerts();
});

// Sidebar functionality
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        });
    }
    
    // Handle dropdown navigation - Enhanced version
    const navParents = document.querySelectorAll('.nav-parent');
    navParents.forEach(parent => {
        parent.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdownId = this.getAttribute('data-dropdown');
            const dropdown = document.getElementById(dropdownId);
            
            if (!dropdown) return;
            
            const isCurrentlyOpen = dropdown.classList.contains('open');
            
            // Close all dropdowns
            document.querySelectorAll('.nav-dropdown').forEach(dd => {
                dd.classList.remove('open');
            });
            document.querySelectorAll('.nav-parent').forEach(np => {
                np.classList.remove('open');
            });
            
            // Toggle current dropdown if it wasn't open
            if (!isCurrentlyOpen) {
                dropdown.classList.add('open');
                this.classList.add('open');
            }
        });
    });
}

// Dropdown functionality - Enhanced version
function initDropdowns() {
    // Close dropdowns when clicking outside sidebar
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.sidebar')) {
            document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                dropdown.classList.remove('open');
            });
            document.querySelectorAll('.nav-parent').forEach(parent => {
                parent.classList.remove('open');
            });
        }
    });
}

// Table functionality
function initTables() {
    // Search functionality for all tables
    const searchInputs = document.querySelectorAll('[id$="searchInput"], [id$="Search"]');
    
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const tableId = this.id.replace('Search', 'Table').replace('searchInput', 'Table');
            const table = document.getElementById(tableId) || 
                         document.querySelector('.table') || 
                         this.closest('.section').querySelector('table');
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            }
        });
    });
    
    // Table row hover effects
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'var(--bg-secondary)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    });
}

// Form functionality
function initForms() {
    // Form validation and submission
    const forms = document.querySelectorAll('form[id$="Form"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger)';
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--border)';
                }
            });
            
            if (isValid) {
                // Call the specific form handler if it exists
                const formId = this.id;
                const apiUrl = this.getAttribute('data-api') || 
                              `api/${formId.replace('Form', '').toLowerCase()}.php`;
                
                if (window.submitForm) {
                    window.submitForm(formId, apiUrl);
                }
            } else {
                showAlert('لطفاً تمام فیلدهای الزامی را پر کنید', 'danger');
            }
        });
    });
    
    // Auto-generate product codes
    const productCodeField = document.getElementById('productCode');
    if (productCodeField && window.generateProductCode) {
        window.generateProductCode();
    }
}

// Alert system
function initAlerts() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertClass = `alert-${type}`;
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
}

// Confirm delete function
function confirmDelete(id, apiUrl, name) {
    if (confirm(`آیا از حذف "${name}" اطمینان دارید؟`)) {
        deleteItem(id, apiUrl);
    }
}

// Delete item function
async function deleteItem(id, apiUrl) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        
        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                         document.querySelector('input[name="csrf_token"]')?.value;
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }

        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (result.success) {
            showAlert('آیتم با موفقیت حذف شد', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message || 'خطا در حذف آیتم', 'danger');
        }
    } catch (error) {
        showAlert('خطا در ارتباط با سرور', 'danger');
    }
}

// Submit form function
async function submitForm(formId, apiUrl) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                     document.querySelector('input[name="csrf_token"]')?.value;
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (result.success) {
            showAlert('عملیات با موفقیت انجام شد', 'success');
            form.reset();
            
            // Auto-generate product code if needed
            if (window.generateProductCode && formId === 'addProductForm') {
                window.generateProductCode();
            }
            
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message || 'خطا در انجام عملیات', 'danger');
        }
    } catch (error) {
        showAlert('خطا در ارتباط با سرور', 'danger');
    }
}

// Theme toggle function
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

// Load saved theme
function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

// Initialize theme on load
loadTheme();

// Export functions for global use
window.showAlert = showAlert;
window.confirmDelete = confirmDelete;
window.deleteItem = deleteItem;
window.submitForm = submitForm;
window.toggleTheme = toggleTheme;