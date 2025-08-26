// Enhanced Table System
class EnhancedTable {
    constructor(tableId, options = {}) {
        this.tableId = tableId;
        this.table = document.getElementById(tableId);
        this.options = {
            sortable: true,
            filterable: true,
            selectable: true,
            searchable: true,
            ...options
        };
        this.selectedRows = new Set();
        this.currentSort = { column: null, direction: 'asc' };
        this.filters = {};
        
        this.init();
    }

    init() {
        if (!this.table) return;
        
        this.setupToolbar();
        if (this.options.sortable) this.setupSorting();
        if (this.options.selectable) this.setupSelection();
        if (this.options.searchable) this.setupSearch();
        if (this.options.filterable) this.setupFilters();
        
        this.updateSummary();
    }

    setupToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'table-toolbar';
        toolbar.innerHTML = `
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="${this.tableId}_search" 
                               placeholder="جستجو در همه فیلدها...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="${this.tableId}_bulk_actions">
                        <option value="">عملیات گروهی</option>
                        <option value="delete">حذف انتخاب شده</option>
                        <option value="export">خروجی انتخاب شده</option>
                        <option value="print">چاپ انتخاب شده</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary" onclick="this.toggleFilters('${this.tableId}')">
                        <i class="fas fa-filter"></i> فیلترها
                    </button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-success" onclick="this.exportVisible('${this.tableId}')">
                        <i class="fas fa-download"></i> خروجی
                    </button>
                </div>
                <div class="col-md-2">
                    <div class="view-options">
                        <button class="btn active" onclick="this.setTableView('${this.tableId}', 'detailed')">
                            <i class="fas fa-th-list"></i>
                        </button>
                        <button class="btn" onclick="this.setTableView('${this.tableId}', 'compact')">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.table.parentNode.insertBefore(toolbar, this.table);
        
        // Add bulk actions bar
        const bulkBar = document.createElement('div');
        bulkBar.className = 'bulk-actions-bar';
        bulkBar.id = `${this.tableId}_bulk_bar`;
        bulkBar.innerHTML = `
            <div class="selected-count">
                <span id="${this.tableId}_selected_count">0</span> مورد انتخاب شده
            </div>
            <div class="bulk-actions">
                <button class="btn btn-sm btn-outline-light" onclick="this.selectAll('${this.tableId}')">
                    انتخاب همه
                </button>
                <button class="btn btn-sm btn-outline-light" onclick="this.clearSelection('${this.tableId}')">
                    لغو انتخاب
                </button>
                <button class="btn btn-sm btn-danger" onclick="this.bulkDelete('${this.tableId}')">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </div>
        `;
        
        toolbar.parentNode.insertBefore(bulkBar, toolbar.nextSibling);
    }

    setupSorting() {
        const headers = this.table.querySelectorAll('thead th');
        headers.forEach((header, index) => {
            if (header.dataset.sortable !== 'false') {
                header.classList.add('sortable');
                header.addEventListener('click', () => this.sortTable(index));
            }
        });
    }

    setupSelection() {
        // Add master checkbox
        const masterCheckbox = document.createElement('input');
        masterCheckbox.type = 'checkbox';
        masterCheckbox.className = 'row-select';
        masterCheckbox.addEventListener('change', (e) => {
            this.toggleAllRows(e.target.checked);
        });

        const firstHeader = this.table.querySelector('thead tr');
        const checkboxHeader = document.createElement('th');
        checkboxHeader.appendChild(masterCheckbox);
        firstHeader.insertBefore(checkboxHeader, firstHeader.firstChild);

        // Add row checkboxes
        const rows = this.table.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'row-select';
            checkbox.value = index;
            checkbox.addEventListener('change', (e) => {
                this.toggleRow(index, e.target.checked);
            });

            const checkboxCell = document.createElement('td');
            checkboxCell.appendChild(checkbox);
            row.insertBefore(checkboxCell, row.firstChild);
        });
    }

    setupSearch() {
        const searchInput = document.getElementById(`${this.tableId}_search`);
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 300);
            });
        }
    }

    setupFilters() {
        const filterPanel = document.createElement('div');
        filterPanel.className = 'filter-panel';
        filterPanel.id = `${this.tableId}_filters`;
        
        // Generate filter controls based on table columns
        const headers = Array.from(this.table.querySelectorAll('thead th'));
        const filterControls = headers.map((header, index) => {
            if (header.dataset.filterable === 'false') return '';
            
            const columnName = header.textContent.trim();
            const columnType = header.dataset.type || 'text';
            
            return `
                <div class="filter-group">
                    <label>${columnName}</label>
                    ${this.generateFilterControl(columnName, columnType, index)}
                </div>
            `;
        }).filter(control => control).join('');

        filterPanel.innerHTML = `
            <div class="filter-row">
                ${filterControls}
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary btn-sm" onclick="this.applyFilters('${this.tableId}')">
                    <i class="fas fa-filter"></i> اعمال فیلتر
                </button>
                <button class="btn btn-secondary btn-sm" onclick="this.clearFilters('${this.tableId}')">
                    <i class="fas fa-times"></i> پاک کردن
                </button>
            </div>
        `;

        this.table.parentNode.insertBefore(filterPanel, this.table);
    }

    generateFilterControl(columnName, columnType, columnIndex) {
        switch (columnType) {
            case 'date':
                return `
                    <input type="date" class="form-control form-control-sm" 
                           data-column="${columnIndex}" data-filter="date">
                `;
            case 'select':
                return `
                    <select class="form-control form-control-sm" 
                            data-column="${columnIndex}" data-filter="select">
                        <option value="">همه</option>
                        <!-- Options will be populated dynamically -->
                    </select>
                `;
            case 'number':
                return `
                    <div class="row">
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm" 
                                   placeholder="از" data-column="${columnIndex}" data-filter="number-min">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm" 
                                   placeholder="تا" data-column="${columnIndex}" data-filter="number-max">
                        </div>
                    </div>
                `;
            default:
                return `
                    <input type="text" class="form-control form-control-sm" 
                           placeholder="جستجو..." data-column="${columnIndex}" data-filter="text">
                `;
        }
    }

    sortTable(columnIndex) {
        const rows = Array.from(this.table.querySelectorAll('tbody tr'));
        const header = this.table.querySelectorAll('thead th')[columnIndex];
        
        // Update sort direction
        if (this.currentSort.column === columnIndex) {
            this.currentSort.direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSort.column = columnIndex;
            this.currentSort.direction = 'asc';
        }

        // Update header classes
        this.table.querySelectorAll('thead th').forEach(h => {
            h.classList.remove('sorted-asc', 'sorted-desc');
        });
        header.classList.add(`sorted-${this.currentSort.direction}`);

        // Sort rows
        rows.sort((a, b) => {
            const aValue = this.getCellValue(a, columnIndex);
            const bValue = this.getCellValue(b, columnIndex);
            
            let comparison = 0;
            if (aValue > bValue) comparison = 1;
            if (aValue < bValue) comparison = -1;
            
            return this.currentSort.direction === 'desc' ? -comparison : comparison;
        });

        // Reorder rows in DOM
        const tbody = this.table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));
        
        this.updateSummary();
    }

    getCellValue(row, columnIndex) {
        const cell = row.cells[columnIndex];
        const value = cell.textContent.trim();
        
        // Try to parse as number
        const numValue = parseFloat(value.replace(/[^\d.-]/g, ''));
        if (!isNaN(numValue)) return numValue;
        
        // Try to parse as date
        const dateValue = new Date(value);
        if (!isNaN(dateValue.getTime())) return dateValue.getTime();
        
        return value.toLowerCase();
    }

    performSearch(query) {
        const rows = this.table.querySelectorAll('tbody tr');
        const searchTerm = query.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(searchTerm);
            row.style.display = isVisible ? '' : 'none';
            
            if (!isVisible) {
                this.selectedRows.delete(row.rowIndex - 1);
                const checkbox = row.querySelector('.row-select');
                if (checkbox) checkbox.checked = false;
            }
        });
        
        this.updateSummary();
        this.updateBulkActionsBar();
    }

    toggleRow(rowIndex, selected) {
        if (selected) {
            this.selectedRows.add(rowIndex);
        } else {
            this.selectedRows.delete(rowIndex);
        }
        
        this.updateBulkActionsBar();
    }

    toggleAllRows(selected) {
        const visibleRows = Array.from(this.table.querySelectorAll('tbody tr'))
            .filter(row => row.style.display !== 'none');
        
        visibleRows.forEach(row => {
            const checkbox = row.querySelector('.row-select');
            if (checkbox) {
                checkbox.checked = selected;
                const rowIndex = row.rowIndex - 1;
                if (selected) {
                    this.selectedRows.add(rowIndex);
                } else {
                    this.selectedRows.delete(rowIndex);
                }
            }
        });
        
        this.updateBulkActionsBar();
    }

    updateBulkActionsBar() {
        const bulkBar = document.getElementById(`${this.tableId}_bulk_bar`);
        const countElement = document.getElementById(`${this.tableId}_selected_count`);
        
        if (countElement) {
            countElement.textContent = this.selectedRows.size;
        }
        
        if (bulkBar) {
            if (this.selectedRows.size > 0) {
                bulkBar.classList.add('show');
            } else {
                bulkBar.classList.remove('show');
            }
        }
    }

    updateSummary() {
        // Update visible row count and other summary information
        const visibleRows = Array.from(this.table.querySelectorAll('tbody tr'))
            .filter(row => row.style.display !== 'none');
        
        // Trigger custom event for summary updates
        const event = new CustomEvent('tableUpdated', {
            detail: {
                tableId: this.tableId,
                visibleRows: visibleRows.length,
                totalRows: this.table.querySelectorAll('tbody tr').length
            }
        });
        
        document.dispatchEvent(event);
    }

    // Global methods for toolbar buttons
    toggleFilters(tableId) {
        const filterPanel = document.getElementById(`${tableId}_filters`);
        if (filterPanel) {
            filterPanel.classList.toggle('show');
        }
    }

    setTableView(tableId, view) {
        const table = document.getElementById(tableId);
        const buttons = table.parentNode.querySelectorAll('.view-options .btn');
        
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        if (view === 'compact') {
            table.classList.add('table-compact');
        } else {
            table.classList.remove('table-compact');
        }
    }

    exportVisible(tableId) {
        const table = document.getElementById(tableId);
        const visibleRows = Array.from(table.querySelectorAll('tbody tr'))
            .filter(row => row.style.display !== 'none');
        
        // Create export data
        const headers = Array.from(table.querySelectorAll('thead th'))
            .map(th => th.textContent.trim());
        
        const data = visibleRows.map(row => 
            Array.from(row.cells).map(cell => cell.textContent.trim())
        );
        
        this.downloadCSV([headers, ...data], `${tableId}_export.csv`);
    }

    downloadCSV(data, filename) {
        const csv = data.map(row => row.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        
        window.URL.revokeObjectURL(url);
    }
}

// Auto-initialize enhanced tables
document.addEventListener('DOMContentLoaded', () => {
    // Initialize tables with class 'enhanced-table'
    document.querySelectorAll('.enhanced-table').forEach(table => {
        new EnhancedTable(table.id);
    });
});