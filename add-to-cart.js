document.addEventListener('DOMContentLoaded', function() {
    // Find all add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    console.log('Found buttons:', addToCartButtons.length);
    
    // Create notification container if it doesn't exist
    if (!document.getElementById('notification-container')) {
        const notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
    
    // Add click event listener to each button
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Button clicked!');
            
            // Get part ID from data attribute
            const partId = this.getAttribute('data-part-id');
            console.log('Adding part ID:', partId);
            
            // Store the original button text
            const originalText = this.textContent;
            
            // Change button text temporarily to show loading
            this.textContent = 'Adding...';
            this.disabled = true;
            
            // Send request
            const formData = new FormData();
            formData.append('part_id', partId);
            formData.append('quantity', 1);
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                
                // Show custom notification
                showNotification(data.message, data.success);
                
                // Update cart icon if available
                const cartButton = document.querySelector('.header-btn[href="cart.php"]');
                if (cartButton && data.success) {
                    cartButton.classList.add('cart-updated');
                    setTimeout(() => {
                        cartButton.classList.remove('cart-updated');
                    }, 1500);
                }
                
                if (data.success) {
                    // Change button text briefly to show success
                    this.textContent = 'Added!';
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.disabled = false;
                    }, 1500);
                } else {
                    // Reset button text immediately if there was an error
                    this.textContent = originalText;
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error notification
                showNotification('Error connecting to server. Please try again.', false);
                // Reset button
                this.textContent = originalText;
                this.disabled = false;
            });
        });
    });
    
    // Function to show custom notification
    function showNotification(message, isSuccess) {
        const notificationContainer = document.getElementById('notification-container');
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'notification ' + (isSuccess ? 'success-notification' : 'error-notification');
        notification.textContent = message;
        
        // Add to container
        notificationContainer.appendChild(notification);
        
        // Show notification with slight delay to allow for DOM rendering
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Set timeout to remove notification
        setTimeout(() => {
            notification.classList.remove('show');
            
            // Remove from DOM after animation completes
            setTimeout(() => {
                notification.remove();
            }, 300); // matches transition duration
        }, 4000); // display for 4 seconds
    }
});