// Format phone number to M-Pesa format (2547XXXXXXXX)
function formatMpesaPhoneNumber(phone) {
    // Remove all non-digit characters
    let cleanPhone = phone.replace(/\D/g, '');
    
    // Convert to 254 format if it's a local number
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '254' + cleanPhone.substring(1);
    } else if (cleanPhone.startsWith('+')) {
        cleanPhone = cleanPhone.substring(1);
    }
    
    return cleanPhone;
}

// Poll payment status
async function pollPaymentStatus(checkoutRequestId, submitButton, originalButtonText, attempt = 0) {
    const maxAttempts = 20; // Max 20 attempts (about 1 minute with 3-second intervals)
    
    if (attempt >= maxAttempts) {
        showNotification('Payment verification timed out. Please check your M-Pesa messages and refresh the page.', 'warning');
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
        return;
    }

    try {
        const response = await fetch(`check_payment_status.php?checkout_request_id=${checkoutRequestId}`);
        const data = await response.json();

        if (data.success) {
            if (data.status === 'completed') {
                // Payment successful
                showNotification('Payment received! Your order is being processed.', 'success');
                // Redirect to thank you page or order confirmation
                window.location.href = 'order_confirmation.php?order_id=' + data.data.order_id;
                return;
            } else if (data.status === 'failed' || data.status === 'cancelled') {
                // Payment failed or was cancelled
                throw new Error(data.message || 'Payment was not completed');
            } else {
                // Payment still pending, poll again after delay
                setTimeout(() => {
                    pollPaymentStatus(checkoutRequestId, submitButton, originalButtonText, attempt + 1);
                }, 3000); // Poll every 3 seconds
            }
        } else {
            throw new Error(data.message || 'Error checking payment status');
        }
    } catch (error) {
        console.error('Error polling payment status:', error);
        showNotification(error.message || 'Error verifying payment status. Please refresh the page and check your order status.', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const notificationContent = document.getElementById('notification-content');
    const notificationMessage = document.getElementById('notification-message');
    const notificationIcon = document.getElementById('notification-icon');

    if (!notification || !notificationContent || !notificationMessage || !notificationIcon) {
        console.error('Notification elements not found');
        return;
    }

    // Set message and styles based on type
    notificationMessage.textContent = message;
    notificationContent.className = `p-4 rounded-md shadow-lg ${type === 'success' ? 'bg-green-50' : type === 'error' ? 'bg-red-50' : 'bg-blue-50'}`;
    notificationIcon.className = `flex-shrink-0 ${type === 'success' ? 'text-green-400' : type === 'error' ? 'text-red-400' : 'text-blue-400'}`;
    notificationIcon.innerHTML = type === 'success' ? '<i class="fas fa-check-circle h-5 w-5"></i>' : 
                                 type === 'error' ? '<i class="fas fa-exclamation-circle h-5 w-5"></i>' : 
                                 '<i class="fas fa-info-circle h-5 w-5"></i>';

    // Show notification
    notification.classList.remove('hidden');
    notification.classList.add('block');

    // Auto-hide after 5 seconds
    setTimeout(() => {
        notification.classList.remove('block');
        notification.classList.add('hidden');
    }, 5000);
}

// Save customer info to localStorage
function saveCustomerInfo() {
    const customerInfo = {
        full_name: document.getElementById('full_name')?.value || '',
        email: document.getElementById('email')?.value || '',
        phone: document.getElementById('phone')?.value || '',
        address: document.getElementById('address')?.value || ''
    };
    localStorage.setItem('customerInfo', JSON.stringify(customerInfo));
}

// Initialize M-Pesa payment
async function initMpesaPayment(formData, submitButton, orderId = null) {
    try {
        // Show loading state
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

        // Get and validate M-Pesa phone number
        const mpesaPhone = formData.get('mpesa_phone');
        if (!mpesaPhone || mpesaPhone.trim() === '') {
            throw new Error('Please enter your M-Pesa phone number.');
        }

        // Format phone number
        const phoneNumber = formatMpesaPhoneNumber(mpesaPhone);
        
        // Get order amount from the page
        const orderAmount = document.querySelector('.order-total-amount')?.textContent?.replace(/[^0-9.]/g, '') || '0';
        
        // If no orderId is provided, create a temporary one (this should only happen if called directly)
        if (!orderId) {
            orderId = 'TEMP-' + Date.now();
        }
        
        // Prepare order data
        const orderData = {
            phone: phoneNumber,
            amount: orderAmount,
            order_id: orderId
        };

        // Send request to initiate M-Pesa payment
        const response = await fetch('initiate_stk_push.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });

        const result = await response.json();

        if (result.success) {
            // Show success message
            showNotification('Payment request sent to your phone. Please complete the payment on your phone.', 'success');
            
            // Start polling for payment status
            await pollPaymentStatus(result.data.checkout_request_id, submitButton, originalButtonText);
        } else {
            throw new Error(result.message || 'Failed to initiate M-Pesa payment');
        }
    } catch (error) {
        console.error('M-Pesa payment error:', error);
        showNotification(error.message || 'An error occurred while processing your payment. Please try again.', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}

// Function to handle form submission for M-Pesa payments
async function handleMpesaPayment(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    
    if (!submitButton) {
        console.error('Submit button not found');
        return;
    }
    
    try {
        // First submit the form to create the order
        const submitResponse = await fetch('process_checkout.php', {
            method: 'POST',
            body: formData
        });

        const result = await submitResponse.json();

        if (result.success) {
            // If order was created successfully, initiate M-Pesa payment
            await initMpesaPayment(formData, submitButton, result.order_id);
        } else {
            throw new Error(result.message || 'Failed to process your order');
        }
    } catch (error) {
        console.error('Order submission error:', error);
        showNotification(error.message || 'An error occurred while processing your order. Please try again.', 'error');
        submitButton.disabled = false;
        submitButton.textContent = 'Place Order';
    }
}

// Initialize the checkout form
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkout-form');
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    const paymentDetails = document.getElementById('payment-details');
    const mpesaDetails = document.getElementById('mpesa-details');
    const cardDetails = document.getElementById('card-details');
    const airtelDetails = document.getElementById('airtel-details');

    // Load saved customer info
    const savedInfo = localStorage.getItem('customerInfo');
    if (savedInfo) {
        const customerInfo = JSON.parse(savedInfo);
        Object.keys(customerInfo).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.value = customerInfo[key];
            }
        });
    }

    // Save customer info when input changes
    const formFields = ['full_name', 'email', 'phone', 'address'];
    formFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', saveCustomerInfo);
        }
    });

    // Toggle payment details based on selected payment method
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                // Show payment details container if not cash on delivery
                if (this.value !== 'cash') {
                    if (paymentDetails) paymentDetails.classList.remove('hidden');
                    
                    // Show relevant payment method details
                    if (mpesaDetails) mpesaDetails.classList.toggle('hidden', this.value !== 'mpesa');
                    if (cardDetails) cardDetails.classList.toggle('hidden', this.value !== 'card');
                    if (airtelDetails) airtelDetails.classList.toggle('hidden', this.value !== 'airtel');
                } else {
                    if (paymentDetails) paymentDetails.classList.add('hidden');
                }
            }
        });
    });

    // Initialize form submission
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default form submission

            // Get form data
            const formData = new FormData(this);
            const paymentMethod = formData.get('payment_method');
            const submitButton = this.querySelector('button[type="submit"]');

            if (!submitButton) {
                console.error('Submit button not found');
                return;
            }

            // Validate required fields
            const requiredFields = ['full_name', 'email', 'phone', 'address'];
            const missingFields = [];
            
            requiredFields.forEach(field => {
                if (!formData.get(field)?.trim()) {
                    missingFields.push(field);
                }
            });

            if (missingFields.length > 0) {
                showNotification(`Please fill in all required fields: ${missingFields.join(', ')}`, 'error');
                return;
            }

            // Handle M-Pesa payment
            if (paymentMethod === 'mpesa') {
                // First submit the form to create the order
                try {
                    const submitResponse = await fetch('process_checkout.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await submitResponse.json();

                    if (result.success) {
                        // If order was created successfully, initiate M-Pesa payment
                        await initMpesaPayment({
                            get: (key) => {
                                if (key === 'mpesa_phone') return formData.get('mpesa_phone');
                                return null;
                            }
                        }, submitButton, result.order_id);
                    } else {
                        throw new Error(result.message || 'Failed to process your order');
                    }
                } catch (error) {
                    console.error('Order submission error:', error);
                    showNotification(error.message || 'An error occurred while processing your order. Please try again.', 'error');
                    submitButton.disabled = false;
                    submitButton.textContent = 'Place Order';
                }
            } 
            // Handle other payment methods
            else {
                try {
                    const originalButtonText = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

                    const response = await fetch('process_checkout.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Redirect to thank you page
                        window.location.href = 'order_confirmation.php?order_id=' + result.order_id;
                    } else {
                        throw new Error(result.message || 'Failed to process your order');
                    }
                } catch (error) {
                    console.error('Form submission error:', error);
                    showNotification(error.message || 'An error occurred while processing your order. Please try again.', 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Place Order';
                }
            }
        });
    }
});
