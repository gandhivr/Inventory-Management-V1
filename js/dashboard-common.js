// Common Dashboard JavaScript Functions
// Author: Kiro AI Assistant
// Description: Shared functionality for analytics and reports pages

// Utility Functions
const DashboardUtils = {
    // Show notification toast
    showNotification: function(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed fade-in`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
        
        const iconMap = {
            'success': 'check-circle',
            'error': 'exclamation-triangle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${iconMap[type]} me-2"></i>
                <span class="flex-grow-1">${message}</span>
                <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after specified duration
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    },

    // Loading state for buttons
    setButtonLoading: function(button, isLoading, loadingText = 'Loading...') {
        if (isLoading) {
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<span class="loading-spinner me-2"></span>${loadingText}`;
            button.disabled = true;
        } else {
            button.innerHTML = button.dataset.originalText;
            button.disabled = false;
        }
    },

    // Animate elements on page load
    animateOnLoad: function(selector, delay = 100) {
        const elements = document.querySelectorAll(selector);
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.5s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * delay);
        });
    },

    // Format numbers with commas
    formatNumber: function(num) {
        return new Intl.NumberFormat().format(num);
    },

    // Format currency
    formatCurrency: function(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    // Copy text to clipboard
    copyToClipboard: function(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showNotification('Copied to clipboard!', 'success', 2000);
        }).catch(() => {
            this.showNotification('Failed to copy to clipboard', 'error', 2000);
        });
    },

    // Smooth scroll to element
    scrollToElement: function(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    },

    // Debounce function for search inputs
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Initialize tooltips
    initTooltips: function() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(element => {
            element.classList.add('tooltip-custom');
        });
    },

    // Initialize common dashboard features
    init: function() {
        // Animate cards on load
        this.animateOnLoad('.chart-card, .analytics-table, .activity-feed, .stat-card, .report-card');
        
        // Initialize tooltips
        this.initTooltips();
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.showNotification('Refreshing dashboard...', 'info', 1000);
                setTimeout(() => location.reload(), 1000);
            }
            
            // Escape to close notifications
            if (e.key === 'Escape') {
                const notifications = document.querySelectorAll('.alert.position-fixed');
                notifications.forEach(notification => notification.remove());
            }
        });

        // Add smooth scrolling to anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Show welcome message
        setTimeout(() => {
            this.showNotification('Dashboard loaded successfully!', 'success', 2000);
        }, 500);
    }
};

// Chart utilities
const ChartUtils = {
    // Common chart options
    getDefaultOptions: function() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        };
    },

    // Color schemes
    colorSchemes: {
        primary: ['#667eea', '#764ba2', '#f093fb', '#f5576c'],
        success: ['#28a745', '#20c997', '#17a2b8', '#6f42c1'],
        warm: ['#fd7e14', '#ffc107', '#28a745', '#17a2b8'],
        cool: ['#6f42c1', '#e83e8c', '#fd7e14', '#20c997']
    },

    // Generate gradient
    createGradient: function(ctx, color1, color2) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, color1);
        gradient.addColorStop(1, color2);
        return gradient;
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    DashboardUtils.init();
});

// Export for use in other scripts
window.DashboardUtils = DashboardUtils;
window.ChartUtils = ChartUtils;