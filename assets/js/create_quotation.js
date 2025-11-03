
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('add-item').addEventListener('click', addItemRow);
            document.getElementById('items-body').addEventListener('input', calculateTotals);
            document.getElementById('tax_rate').addEventListener('input', calculateTotals);
            calculateTotals();
        });
        
        function addItemRow() {
            const tbody = document.getElementById('items-body');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="item_description[]" placeholder="Description de l'article ou service" class="item-desc"></td>
                <td><input type="number" name="item_quantity[]" min="1" value="1" class="item-qty"></td>
                <td><input type="number" name="item_price[]" min="0" step="0.01" value="0.00" class="item-price"></td>
                <td class="item-total">0.00</td>
                <td><span class="delete-item" onclick="removeItem(this)"><i class="fas fa-trash"></i></span></td>
            `;
            tbody.appendChild(row);
            calculateTotals();
        }
        
        function removeItem(element) {
            const row = element.closest('tr');
            if (document.querySelectorAll('#items-body tr').length > 1) {
                row.remove();
            } else {
                row.querySelector('.item-desc').value = '';
                row.querySelector('.item-qty').value = 1;
                row.querySelector('.item-price').value = 0.00;
                row.querySelector('.item-total').textContent = '0.00';
            }
            calculateTotals();
        }
        
        function calculateTotals() {
            const rows = document.querySelectorAll('#items-body tr');
            let subtotal = 0;
            
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                const total = qty * price;
                
                row.querySelector('.item-total').textContent = total.toFixed(2);
                subtotal += total;
            });
            
            const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
            const taxAmount = subtotal * (taxRate / 100);
            const totalAmount = subtotal + taxAmount;
            
            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('tax-amount').textContent = taxAmount.toFixed(2);
            document.getElementById('total-amount').textContent = totalAmount.toFixed(2);
            document.getElementById('tax-rate-value').textContent = taxRate;
        }
    