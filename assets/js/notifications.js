// Real-time Notification System
class NotificationSystem {
    constructor() {
        this.notifications = [];
        this.container = null;
        this.checkInterval = 30000; // 30 seconds
        this.init();
    }

    init() {
        this.createContainer();
        this.startPeriodicChecks();
        this.setupEventListeners();
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notifications-container';
        this.container.className = 'notifications-container';
        this.container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            pointer-events: none;
        `;
        document.body.appendChild(this.container);
    }

    startPeriodicChecks() {
        // Check immediately
        this.checkLowStock();
        this.checkOverdueSales();
        
        // Then check periodically
        setInterval(() => {
            this.checkLowStock();
            this.checkOverdueSales();
        }, this.checkInterval);
    }

    setupEventListeners() {
        // Listen for custom events
        document.addEventListener('stockUpdated', (e) => {
            if (e.detail.newStock <= e.detail.minStock) {
                this.showNotification({
                    type: 'warning',
                    title: 'هشدار موجودی',
                    message: `موجودی ${e.detail.productName} به ${e.detail.newStock} رسیده است`,
                    duration: 8000
                });
            }
        });

        document.addEventListener('saleCompleted', (e) => {
            this.showNotification({
                type: 'success',
                title: 'فروش موفق',
                message: `فاکتور #${e.detail.saleId} با موفقیت ثبت شد`,
                duration: 5000,
                actions: [{
                    text: 'چاپ فاکتور',
                    action: () => window.open(`print_invoice.php?id=${e.detail.saleId}`, '_blank')
                }]
            });
        });
    }

    async checkLowStock() {
        // فقط در صفحه داشبورد نمایش داده شود
        const currentPage = document.body.dataset.page;
        if (currentPage !== 'dashboard') {
            return;
        }
        
        try {
            const response = await fetch('api/check_low_stock.php');
            const data = await response.json();
            
            if (data.success && data.count > 0) {
                // Only show if we haven't shown this notification recently
                const notificationId = `low_stock_${data.count}`;
                if (!this.hasRecentNotification(notificationId)) {
                    this.showNotification({
                        id: notificationId,
                        type: 'warning',
                        title: 'هشدار موجودی کم',
                        message: `${data.count} محصول موجودی کم دارند`,
                        duration: 0, // Persistent
                        actions: [{
                            text: 'مشاهده',
                            action: () => window.location.href = 'products.php?filter=low_stock'
                        }]
                    });
                }
            }
        } catch (error) {
            console.error('خطا در بررسی موجودی:', error);
        }
    }

    async checkOverdueSales() {
        try {
            const response = await fetch('api/check_overdue_sales.php');
            const data = await response.json();
            
            if (data.success && data.count > 0) {
                const notificationId = `overdue_sales_${data.count}`;
                if (!this.hasRecentNotification(notificationId)) {
                    this.showNotification({
                        id: notificationId,
                        type: 'danger',
                        title: 'فاکتورهای معوق',
                        message: `${data.count} فاکتور معوق وجود دارد`,
                        duration: 0,
                        actions: [{
                            text: 'مشاهده',
                            action: () => window.location.href = 'sales.php?filter=overdue'
                        }]
                    });
                }
            }
        } catch (error) {
            console.error('خطا در بررسی فاکتورهای معوق:', error);
        }
    }

    hasRecentNotification(id) {
        const recent = this.notifications.find(n => 
            n.id === id && 
            (Date.now() - n.timestamp) < 300000 // 5 minutes
        );
        return !!recent;
    }

    showNotification({id, type = 'info', title, message, duration = 5000, actions = []}) {
        const notification = document.createElement('div');
        const notificationId = id || `notification_${Date.now()}`;
        
        notification.className = `notification notification-${type} animate__animated animate__slideInRight`;
        notification.style.cssText = `
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 15px;
            padding: 20px;
            border-left: 4px solid ${this.getTypeColor(type)};
            pointer-events: auto;
            position: relative;
            overflow: hidden;
        `;

        const actionsHtml = actions.map(action => 
            `<button class="btn btn-sm btn-outline-primary me-2" onclick="(${action.action.toString()})(); this.closest('.notification').remove();">
                ${action.text}
            </button>`
        ).join('');

        notification.innerHTML = `
            <div class="notification-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div class="notification-icon" style="color: ${this.getTypeColor(type)}; font-size: 1.2rem; margin-left: 10px;">
                    <i class="fas fa-${this.getTypeIcon(type)}"></i>
                </div>
                <div style="flex: 1;">
                    <h6 style="margin: 0; color: #1f2937; font-weight: 600;">${title}</h6>
                </div>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: #6b7280; font-size: 1.2rem; cursor: pointer; padding: 0;">
                    ×
                </button>
            </div>
            <div class="notification-content">
                <p style="margin: 0 0 15px 0; color: #6b7280; line-height: 1.5;">${message}</p>
                ${actions.length > 0 ? `<div class="notification-actions">${actionsHtml}</div>` : ''}
            </div>
            ${duration > 0 ? `<div class="notification-progress" style="position: absolute; bottom: 0; left: 0; height: 3px; background: ${this.getTypeColor(type)}; width: 100%; animation: shrink ${duration}ms linear;"></div>` : ''}
        `;

        this.container.appendChild(notification);
        
        // Store notification info
        this.notifications.push({
            id: notificationId,
            timestamp: Date.now(),
            element: notification
        });

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.add('animate__slideOutRight');
                    setTimeout(() => notification.remove(), 300);
                }
            }, duration);
        }

        // Add progress bar animation
        if (duration > 0) {
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shrink {
                    from { width: 100%; }
                    to { width: 0%; }
                }
            `;
            document.head.appendChild(style);
        }

        // Play sound if enabled
        this.playNotificationSound(type);
    }

    getTypeColor(type) {
        const colors = {
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            error: '#ef4444',
            info: '#06b6d4'
        };
        return colors[type] || colors.info;
    }

    getTypeIcon(type) {
        const icons = {
            success: 'check-circle',
            warning: 'exclamation-triangle',
            danger: 'exclamation-circle',
            error: 'times-circle',
            info: 'info-circle'
        };
        return icons[type] || icons.info;
    }

    playNotificationSound(type) {
        const soundEnabled = localStorage.getItem('soundNotifications') === 'true';
        if (!soundEnabled) return;

        // Create audio context for different notification sounds
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        // Different frequencies for different types
        const frequencies = {
            success: 800,
            warning: 600,
            danger: 400,
            error: 400,
            info: 700
        };

        oscillator.frequency.setValueAtTime(frequencies[type] || 700, audioContext.currentTime);
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    }

    // Public methods for manual notifications
    success(title, message, options = {}) {
        this.showNotification({
            type: 'success',
            title,
            message,
            ...options
        });
    }

    warning(title, message, options = {}) {
        this.showNotification({
            type: 'warning',
            title,
            message,
            ...options
        });
    }

    error(title, message, options = {}) {
        this.showNotification({
            type: 'error',
            title,
            message,
            ...options
        });
    }

    info(title, message, options = {}) {
        this.showNotification({
            type: 'info',
            title,
            message,
            ...options
        });
    }

    clearAll() {
        this.container.innerHTML = '';
        this.notifications = [];
    }
}

// Initialize notification system
let notificationSystem;
document.addEventListener('DOMContentLoaded', () => {
    notificationSystem = new NotificationSystem();
    
    // Make it globally available
    window.notify = notificationSystem;
});