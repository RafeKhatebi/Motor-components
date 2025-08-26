// Quick Sale System
class QuickSale {
    constructor() {
        this.cart = [];
        this.init();
    }

    init() {
        this.initKeyboardShortcuts();
        this.initProductSearch();
        this.initQuickActions();
    }

    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // F2: Focus on product search
            if (e.key === 'F2') {
                e.preventDefault();
                const searchInput = document.getElementById('quickProductSearch');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }

            // F9: Quick checkout
            if (e.key === 'F9') {
                e.preventDefault();
                this.quickCheckout();
            }

            // Ctrl+N: New sale
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.openNewSaleModal();
            }

            // Ctrl+P: Add product
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                this.quickAddProduct();
            }

            // Ctrl+I: Check inventory
            if (e.ctrlKey && e.key === 'i') {
                e.preventDefault();
                this.checkInventory();
            }

            // Ctrl+R: Today's report
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.todayReport();
            }

            // Escape: Close modals
            if (e.key === 'Escape') {
                this.closeAllModals();
            }

            // Enter in quantity field: Add to cart
            if (e.key === 'Enter' && e.target.id === 'quickQuantity') {
                e.preventDefault();
                this.addToCart();
            }
        });
    }

    initProductSearch() {
        const searchInput = document.getElementById('quickProductSearch');
        const suggestionsDiv = document.getElementById('productSuggestions');
        
        if (!searchInput || !suggestionsDiv) return;

        let searchTimeout;
        
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                suggestionsDiv.classList.remove('show');
                return;
            }

            searchTimeout = setTimeout(() => {
                this.searchProducts(query);
            }, 300);
        });

        // Handle arrow keys and enter in suggestions
        searchInput.addEventListener('keydown', (e) => {
            const suggestions = suggestionsDiv.querySelectorAll('.suggestion-item');
            const active = suggestionsDiv.querySelector('.suggestion-item.active');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (active) {
                    active.classList.remove('active');
                    const next = active.nextElementSibling;
                    if (next) next.classList.add('active');
                    else suggestions[0]?.classList.add('active');
                } else {
                    suggestions[0]?.classList.add('active');
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (active) {
                    active.classList.remove('active');
                    const prev = active.previousElementSibling;
                    if (prev) prev.classList.add('active');
                    else suggestions[suggestions.length - 1]?.classList.add('active');
                }
            } else if (e.key === 'Enter' && active) {
                e.preventDefault();
                this.selectProduct(active);
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.remove('show');
            }
        });
    }

    async searchProducts(query) {
        try {
            const response = await fetch(`api/search_products.php?q=${encodeURIComponent(query)}`);
            const products = await response.json();
            
            this.displaySuggestions(products);
        } catch (error) {
            console.error('خطا در جستجو:', error);
        }
    }

    displaySuggestions(products) {
        const suggestionsDiv = document.getElementById('productSuggestions');
        
        if (products.length === 0) {
            suggestionsDiv.innerHTML = '<div class="suggestion-item">محصولی یافت نشد</div>';
        } else {
            suggestionsDiv.innerHTML = products.map(product => `
                <div class="suggestion-item" 
                     data-id="${product.id}" 
                     data-name="${product.name}"
                     data-price="${product.sell_price}"
                     data-stock="${product.stock_quantity}">
                    <div class="product-name">${product.name}</div>
                    <div class="product-info">
                        <span class="price">${parseInt(product.sell_price).toLocaleString()} افغانی</span>
                        <span class="stock ${product.stock_quantity < 10 ? 'low-stock' : ''}">
                            موجودی: ${product.stock_quantity}
                        </span>
                    </div>
                </div>
            `).join('');

            // Add click handlers
            suggestionsDiv.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', () => this.selectProduct(item));
            });
        }
        
        suggestionsDiv.classList.add('show');
    }

    selectProduct(item) {
        const productId = item.dataset.id;
        const productName = item.dataset.name;
        const productPrice = item.dataset.price;
        const productStock = item.dataset.stock;

        if (!productId) return;

        // Update UI
        document.getElementById('quickProductSearch').value = productName;
        document.getElementById('quickPrice').textContent = `${parseInt(productPrice).toLocaleString()} افغانی`;
        document.getElementById('productSuggestions').classList.remove('show');

        // Focus on quantity
        const quantityInput = document.getElementById('quickQuantity');
        if (quantityInput) {
            quantityInput.focus();
            quantityInput.select();
        }

        // Store selected product data
        this.selectedProduct = {
            id: productId,
            name: productName,
            price: parseFloat(productPrice),
            stock: parseInt(productStock)
        };
    }

    addToCart() {
        if (!this.selectedProduct) {
            this.showNotification('ابتدا محصولی را انتخاب کنید', 'warning');
            return;
        }

        const quantity = parseInt(document.getElementById('quickQuantity').value) || 1;
        
        if (quantity > this.selectedProduct.stock) {
            this.showNotification(`موجودی کافی نیست. موجودی فعلی: ${this.selectedProduct.stock}`, 'error');
            return;
        }

        // Add to cart
        const existingItem = this.cart.find(item => item.id === this.selectedProduct.id);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.cart.push({
                ...this.selectedProduct,
                quantity: quantity
            });
        }

        this.updateCartDisplay();
        this.clearQuickSale();
        this.showNotification(`${this.selectedProduct.name} به سبد اضافه شد`, 'success');
    }

    updateCartDisplay() {
        const cartBtn = document.getElementById('quickCheckout');
        if (cartBtn) {
            const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
            const totalAmount = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            cartBtn.innerHTML = `
                <i class="fas fa-shopping-cart"></i> 
                تسویه (${totalItems}) - ${totalAmount.toLocaleString()} افغانی
                <span class="shortcut-hint">F9</span>
            `;
        }
    }

    clearQuickSale() {
        document.getElementById('quickProductSearch').value = '';
        document.getElementById('quickQuantity').value = '1';
        document.getElementById('quickPrice').textContent = '0 افغانی';
        document.getElementById('productSuggestions').classList.remove('show');
        this.selectedProduct = null;
    }

    quickCheckout() {
        if (this.cart.length === 0) {
            this.showNotification('سبد خرید خالی است', 'warning');
            return;
        }

        // Open checkout modal or redirect
        this.openCheckoutModal();
    }

    openCheckoutModal() {
        // Create and show checkout modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'quickCheckoutModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تسویه سریع</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${this.generateCheckoutContent()}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="button" class="btn btn-success" onclick="quickSale.processCheckout()">
                            <i class="fas fa-check"></i> تکمیل فروش
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Remove modal after hide
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }

    generateCheckoutContent() {
        const totalAmount = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        return `
            <div class="checkout-items">
                <h6>اقلام سبد خرید:</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>محصول</th>
                                <th>تعداد</th>
                                <th>قیمت واحد</th>
                                <th>جمع</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${this.cart.map((item, index) => `
                                <tr>
                                    <td>${item.name}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" 
                                               value="${item.quantity}" min="1" max="${item.stock}"
                                               onchange="quickSale.updateCartItem(${index}, this.value)">
                                    </td>
                                    <td>${item.price.toLocaleString()} افغانی</td>
                                    <td>${(item.price * item.quantity).toLocaleString()} افغانی</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="quickSale.removeCartItem(${index})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="3">جمع کل:</th>
                                <th>${totalAmount.toLocaleString()} افغانی</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div class="checkout-options mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <label>مشتری:</label>
                        <select class="form-control" id="quickCustomer">
                            <option value="">مشتری نقدی</option>
                            <!-- Customer options will be loaded here -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>نوع پرداخت:</label>
                        <select class="form-control" id="quickPaymentType">
                            <option value="cash">نقدی</option>
                            <option value="credit">قرضی</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    }

    updateCartItem(index, newQuantity) {
        const quantity = parseInt(newQuantity);
        if (quantity > 0 && quantity <= this.cart[index].stock) {
            this.cart[index].quantity = quantity;
            this.updateCheckoutDisplay();
        }
    }

    removeCartItem(index) {
        this.cart.splice(index, 1);
        this.updateCheckoutDisplay();
        
        if (this.cart.length === 0) {
            bootstrap.Modal.getInstance(document.getElementById('quickCheckoutModal')).hide();
        }
    }

    updateCheckoutDisplay() {
        const modal = document.getElementById('quickCheckoutModal');
        if (modal) {
            const modalBody = modal.querySelector('.modal-body');
            modalBody.innerHTML = this.generateCheckoutContent();
        }
        this.updateCartDisplay();
    }

    async processCheckout() {
        if (this.cart.length === 0) return;

        try {
            const formData = new FormData();
            formData.append('customer_id', document.getElementById('quickCustomer').value);
            formData.append('payment_type', document.getElementById('quickPaymentType').value);
            formData.append('discount', '0');
            
            this.cart.forEach((item, index) => {
                formData.append('products[]', item.id);
                formData.append('quantities[]', item.quantity);
                formData.append('prices[]', item.price);
            });

            const response = await fetch('api/add_sale.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('فروش با موفقیت ثبت شد', 'success');
                bootstrap.Modal.getInstance(document.getElementById('quickCheckoutModal')).hide();
                this.cart = [];
                this.updateCartDisplay();
                
                // Optionally print invoice
                if (confirm('آیا می‌خواهید فاکتور را چاپ کنید؟')) {
                    window.open(`print_invoice.php?id=${result.sale_id}`, '_blank');
                }
                
                // Refresh page after delay
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showNotification(result.message || 'خطا در ثبت فروش', 'error');
            }
        } catch (error) {
            this.showNotification('خطا در ارتباط با سرور', 'error');
        }
    }

    initQuickActions() {
        // Initialize quick action buttons
        const quickActions = {
            quickSale: () => document.getElementById('quickProductSearch')?.focus(),
            addProduct: () => window.location.href = 'products.php#add',
            checkInventory: () => window.location.href = 'products.php?filter=low_stock',
            todayReport: () => window.location.href = 'reports.php#today'
        };

        Object.keys(quickActions).forEach(action => {
            const btn = document.querySelector(`[onclick="${action}()"]`);
            if (btn) {
                btn.onclick = quickActions[action];
            }
        });
    }

    openNewSaleModal() {
        const modal = document.getElementById('newSaleModal');
        if (modal) {
            new bootstrap.Modal(modal).show();
        }
    }

    closeAllModals() {
        document.querySelectorAll('.modal.show').forEach(modal => {
            bootstrap.Modal.getInstance(modal)?.hide();
        });
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize Quick Sale System
let quickSale;
document.addEventListener('DOMContentLoaded', () => {
    quickSale = new QuickSale();
});