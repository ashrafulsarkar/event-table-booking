document.addEventListener('DOMContentLoaded', function () {
    // Initialize Stripe
    const stripe = Stripe(checkoutData.publishable_key);
    const elements = stripe.elements();
    const card = elements.create('card');
    card.mount('#card-element');

    // Handle form submission
    const payNowButton = document.getElementById('pay-now-button');
    payNowButton.addEventListener('click', async function (event) {
        event.preventDefault();

        // Disable the button to prevent duplicate clicks
        payNowButton.disabled = true;

        // Collect form data
        const totalPrice = document.getElementById('total_price').innerText.trim();
        const seatQuantity = document.getElementById('seat-quantity').innerText.trim();
        const tableNumber = document.getElementById('table_number').value.trim();
        const tableType = document.getElementById('table-type').value.trim();
        const fname = document.getElementById('fname').value.trim();
        const lname = document.getElementById('lname').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const companyName = document.getElementById('cname').value.trim();

        // Validate fields
        if (!totalPrice || !tableNumber || !tableType || !fname || !lname || !email) {
            alert('Please fill out all required fields.');
            payNowButton.disabled = false;
            return;
        }

        // Create a payment method
        const { paymentMethod, error } = await stripe.createPaymentMethod({
            type: 'card',
            card: card,
            billing_details: {
                email: email,
                name: `${fname} ${lname}`,
            },
        });

        if (error) {
            // Display error in #card-errors
            document.getElementById('card-errors').textContent = error.message;
            payNowButton.disabled = false;
            return;
        }        

        // Send payment details to the server
        jQuery.ajax({
            url: checkoutData.ajax_url,
            method: 'POST',
            data: {
                action: 'process_payment', // Must match the PHP action hook
                payment_method: paymentMethod.id, // Should not be null/undefined
                amount: totalPrice * 100, // Ensure this is a valid number
                table_number: tableNumber,
                seat_quantity: seatQuantity,
                table_type: tableType,
                fname: fname,
                lname: lname,
                email: email,
                phone: phone,
                company_name: companyName,
            },
            success: function (response) {
                if (response.success) {
                    // alert('Payment successful! Redirecting to confirmation page...');
                    // console.log(response.data.payment_intent);
                    window.location.href = response.data.redirect_url+'?payment_intent='+response.data.payment_intent.id;
                } else {
                    payNowButton.disabled = false;
                    alert('Payment failed: ' + response.data);                    
                }
            },
            error: function (xhr, status, error) {
                // console.log('AJAX Error:', xhr.responseText);
                payNowButton.disabled = false;
                alert('Payment failed: ' + error);
            },
        });
    });
});
