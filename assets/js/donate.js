document.addEventListener('DOMContentLoaded', function() {
    const amountCards = document.querySelectorAll('.amount-card');
    const categoryCards = document.querySelectorAll('.category-card');
    const inputCategory = document.getElementById('donation_category');
    const customAmountTrigger = document.getElementById('custom-amount-trigger');
    const customAmountContainer = document.getElementById('custom-amount-container');
    const inputAmount = document.getElementById('input-amount');
    const btnOnetime = document.getElementById('btn-onetime');
    const btnMonthly = document.getElementById('btn-monthly');
    const inputFrequency = document.getElementById('input-frequency');
    const donationForm = document.getElementById('donation-form');
    const thankYouModal = document.getElementById('thank-you-modal');
    const displayAmount = document.getElementById('display-amount');
    
    // Payment Method Elements
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const cardDetailsSection = document.getElementById('card-details-section');
    const bankTransferSection = document.getElementById('bank-transfer-section');
    const transferRefInput = document.getElementById('transfer_ref');

    // Paystack Configuration
    const PAYSTACK_PUBLIC_KEY = 'pk_test_5df65f502799b3962fdaf7c8d839c68ddb3ea4c0';

    // Category Selection Logic
    categoryCards.forEach(card => {
        card.addEventListener('click', function() {
            categoryCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            inputCategory.value = this.getAttribute('data-category');
        });
    });

    // Amount Selection Logic
    amountCards.forEach(card => {
        card.addEventListener('click', function() {
            amountCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');

            if (this.id === 'custom-amount-trigger') {
                customAmountContainer.classList.remove('hidden');
                inputAmount.focus();
            } else {
                customAmountContainer.classList.add('hidden');
                inputAmount.value = this.getAttribute('data-amount');
            }
        });
    });

    // Payment Method Toggle Logic
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'transfer') {
                bankTransferSection.classList.remove('hidden');
                cardDetailsSection.classList.add('hidden');
                transferRefInput.required = true;
            } else {
                bankTransferSection.classList.add('hidden');
                cardDetailsSection.classList.remove('hidden');
                transferRefInput.required = false;
            }
        });
    });

    // Frequency Toggle Logic
    if (btnOnetime && btnMonthly) {
        btnOnetime.addEventListener('click', (e) => {
            e.preventDefault();
            inputFrequency.value = 'onetime';
            btnOnetime.classList.add('bg-white', 'shadow-sm', 'text-blue-600');
            btnOnetime.classList.remove('text-slate-500');
            btnMonthly.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
            btnMonthly.classList.add('text-slate-500');
        });

        btnMonthly.addEventListener('click', (e) => {
            e.preventDefault();
            inputFrequency.value = 'monthly';
            btnMonthly.classList.add('bg-white', 'shadow-sm', 'text-blue-600');
            btnMonthly.classList.remove('text-slate-500');
            btnOnetime.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
            btnOnetime.classList.add('text-slate-500');
        });
    }

    // Lead Capture: Notify server when someone proceeds
    async function captureLead(name, email, amount, category, method) {
        const leadData = new FormData();
        leadData.append('action', 'lead_capture');
        leadData.append('name', name);
        leadData.append('email', email);
        leadData.append('amount', amount);
        leadData.append('category', category);
        leadData.append('method', method);

        try {
            await fetch('process_donation.php', { method: 'POST', body: leadData });
        } catch (e) { console.warn('Lead capture failed silently.'); }
    }

    // Main Submission Logic
    if (donationForm) {
        donationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = this.name.value;
            const email = this.email.value;
            const amount = parseFloat(inputAmount.value);
            const category = inputCategory.value;
            const method = document.querySelector('input[name="payment_method"]:checked').value;

            if (!name || !email || isNaN(amount) || amount <= 0) {
                alert('Please provide valid contact and amount details.');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.disabled = true;

            // Log the "Lead" before continuing
            captureLead(name, email, amount, category, method);

            if (method === 'transfer') {
                // Manual Bank Transfer Logic
                submitBtn.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin h-5 w-5 mr-3 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Sending Receipt...</span>';
                
                const formData = new FormData(this);
                formData.append('action', 'manual_transfer');

                setTimeout(() => {
                    fetch('process_donation.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            displayAmount.innerText = '₦' + amount.toLocaleString();
                            showModal();
                            donationForm.reset();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                    });
                }, 2000);

            } else {
                // Paystack Logic
                submitBtn.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin h-5 w-5 mr-3 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Initializing Secure Payment...</span>';

                let handler = PaystackPop.setup({
                    key: PAYSTACK_PUBLIC_KEY,
                    email: email,
                    amount: amount * 100,
                    currency: 'NGN',
                    ref: 'HH-' + Math.floor((Math.random() * 1000000000) + 1),
                    callback: function(response) {
                        submitBtn.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin h-5 w-5 mr-3 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Verifying Transaction...</span>';
                        
                        const verificationData = new FormData();
                        verificationData.append('action', 'verify_paystack');
                        verificationData.append('reference', response.reference);
                        verificationData.append('name', name);
                        verificationData.append('amount', amount);

                        fetch('process_donation.php', { method: 'POST', body: verificationData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                displayAmount.innerText = '₦' + amount.toLocaleString();
                                showModal();
                                donationForm.reset();
                            } else {
                                alert('Verification Failed: ' + data.message);
                            }
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerText = originalText;
                        });
                    },
                    onClose: function() {
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                        alert('Transaction cancelled.');
                    }
                });
                handler.openIframe();
            }
        });
    }

    function showModal() {
        thankYouModal.classList.remove('hidden');
        setTimeout(() => {
            thankYouModal.classList.add('modal-show');
        }, 10);
    }

    window.closeModal = function() {
        thankYouModal.classList.remove('modal-show');
        setTimeout(() => {
            thankYouModal.classList.add('hidden');
            window.location.href = 'index.php';
        }, 300);
    };
});
