// Dark Mode Theme Toggle
class DarkModeManager {
    constructor() {
        this.storageKey = 'theme_preference';
        this.darkModeAttribute = 'data-theme';
        this.init();
    }

    init() {
        // Load theme preference from localStorage
        const savedTheme = localStorage.getItem(this.storageKey);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Set theme: saved preference > system preference > light
        const theme = savedTheme || (prefersDark ? 'dark' : 'light');
        this.setTheme(theme);
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(this.storageKey)) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    setTheme(theme) {
        // Apply to both html and body elements for compatibility
        if (theme === 'dark') {
            document.documentElement.setAttribute(this.darkModeAttribute, 'dark');
            document.body.setAttribute(this.darkModeAttribute, 'dark');
            document.documentElement.classList.add('dark-mode');
            document.body.classList.add('dark-mode');
        } else {
            document.documentElement.removeAttribute(this.darkModeAttribute);
            document.body.removeAttribute(this.darkModeAttribute);
            document.documentElement.classList.remove('dark-mode');
            document.body.classList.remove('dark-mode');
        }
        
        localStorage.setItem(this.storageKey, theme);
        this.saveThemePreference(theme);
        
        // Dispatch custom event for other scripts to listen
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    }

    getTheme() {
        return localStorage.getItem(this.storageKey) || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    }

    toggleTheme() {
        const currentTheme = this.getTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
        return newTheme;
    }

    saveThemePreference(theme) {
        // Send preference to server to save in database (optional, non-blocking)
        if (typeof fetch !== 'undefined') {
            fetch('/api/settings/update_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme_mode: theme })
            }).catch(err => console.log('Theme save (optional):', err.message));
        }
    }

    // For use in settings page
    setDarkModeEnabled(enabled) {
        this.setTheme(enabled ? 'dark' : 'light');
    }

    isDarkModeEnabled() {
        return this.getTheme() === 'dark';
    }
}

// Initialize dark mode manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.darkModeManager = new DarkModeManager();
        // Update toggle buttons after manager is initialized
        updateThemeToggleButton();
    });
} else {
    window.darkModeManager = new DarkModeManager();
    // Update toggle buttons after manager is initialized
    updateThemeToggleButton();
}

// Theme toggle button handler
document.addEventListener('DOMContentLoaded', function() {
    const themeToggleButtons = document.querySelectorAll('.theme-toggle-btn, .theme-toggle');
    
    themeToggleButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const newTheme = window.darkModeManager.toggleTheme();
            
            // Update button text/icon
            updateThemeToggleButton();
        });
    });
});

// Update theme toggle button appearance
function updateThemeToggleButton() {
    if (!window.darkModeManager) {
        return; // Skip if manager not initialized yet
    }
    
    const isDark = window.darkModeManager.isDarkModeEnabled();
    const buttons = document.querySelectorAll('.theme-toggle-btn, .theme-toggle');
    
    buttons.forEach(btn => {
        if (isDark) {
            btn.innerHTML = '<i class="lucide-icon" data-lucide="sun" style="width: 16px; height: 16px;"></i><span class="d-none d-sm-inline">Light</span>';
            btn.title = 'Switch to Light Mode';
            btn.setAttribute('aria-label', 'Switch to Light Mode');
        } else {
            btn.innerHTML = '<i class="lucide-icon" data-lucide="moon" style="width: 16px; height: 16px;"></i><span class="d-none d-sm-inline">Dark</span>';
            btn.title = 'Switch to Dark Mode';
            btn.setAttribute('aria-label', 'Switch to Dark Mode');
        }
    });
    
    // Reinitialize lucide icons if available
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Initialize theme toggle button on page load
document.addEventListener('DOMContentLoaded', updateThemeToggleButton);

// Listen for theme changes
window.addEventListener('themechange', (e) => {
    updateThemeToggleButton();
});
