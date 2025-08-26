// Smart Form Validation System
class SmartFormValidator {
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);
        this.options = {
            validateOnBlur: true,
            validateOnInput: false,
            showSuccessMessages: true,
            ...options
        };
        this.rules = {};
        this.customValidators = {};
        
        if (this.form) {
            this.init();
        }
    }

    init() {
        this.setupEventListeners();
        this.loadRulesFromAttributes();
    }

    setupEventListeners() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (this.options.validateOnBlur) {
                input.addEventListener('blur', () => this.validateField(input));
            }
            
            if (this.options.validateOnInput) {
                input.addEventListener('input', () => this.clearError(input));
            }
            
            // Real-time validation for specific field types
            if (input.type === 'email' || input.type === 'tel') {
                input.addEventListener('input', () => {
                    clearTimeout(input.validationTimeout);
                    input.validationTimeout = setTimeout(() => {
                        this.validateField(input);
                    }, 500);
                });
            }
        });

        // Form submission validation
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.focusFirstError();
            }
        });
    }

    loadRulesFromAttributes() {
        const inputs = this.form.querySelectorAll('[data-rules]');
        inputs.forEach(input => {
            const rules = input.dataset.rules.split('|');
            this.rules[input.name || input.id] = rules;
        });
    }

    addRule(fieldName, rules) {
        this.rules[fieldName] = Array.isArray(rules) ? rules : [rules];
    }

    addCustomValidator(name, validator) {
        this.customValidators[name] = validator;
    }

    validateField(field) {
        const fieldName = field.name || field.id;
        const rules = this.rules[fieldName] || [];
        const value = field.value.trim();

        // Clear previous validation state
        this.clearError(field);

        for (let rule of rules) {
            const result = this.applyRule(field, rule, value);
            if (!result.valid) {
                this.showError(field, result.message);
                return false;
            }
        }

        if (this.options.showSuccessMessages && value) {
            this.showSuccess(field);
        }
        
        return true;
    }

    applyRule(field, rule, value) {
        // Parse rule (e.g., "min:5" -> {name: "min", param: "5"})
        const [ruleName, param] = rule.split(':');
        
        switch (ruleName) {
            case 'required':
                return {
                    valid: value.length > 0,
                    message: 'این فیلد اجباری است'
                };
                
            case 'min':
                const minLength = parseInt(param);
                return {
                    valid: value.length >= minLength,
                    message: `حداقل ${minLength} کاراکتر وارد کنید`
                };
                
            case 'max':
                const maxLength = parseInt(param);
                return {
                    valid: value.length <= maxLength,
                    message: `حداکثر ${maxLength} کاراکتر مجاز است`
                };
                
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return {
                    valid: !value || emailRegex.test(value),
                    message: 'آدرس ایمیل معتبر نیست'
                };
                
            case 'phone':
                return {
                    valid: !value || this.isValidPhone(value),
                    message: 'شماره تلفن معتبر نیست'
                };
                
            case 'numeric':
                return {
                    valid: !value || /^\d+$/.test(value),
                    message: 'فقط عدد وارد کنید'
                };
                
            case 'decimal':
                return {
                    valid: !value || /^\d+(\.\d+)?$/.test(value),
                    message: 'عدد معتبر وارد کنید'
                };
                
            case 'persian':
                const persianRegex = /^[\u0600-\u06FF\s]+$/;
                return {
                    valid: !value || persianRegex.test(value),
                    message: 'فقط حروف فارسی مجاز است'
                };
                
            case 'english':
                const englishRegex = /^[a-zA-Z\s]+$/;
                return {
                    valid: !value || englishRegex.test(value),
                    message: 'فقط حروف انگلیسی مجاز است'
                };
                
            default:
                // Check custom validators
                if (this.customValidators[ruleName]) {
                    return this.customValidators[ruleName](value, param, field);
                }
                return { valid: true };
        }
    }

    isValidPhone(phone) {
        // Iranian phone number patterns
        const patterns = [
            /^09\d{9}$/, // Mobile: 09xxxxxxxxx
            /^0\d{10}$/, // Landline: 0xxxxxxxxxx
            /^\+989\d{9}$/ // International mobile: +989xxxxxxxxx
        ];
        
        return patterns.some(pattern => pattern.test(phone.replace(/\s|-/g, '')));
    }

    showError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        
        let feedback = field.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentElement.appendChild(feedback);
        }
        feedback.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }

    showSuccess(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        const invalidFeedback = field.parentElement.querySelector('.invalid-feedback');
        if (invalidFeedback) invalidFeedback.remove();
        
        let feedback = field.parentElement.querySelector('.valid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'valid-feedback';
            field.parentElement.appendChild(feedback);
        }
        feedback.innerHTML = '<i class="fas fa-check-circle"></i> معتبر است';
    }

    clearError(field) {
        field.classList.remove('is-invalid', 'is-valid');
        
        const feedback = field.parentElement.querySelector('.invalid-feedback, .valid-feedback');
        if (feedback) feedback.remove();
    }

    validateForm() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    focusFirstError() {
        const firstError = this.form.querySelector('.is-invalid');
        if (firstError) {
            firstError.focus();
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    reset() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            this.clearError(input);
        });
    }
}

// Auto-complete System
class AutoComplete {
    constructor(inputId, options = {}) {
        this.input = document.getElementById(inputId);
        this.options = {
            minLength: 2,
            maxResults: 10,
            source: [],
            onSelect: null,
            ...options
        };
        this.suggestions = [];
        this.selectedIndex = -1;
        
        if (this.input) {
            this.init();
        }
    }

    init() {
        this.createSuggestionsContainer();
        this.setupEventListeners();
    }

    createSuggestionsContainer() {
        const container = document.createElement('div');
        container.className = 'autocomplete-container';
        
        this.input.parentNode.insertBefore(container, this.input);
        container.appendChild(this.input);
        
        this.suggestionsEl = document.createElement('div');
        this.suggestionsEl.className = 'autocomplete-suggestions';
        container.appendChild(this.suggestionsEl);
    }

    setupEventListeners() {
        this.input.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });

        this.input.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });

        this.input.addEventListener('blur', () => {
            setTimeout(() => this.hideSuggestions(), 150);
        });

        document.addEventListener('click', (e) => {
            if (!this.input.parentNode.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }

    async handleInput(value) {
        if (value.length < this.options.minLength) {
            this.hideSuggestions();
            return;
        }

        try {
            let suggestions;
            
            if (typeof this.options.source === 'function') {
                suggestions = await this.options.source(value);
            } else if (typeof this.options.source === 'string') {
                // API endpoint
                const response = await fetch(`${this.options.source}?q=${encodeURIComponent(value)}`);
                suggestions = await response.json();
            } else {
                // Array of items
                suggestions = this.options.source.filter(item => 
                    item.toLowerCase().includes(value.toLowerCase())
                ).slice(0, this.options.maxResults);
            }

            this.showSuggestions(suggestions);
        } catch (error) {
            console.error('AutoComplete error:', error);
        }
    }

    showSuggestions(suggestions) {
        this.suggestions = suggestions;
        this.selectedIndex = -1;

        if (suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }

        this.suggestionsEl.innerHTML = suggestions.map((item, index) => {
            const text = typeof item === 'string' ? item : item.text || item.name;
            return `<div class="autocomplete-item" data-index="${index}">${text}</div>`;
        }).join('');

        // Add click handlers
        this.suggestionsEl.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectItem(parseInt(item.dataset.index));
            });
        });

        this.suggestionsEl.classList.add('show');
    }

    hideSuggestions() {
        this.suggestionsEl.classList.remove('show');
        this.selectedIndex = -1;
    }

    handleKeydown(e) {
        const items = this.suggestionsEl.querySelectorAll('.autocomplete-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.updateSelection();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection();
                break;
                
            case 'Enter':
                if (this.selectedIndex >= 0) {
                    e.preventDefault();
                    this.selectItem(this.selectedIndex);
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }

    updateSelection() {
        const items = this.suggestionsEl.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            item.classList.toggle('active', index === this.selectedIndex);
        });
    }

    selectItem(index) {
        const item = this.suggestions[index];
        const text = typeof item === 'string' ? item : item.text || item.name;
        
        this.input.value = text;
        this.hideSuggestions();
        
        if (this.options.onSelect) {
            this.options.onSelect(item, index);
        }
        
        // Trigger change event
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

// Settings Panel System
class SettingsPanel {
    constructor() {
        this.settings = this.loadSettings();
        this.init();
    }

    init() {
        this.createPanel();
        this.createToggleButton();
        this.createShortcutsHelp();
        this.applySettings();
        this.setupEventListeners();
    }

    createPanel() {
        const panel = document.createElement('div');
        panel.className = 'settings-panel';
        panel.id = 'settingsPanel';
        panel.innerHTML = `
            <div class="settings-header">
                <h5>تنظیمات سریع</h5>
                <button class="btn-close" onclick="settingsPanel.toggle()">×</button>
            </div>
            <div class="settings-body">
                <div class="setting-group">
                    <label>تم رنگی</label>
                    <div class="theme-options">
                        <button class="theme-btn" data-theme="light">روشن</button>
                        <button class="theme-btn" data-theme="dark">تیره</button>
                        <button class="theme-btn" data-theme="auto">خودکار</button>
                    </div>
                </div>
                
                <div class="setting-group">
                    <label>اندازه فونت</label>
                    <input type="range" class="form-range" id="fontSizeRange" 
                           min="12" max="18" value="${this.settings.fontSize}" 
                           onchange="settingsPanel.changeFontSize(this.value)">
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i>
                        <span id="fontSizeValue">${this.settings.fontSize}px</span>
                    </div>
                </div>
                
                <div class="setting-group">
                    <label>
                        <input type="checkbox" class="form-check-input" id="showShortcuts" 
                               ${this.settings.showShortcuts ? 'checked' : ''}
                               onchange="settingsPanel.toggleShortcuts(this.checked)">
                        نمایش کلیدهای میانبر
                    </label>
                </div>
                
                <div class="setting-group">
                    <label>
                        <input type="checkbox" class="form-check-input" id="soundNotifications"
                               ${this.settings.soundNotifications ? 'checked' : ''}
                               onchange="settingsPanel.toggleSound(this.checked)">
                        صدای اعلانات
                    </label>
                </div>
                
                <div class="setting-group">
                    <label>
                        <input type="checkbox" class="form-check-input" id="autoSave"
                               ${this.settings.autoSave ? 'checked' : ''}
                               onchange="settingsPanel.toggleAutoSave(this.checked)">
                        ذخیره خودکار فرمها
                    </label>
                </div>
            </div>
        `;
        
        document.body.appendChild(panel);
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'settings-overlay';
        overlay.onclick = () => this.hide();
        document.body.appendChild(overlay);
    }

    createToggleButton() {
        const button = document.createElement('button');
        button.className = 'settings-toggle';
        button.innerHTML = '<i class="fas fa-cog"></i>';
        button.onclick = () => this.toggle();
        button.title = 'تنظیمات (Alt+S)';
        document.body.appendChild(button);
    }

    createShortcutsHelp() {
        const help = document.createElement('div');
        help.className = 'shortcuts-help';
        help.id = 'shortcutsHelp';
        help.innerHTML = `
            <h6>کلیدهای میانبر</h6>
            <div class="shortcut-item">
                <span>فروش سریع</span>
                <span class="shortcut-key">F2</span>
            </div>
            <div class="shortcut-item">
                <span>تسویه</span>
                <span class="shortcut-key">F9</span>
            </div>
            <div class="shortcut-item">
                <span>فاکتور جدید</span>
                <span class="shortcut-key">Ctrl+N</span>
            </div>
            <div class="shortcut-item">
                <span>محصول جدید</span>
                <span class="shortcut-key">Ctrl+P</span>
            </div>
            <div class="shortcut-item">
                <span>موجودی</span>
                <span class="shortcut-key">Ctrl+I</span>
            </div>
            <div class="shortcut-item">
                <span>گزارش</span>
                <span class="shortcut-key">Ctrl+R</span>
            </div>
            <div class="shortcut-item">
                <span>تنظیمات</span>
                <span class="shortcut-key">Alt+S</span>
            </div>
        `;
        document.body.appendChild(help);
    }

    setupEventListeners() {
        // Theme buttons
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.setTheme(btn.dataset.theme);
            });
        });

        // Keyboard shortcut for settings
        document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                this.toggle();
            }
        });

        // Update active theme button
        this.updateThemeButtons();
    }

    toggle() {
        const panel = document.getElementById('settingsPanel');
        const overlay = document.querySelector('.settings-overlay');
        
        if (panel.classList.contains('show')) {
            this.hide();
        } else {
            this.show();
        }
    }

    show() {
        const panel = document.getElementById('settingsPanel');
        const overlay = document.querySelector('.settings-overlay');
        
        panel.classList.add('show');
        overlay.classList.add('show');
    }

    hide() {
        const panel = document.getElementById('settingsPanel');
        const overlay = document.querySelector('.settings-overlay');
        
        panel.classList.remove('show');
        overlay.classList.remove('show');
    }

    setTheme(theme) {
        this.settings.theme = theme;
        this.applyTheme();
        this.updateThemeButtons();
        this.saveSettings();
    }

    applyTheme() {
        const body = document.body;
        
        switch (this.settings.theme) {
            case 'dark':
                body.classList.add('dark-theme');
                break;
            case 'light':
                body.classList.remove('dark-theme');
                break;
            case 'auto':
                const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                body.classList.toggle('dark-theme', isDark);
                break;
        }
    }

    updateThemeButtons() {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.theme === this.settings.theme);
        });
    }

    changeFontSize(size) {
        this.settings.fontSize = parseInt(size);
        document.documentElement.style.fontSize = `${size}px`;
        document.getElementById('fontSizeValue').textContent = `${size}px`;
        this.saveSettings();
    }

    toggleShortcuts(show) {
        this.settings.showShortcuts = show;
        const help = document.getElementById('shortcutsHelp');
        help.classList.toggle('show', show);
        this.saveSettings();
    }

    toggleSound(enabled) {
        this.settings.soundNotifications = enabled;
        localStorage.setItem('soundNotifications', enabled);
        this.saveSettings();
    }

    toggleAutoSave(enabled) {
        this.settings.autoSave = enabled;
        this.saveSettings();
        
        if (enabled) {
            this.setupAutoSave();
        }
    }

    setupAutoSave() {
        // Auto-save form data
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    this.saveFormData(form.id, input.name || input.id, input.value);
                });
            });
        });
    }

    saveFormData(formId, fieldName, value) {
        const formData = JSON.parse(localStorage.getItem(`form_${formId}`) || '{}');
        formData[fieldName] = value;
        localStorage.setItem(`form_${formId}`, JSON.stringify(formData));
    }

    loadFormData(formId) {
        return JSON.parse(localStorage.getItem(`form_${formId}`) || '{}');
    }

    applySettings() {
        this.applyTheme();
        this.changeFontSize(this.settings.fontSize);
        this.toggleShortcuts(this.settings.showShortcuts);
        this.toggleSound(this.settings.soundNotifications);
        
        if (this.settings.autoSave) {
            this.setupAutoSave();
        }
    }

    loadSettings() {
        const defaults = {
            theme: 'light',
            fontSize: 14,
            showShortcuts: true,
            soundNotifications: false,
            autoSave: true
        };
        
        const saved = localStorage.getItem('userSettings');
        return saved ? { ...defaults, ...JSON.parse(saved) } : defaults;
    }

    saveSettings() {
        localStorage.setItem('userSettings', JSON.stringify(this.settings));
    }
}

// Initialize systems
let settingsPanel;
document.addEventListener('DOMContentLoaded', () => {
    settingsPanel = new SettingsPanel();
    
    // Auto-initialize forms with validation
    document.querySelectorAll('form[data-validate]').forEach(form => {
        new SmartFormValidator(form.id);
    });
});