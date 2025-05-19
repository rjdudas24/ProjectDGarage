document.addEventListener('DOMContentLoaded', function() {
    // Find all add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    console.log('Found buttons:', addToCartButtons.length);
    
    // Add click event listener to each button
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Button clicked!');
            
            // Get part ID from data attribute
            const partId = this.getAttribute('data-part-id');
            console.log('Adding part ID:', partId);
            
            // Send request directly without the fancy UI changes
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
                alert(data.message || 'Action completed');
                
                if (data.success) {
                    // Change button text briefly
                    const originalText = this.textContent;
                    this.textContent = 'Added!';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error);
            });
        });
    });
});