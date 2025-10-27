// Fonctions utilitaires JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des messages flash
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Confirmation des suppressions
    const deleteButtons = document.querySelectorAll('a[href*="delete"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });

    // Calcul automatique des totaux pour les factures
    if (document.getElementById('items-body')) {
        initInvoiceCalculator();
    }
});

function initInvoiceCalculator() {
    const itemsBody = document.getElementById('items-body');
    const taxRateInput = document.getElementById('tax_rate');
    
    function calculateTotals() {
        const rows = itemsBody.querySelectorAll('tr');
        let subtotal = 0;
        
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const total = qty * price;
            
            const totalCell = row.querySelector('.item-total');
            if (totalCell) {
                totalCell.textContent = total.toFixed(2);
            }
            subtotal += total;
        });
        
        const taxRate = parseFloat(taxRateInput.value) || 0;
        const taxAmount = subtotal * (taxRate / 100);
        const totalAmount = subtotal + taxAmount;
        
        const subtotalEl = document.getElementById('subtotal');
        const taxAmountEl = document.getElementById('tax-amount');
        const totalAmountEl = document.getElementById('total-amount');
        const taxRateValueEl = document.getElementById('tax-rate-value');
        
        if (subtotalEl) subtotalEl.textContent = subtotal.toFixed(2);
        if (taxAmountEl) taxAmountEl.textContent = taxAmount.toFixed(2);
        if (totalAmountEl) totalAmountEl.textContent = totalAmount.toFixed(2);
        if (taxRateValueEl) taxRateValueEl.textContent = taxRate;
    }
    
    itemsBody.addEventListener('input', calculateTotals);
    if (taxRateInput) {
        taxRateInput.addEventListener('input', calculateTotals);
    }
    
    // Initial calculation
    calculateTotals();
}

function addInvoiceItem() {
    const itemsBody = document.getElementById('items-body');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="item_description[]" class="item-desc" placeholder="Description"></td>
        <td><input type="number" name="item_quantity[]" class="item-qty" min="1" value="1"></td>
        <td><input type="number" name="item_price[]" class="item-price" min="0" step="0.01" value="0.00"></td>
        <td class="item-total">0.00</td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeInvoiceItem(this)"><i class="fas fa-trash"></i></button></td>
    `;
    itemsBody.appendChild(row);
    initInvoiceCalculator();
}

function removeInvoiceItem(button) {
    const row = button.closest('tr');
    const itemsBody = document.getElementById('items-body');
    if (itemsBody.querySelectorAll('tr').length > 1) {
        row.remove();
        initInvoiceCalculator();
    }
}

// Fonction pour exporter en PDF (placeholder)
function exportToPDF() {
    alert('Fonction d\'export PDF à implémenter');
}

// Fonction pour l'impression
function printInvoice() {
    window.print();
}