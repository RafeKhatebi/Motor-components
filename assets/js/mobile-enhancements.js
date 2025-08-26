/**
 * Mobile Enhancements for Motor System
 * Improves touch interactions and mobile usability
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Check if device is mobile
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Add mobile-specific enhancements
        initMobileEnhancements();
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const nowMobile = window.innerWidth <= 768;
        if (nowMobile !== isMobile) {
            location.reload(); // Reload to apply proper styles
        }
    });
});

function initMobileEnhancements() {
    
    // 1. Improve dropdown behavior on mobile
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.nextElementSibling;
            if (menu && menu.classList.contains('dropdown-menu')) {
                menu.classList.toggle('show');
            }
        });
    });
    
    // 2. Auto-close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        const navbarCollapse = document.getElementById('navbarNav');
        const navbarToggler = document.querySelector('.navbar-toggler');
        
        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            if (!navbarCollapse.contains(e.target) && !navbarToggler.contains(e.target)) {
                navbarCollapse.classList.remove('show');
            }
        }
    });
    
    // 3. Improve table scrolling on mobile
    const tables = document.querySelectorAll('.table-responsive');
    tables.forEach(table => {
        table.style.overflowX = 'auto';
        table.style.webkitOverflowScrolling = 'touch';
    });
    
    // 4. Add touch-friendly button sizing
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        if (!btn.classList.contains('btn-lg')) {
            btn.style.minHeight = '44px';
            btn.style.minWidth = '44px';
        }
    });
    
    // 5. Improve modal behavior on mobile
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';
            
            // Focus first input in modal
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 300);
            }
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            // Restore body scroll
            document.body.style.overflow = '';
        });
    });
    
    // 6. Improve search input behavior
    const searchInputs = document.querySelectorAll('#searchInput');
    searchInputs.forEach(input => {
        // Add clear button for mobile
        const clearBtn = document.createElement('button');
        clearBtn.innerHTML = '×';
        clearBtn.className = 'btn btn-sm btn-outline-secondary mobile-clear-btn';
        clearBtn.style.cssText = 'position: absolute; right: 5px; top: 50%; transform: translateY(-50%); z-index: 10; border: none; background: none; font-size: 18px; padding: 0; width: 30px; height: 30px;';
        
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(clearBtn);
        
        clearBtn.addEventListener('click', function() {
            input.value = '';
            input.dispatchEvent(new Event('keyup'));
        });
        
        // Show/hide clear button
        input.addEventListener('input', function() {
            clearBtn.style.display = this.value ? 'block' : 'none';
        });
    });
    
    // 7. Improve pagination for mobile
    const paginationContainers = document.querySelectorAll('.pagination');
    paginationContainers.forEach(pagination => {
        // Add swipe gesture support for pagination
        let startX = 0;
        let endX = 0;
        
        pagination.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        pagination.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const threshold = 50;
            const diff = startX - endX;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    // Swipe left - next page
                    const nextBtn = pagination.querySelector('.page-item:last-child .page-link');
                    if (nextBtn && !nextBtn.closest('.page-item').classList.contains('disabled')) {
                        nextBtn.click();
                    }
                } else {
                    // Swipe right - previous page
                    const prevBtn = pagination.querySelector('.page-item:first-child .page-link');
                    if (prevBtn && !prevBtn.closest('.page-item').classList.contains('disabled')) {
                        prevBtn.click();
                    }
                }
            }
        }
    });
    
    // 8. Add loading states for better UX
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال پردازش...';
            }
        });
    });
    
    // 9. Improve alert positioning on mobile
    const originalShowAlert = window.showAlert;
    if (originalShowAlert) {
        window.showAlert = function(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show mobile-alert" role="alert" style="position: fixed; top: 20px; left: 20px; right: 20px; z-index: 9999; margin: 0;">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.body.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.mobile-alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        };
    }
    
    // 10. Add pull-to-refresh hint
    let startY = 0;
    let currentY = 0;
    let pullDistance = 0;
    const pullThreshold = 100;
    
    document.addEventListener('touchstart', function(e) {
        if (window.scrollY === 0) {
            startY = e.touches[0].clientY;
        }
    });
    
    document.addEventListener('touchmove', function(e) {
        if (window.scrollY === 0 && startY) {
            currentY = e.touches[0].clientY;
            pullDistance = currentY - startY;
            
            if (pullDistance > 0 && pullDistance < pullThreshold) {
                // Show pull hint
                if (!document.querySelector('.pull-hint')) {
                    const hint = document.createElement('div');
                    hint.className = 'pull-hint';
                    hint.innerHTML = '<i class="fas fa-arrow-down"></i> کشیده و رها کنید تا بروزرسانی شود';
                    hint.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; background: #007bff; color: white; text-align: center; padding: 10px; z-index: 9999; transform: translateY(-100%); transition: transform 0.3s;';
                    document.body.appendChild(hint);
                    
                    setTimeout(() => {
                        hint.style.transform = 'translateY(0)';
                    }, 10);
                }
            }
        }
    });
    
    document.addEventListener('touchend', function(e) {
        if (pullDistance > pullThreshold) {
            location.reload();
        }
        
        // Remove pull hint
        const hint = document.querySelector('.pull-hint');
        if (hint) {
            hint.remove();
        }
        
        startY = 0;
        pullDistance = 0;
    });
}

// Utility function to detect mobile device
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Add mobile-specific CSS classes
if (isMobileDevice()) {
    document.documentElement.classList.add('mobile-device');
} else {
    document.documentElement.classList.add('desktop-device');
}