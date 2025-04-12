document.addEventListener('DOMContentLoaded', function () {
    // Initialize Stripe
    const stripe = Stripe(checkoutData.publishable_key);
    const elements = stripe.elements();
    const card = elements.create('card');
    card.mount('#card-element');

    // Show error messages
    function showError(message) {
        const errorElement = document.getElementById('card-errors');
        errorElement.textContent = message;
        errorElement.style.display = message ? 'block' : 'none';
    }

    // Handle 3D Secure authentication
    async function handle3DSecure(clientSecret) {
        const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret);
        
        if (error) {
            showError(error.message);
            return false;
        }
        
        if (paymentIntent.status === 'succeeded') {
            window.location.href = checkoutData.successUrl + '?payment_intent=' + paymentIntent.id;
            return true;
        }
        
        showError('Payment failed. Please try again.');
        return false;
    }

    // Get numeric value from price string
    function getPriceAmount(priceString) {
        return parseFloat(priceString.replace(/[^0-9.-]+/g, '')) * 100;
    }

    // Handle form submission
    const paymentForm = document.getElementById('checkout-form');
    const payNowButton = document.getElementById('pay-now-button');

    paymentForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        // Clear previous errors
        showError('');

        // Disable submit button
        payNowButton.disabled = true;
        payNowButton.textContent = 'Processing...';

        try {
            // Collect form data
            const formData = new FormData(paymentForm);
            const totalPrice = getPriceAmount(document.getElementById('total_price').innerText);
            
            // Validate required fields
            const requiredFields = ['fname', 'lname', 'email', 'table_number', 'table-type'];
            for (const field of requiredFields) {
                if (!formData.get(field)) {
                    throw new Error(`${field.replace('-', ' ')} is required`);
                }
            }

            // Create payment method
            const { paymentMethod, error: paymentMethodError } = await stripe.createPaymentMethod({
                type: 'card',
                card: card,
                billing_details: {
                    email: formData.get('email'),
                    name: `${formData.get('fname')} ${formData.get('lname')}`,
                }
            });

            if (paymentMethodError) {
                throw new Error(paymentMethodError.message);
            }

            // Process payment
            const response = await fetch(checkoutData.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'process_payment',
                    payment_method: paymentMethod.id,
                    total_amount: totalPrice,
                    table_number: formData.get('table_number'),
                    seat_quantity: formData.get('seat-quantity'),
                    table_type: formData.get('table-type'),
                    fname: formData.get('fname'),
                    lname: formData.get('lname'),
                    email: formData.get('email'),
                    company_name: formData.get('cname'),
                    payMethod: 'Card',
                    location: formData.get('location') || '',
                    bin_number: formData.get('bin_number') || '',
                    total_vat: document.getElementById('vat_amount').innerText.replace(/[^0-9.-]+/g, ''),
                    vatPercentage: document.getElementById('vat_percentage').innerText.replace(/[^0-9.-]+/g, '')
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.data || 'Payment failed');
            }

            // Handle 3D Secure if required
            if (result.data.requires_action) {
                await handle3DSecure(result.data.payment_intent_client_secret);
            } else {
                // Payment successful
                window.location.href = result.data.redirect_url;
            }

        } catch (error) {
            showError(error.message);
            payNowButton.disabled = false;
            payNowButton.textContent = 'Pay Now';
        }
    });

    // Update card error styling on input
    card.addEventListener('change', function(event) {
        showError(event.error ? event.error.message : '');
    });
});
