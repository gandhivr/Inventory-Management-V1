// Enhanced Cart functionality with animations
function addToCart(productId, maxQuantity) {
    const quantity = document.getElementById(`quantity_${productId}`).value;
    
    if (quantity > maxQuantity) {
        showNotification(`Maximum available quantity is ${maxQuantity}`, 'warning');
        return false;
    }
    
    if (quantity <= 0) {
        showNotification('Please enter a valid quantity', 'error');
        return false;
    }
    
    // Add loading state to button
    const button = event.target;
    const originalText = button.textContent;
    button.innerHTML = '<span class="loading"></span> Adding...';
    button.disabled = true;
    
    // Simulate API call delay for better UX
    setTimeout(() => {
        button.textContent = originalText;
        button.disabled = false;
        showNotification('Item added to cart successfully!', 'success');
    }, 1000);
    
    return true;
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    for (let input of inputs) {
        if (!input.value.trim()) {
            alert(`Please fill in the ${input.name} field`);
            input.focus();
            return false;
        }
    }
    
    return true;
}

// Password confirmation
function validatePassword() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return false;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        return false;
    }
    
    return true;
}

// Image preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Confirm delete actions
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`);
}

// Enhanced notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification-toast`;
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="margin-left: auto; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: inherit;">&times;</button>
        </div>
    `;
    
    // Add toast styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        max-width: 500px;
        animation: slideInRight 0.3s ease-out;
        box-shadow: var(--shadow-xl);
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        success: '✓',
        error: '✗',
        warning: '⚠',
        info: 'ℹ'
    };
    return icons[type] || icons.info;
}

// Auto-hide existing alerts with animation
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to all elements
    const elements = document.querySelectorAll('.dashboard-links, .welcome, .form-container, .product-card, .stat-card');
    elements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        setTimeout(() => {
            element.style.transition = 'all 0.5s ease-out';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Enhanced alert handling
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = `
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        `;
        closeBtn.onclick = () => alert.remove();
        alert.style.position = 'relative';
        alert.appendChild(closeBtn);
        
        // Auto-hide with animation
        setTimeout(function() {
            alert.style.transition = 'all 0.3s ease-out';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.textContent;
                submitBtn.innerHTML = '<span class="loading"></span> Processing...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds if form doesn't redirect
                setTimeout(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
});

// Quantity input validation
function validateQuantity(input, maxStock) {
    const value = parseInt(input.value);
    
    if (value > maxStock) {
        input.value = maxStock;
        alert(`Maximum available stock is ${maxStock}`);
    }
    
    if (value < 1) {
        input.value = 1;
    }
}

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    input.addEventListener('keyup', function() {
        const filter = input.value.toLowerCase();
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length; j++) {
                if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    });
}