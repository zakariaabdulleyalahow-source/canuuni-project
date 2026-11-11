document.addEventListener('DOMContentLoaded', function () {
    // Add to Cart
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-add-to-cart');
        if (!btn) return;

        const productId = btn.getAttribute('data-id');
        
        // Grab quantity from the product details input if it exists, otherwise default to 1
        const qtyInput = document.getElementById('product-qty');
        const quantity = qtyInput ? qtyInput.value : 1;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        fetch('ajax/cart.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const badge = document.getElementById('cart-counter-badge');
                    if (badge) badge.innerText = data.cartCount;
                    Swal.fire({ icon: 'success', title: 'Added to Cart', text: data.message, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Notice', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' }));
    });

    // Handle Pre-Order Button
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-preorder');
        if (!btn) return;

        const productId = btn.getAttribute('data-id');

        Swal.fire({
            title: 'Place Pre-Order',
            input: 'textarea',
            inputLabel: 'Notes or Special Instructions (Optional)',
            inputPlaceholder: 'Type your message here...',
            showCancelButton: true,
            confirmButtonText: 'Submit Pre-Order',
            showLoaderOnConfirm: true,
            preConfirm: (comment) => {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('comment', comment);

                return fetch('ajax/preorder.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'unauthorized') {
                            window.location.href = 'customer/login';
                        } else if (data.status !== 'success') {
                            throw new Error(data.message);
                        }
                        return data;
                    })
                    .catch(err => Swal.showValidationMessage(err.message));
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ icon: 'success', title: 'Success', text: result.value.message });
            }
        });
    });

    // Update Quantity
    document.body.addEventListener('change', function (e) {
        if (!e.target.classList.contains('cart-qty-input')) return;
        const productId = e.target.getAttribute('data-id');
        const qty = e.target.value;

        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('quantity', qty);

        fetch('ajax/cart.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if (data.status === 'success') window.location.reload(); });
    });

    // Remove Item
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-item');
        if (!btn) return;
        const productId = btn.getAttribute('data-id');

        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', productId);

        fetch('ajax/cart.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if (data.status === 'success') window.location.reload(); });
    });

    // Clear Cart
    const clearBtn = document.getElementById('btn-clear-cart');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            const formData = new FormData();
            formData.append('action', 'clear');
            fetch('ajax/cart.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => { if (data.status === 'success') window.location.reload(); });
        });
    }
});