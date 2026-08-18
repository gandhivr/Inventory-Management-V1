// Image Lightbox Functionality

document.addEventListener('DOMContentLoaded', function() {
    // Create lightbox overlay element
    const lightboxOverlay = document.createElement('div');
    lightboxOverlay.className = 'lightbox-overlay';
    lightboxOverlay.innerHTML = `
        <div class="lightbox-content">
            <button class="lightbox-close" aria-label="Close">&times;</button>
            <img class="lightbox-image" src="" alt="">
            <div class="lightbox-info">
                <h3 class="lightbox-title"></h3>
                <div class="price lightbox-price"></div>
            </div>
        </div>
    `;
    document.body.appendChild(lightboxOverlay);

    // Get lightbox elements
    const lightboxImage = lightboxOverlay.querySelector('.lightbox-image');
    const lightboxTitle = lightboxOverlay.querySelector('.lightbox-title');
    const lightboxPrice = lightboxOverlay.querySelector('.lightbox-price');
    const closeButton = lightboxOverlay.querySelector('.lightbox-close');

    // Function to open lightbox
    function openLightbox(imageSrc, productName, productPrice) {
        lightboxImage.src = imageSrc;
        lightboxImage.alt = productName;
        lightboxTitle.textContent = productName;
        lightboxPrice.textContent = productPrice;
        lightboxOverlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    // Function to close lightbox
    function closeLightbox() {
        lightboxOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Restore scrolling
        // Clear image after animation
        setTimeout(() => {
            lightboxImage.src = '';
        }, 300);
    }

    // Add click event to all product images (in product cards)
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        const imageContainer = card.querySelector('.product-image-container');
        const image = card.querySelector('.product-image');
        
        // Only add click event if there's an actual image (not placeholder)
        if (imageContainer && image) {
            imageContainer.addEventListener('click', function() {
                const productName = card.querySelector('h3, h4')?.textContent || 'Product';
                const productPrice = card.querySelector('.price, .product-price')?.textContent || '';
                const imageSrc = image.src;
                
                openLightbox(imageSrc, productName, productPrice);
            });
        }
    });

    // Add click event to cart items
    const cartItems = document.querySelectorAll('.cart-item');
    cartItems.forEach(item => {
        const imageContainer = item.querySelector('.product-image-container');
        const image = item.querySelector('.product-image');
        
        // Only add click event if there's an actual image (not placeholder)
        if (imageContainer && image) {
            imageContainer.addEventListener('click', function() {
                const productName = item.querySelector('h4')?.textContent || 'Product';
                const productPrice = item.querySelector('.price')?.textContent || '';
                const imageSrc = image.src;
                
                openLightbox(imageSrc, productName, productPrice);
            });
        }
    });

    // Close lightbox when clicking close button
    closeButton.addEventListener('click', closeLightbox);

    // Close lightbox when clicking outside the image
    lightboxOverlay.addEventListener('click', function(e) {
        if (e.target === lightboxOverlay) {
            closeLightbox();
        }
    });

    // Close lightbox with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightboxOverlay.classList.contains('active')) {
            closeLightbox();
        }
    });
});
