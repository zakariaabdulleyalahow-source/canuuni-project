document.addEventListener('DOMContentLoaded', function() {
    updateCartBadge();

    // Handle Add to Cart Click
    document.querySelectorAll('.btn-add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const product = {
                id: this.getAttribute('data-id'),
                name: this.getAttribute('data-name'),
                price: parseFloat(this.getAttribute('data-price')),
                quantity: 1
            };
            
            addToLocalStorageCart(product);
        });
    });

    // Handle Pre-Order Click
    document.querySelectorAll('.btn-preorder').forEach(button => {
        button.addEventListener('click', async function() {
            const productId = this.getAttribute('data-id');

            const { value: comment } = await Swal.fire({
                title: 'Pre-Order Product',
                input: 'textarea',
                inputLabel: 'Notes or Special Instructions',
                inputPlaceholder: 'Type your message here...',
                showCancelButton: true
            });

            if (comment !== undefined) {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('comment', comment);

                const res = await fetch('ajax/preorder.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.status === 'success') {
                    Swal.fire('Success', data.message, 'success');
                } else if (data.status === 'unauthorized') {
                    Swal.fire('Login Required', data.message, 'warning');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        });
    });

    function getCart() {
        return JSON.parse(localStorage.getItem('canuuni_cart')) || [];
    }

    function addToLocalStorageCart(item) {
        let cart = getCart();
        let existingItem = cart.find(i => i.id === item.id);

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push(item);
        }

        localStorage.setItem('canuuni_cart', JSON.stringify(cart));
        updateCartBadge();
        
        Swal.fire({
            icon: 'success',
            title: `${item.name} added to cart!`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    }

    function updateCartBadge() {
        let cart = getCart();
        let totalQuantity = cart.reduce((total, item) => total + item.quantity, 0);
        const badge = document.getElementById('cart-counter-badge');
        if (badge) {
            badge.innerText = totalQuantity;
        }
    }
});