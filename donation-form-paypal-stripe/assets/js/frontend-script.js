document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('#custom-onetime-donation-form, #custom-monthly-donation-form');
    if (!forms.length) return;

    forms.forEach(form => {
        const amtButtons  = form.querySelectorAll('.amt-btn');
        const amountInput = form.querySelector('.donation-amount-input');
        const gatewayRows = form.querySelectorAll('.gateway-row');

        // Handle preset amount button clicks
        amtButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                amtButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                if (amountInput) {
                    amountInput.value = this.getAttribute('data-val');
                }
            });
        });

        // Handle manual typing in custom amount input field
        if (amountInput) {
            amountInput.addEventListener('input', function() {
                amtButtons.forEach(btn => {
                    if (btn.getAttribute('data-val') === this.value) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            });
        }

        // Handle payment gateway selection card state switches
        gatewayRows.forEach(row => {
            row.addEventListener('click', function(e) {
                gatewayRows.forEach(r => r.classList.remove('active'));
                this.classList.add('active');
                const radioInput = row.querySelector('input[type="radio"]');
                if (radioInput && e.target !== radioInput) {
                    radioInput.checked = true;
                }
            });
        });
    });
});
