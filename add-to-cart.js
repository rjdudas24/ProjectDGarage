document.addEventListener('DOMContentLoaded', function() {
    // Find all add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    // Add click event listener to each button
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Check if button is disabled
            if (button.disabled) {
                return;
            }
            
            // Get the parent part card element
            const partCard = button.closest('.part-card');
            
            // Get part ID from data attribute
            const partId = button.getAttribute('data-part-id');
            
            // Default quantity is 1, but we could add quantity selector in the future
            const quantity = 1;
            
            // Add visual feedback - change button text temporarily
            const originalText = button.textContent;
            button.textContent = 'Adding...';
            button.disabled = true;
            
            // Send AJAX request to add_to_cart.php
            addToCart(partId, quantity)
                .then(response => {
                    // Show success message
                    if (response.success) {
                        showNotification(response.message, 'success');
                        
                        // Update cart count in header if it exists
                        updateCartCount();
                        
                        // Change button text briefly to "Added!"
                        button.textContent = 'Added!';
                        setTimeout(() => {
                            button.textContent = originalText;
                            button.disabled = false;
                        }, 1500);
                    } else {
                        // Show error message
                        showNotification(response.message, 'error');
                        button.textContent = originalText;
                        button.disabled = false;
                        
                        // If not logged in, redirect to login page
                        if (response.message.includes('log in')) {
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 1500);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                    showNotification('Error adding item to cart. Please try again.', 'error');
                    button.textContent = originalText;
                    button.disabled = false;
                });
        });
    });
    
    // Function to send AJAX request to add_to_cart.php
    function addToCart(partId, quantity) {
        // Create form data
        const formData = new FormData();
        formData.append('part_id', partId);
        formData.append('quantity', quantity);
        
        // Send POST request
        return fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        });
    }
    
    // Function to show notification
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}-notification`;
        notification.textContent = message;
        
        // Add to document
        document.body.appendChild(notification);
        
        // Show notification with animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Remove notification after delay
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Function to update cart count in header
    function updateCartCount() {
        // This could be implemented to update a cart counter in the header
        // For now, we'll just add a visual indicator to the cart button
        const cartBtn = document.querySelector('.header-btn:nth-child(2)');
        if (cartBtn) {
            cartBtn.classList.add('cart-updated');
            setTimeout(() => {
                cartBtn.classList.remove('cart-updated');
            }, 1000);
        }
    }
});
