# Image Lightbox Feature

## Overview
Added a lightbox feature that allows buyers to click on product images to view them in a larger, full-screen view.

## Features
- **Click to Enlarge**: Click any product image to view it in full size
- **Smooth Animations**: Fade-in and zoom effects for a polished experience
- **Product Info Display**: Shows product name and price below the enlarged image
- **Easy Close**: Close the lightbox by:
  - Clicking the red X button
  - Clicking outside the image
  - Pressing the Escape key
- **Zoom Indicator**: A magnifying glass icon appears on hover to indicate clickable images
- **Responsive Design**: Works perfectly on mobile and desktop devices

## Files Added
1. **js/image-lightbox.js** - JavaScript functionality for the lightbox
2. **css/image-lightbox.css** - Styling for the lightbox overlay and animations

## Files Updated
1. **buyer-dashboard.php** - Added lightbox CSS and JS
2. **product-list.php** - Added lightbox CSS and JS
3. **cart.php** - Added lightbox CSS and JS with proper image structure

## How It Works
1. When a buyer hovers over a product image, a magnifying glass icon appears
2. Clicking the image opens a full-screen overlay with the enlarged image
3. Product name and price are displayed below the image
4. The lightbox can be closed using multiple methods for user convenience

## Usage
The lightbox automatically works on:
- Product listing page (product-list.php)
- Buyer dashboard (buyer-dashboard.php)
- Shopping cart page (cart.php)

No additional configuration needed - just click on any product image!

## Browser Compatibility
- Works on all modern browsers (Chrome, Firefox, Safari, Edge)
- Fully responsive for mobile devices
- Smooth animations using CSS transitions
