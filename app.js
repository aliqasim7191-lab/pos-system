let cart = [];
let customers = [];
let currentTotal = 0;
let currentPoints = 0;

function fetchCustomers() {
    fetch('api_customers.php')
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            customers = data.customers;
            let sel = document.getElementById('customerSelect');
            if(sel) {
                // We keep the first option as is (handled by HTML now, we shouldn't overwrite the whole thing if we added attributes in index.php)
                // Actually wait, fetchCustomers overwrites innerHTML. I must preserve the data-points logic here!
                sel.innerHTML = '<option value="" data-type="walk_in" data-points="0">Walk-in Customer</option>';
                customers.forEach(c => {
                    let typeText = c.customer_type === 'credit' ? ' [B2B]' : '';
                    let pts = c.points || c.loyalty_points || 0; // fallback to either name
                    let ptsText = pts ? ` | ⭐ ${pts} Pts` : '';
                    sel.innerHTML += `<option value="${c.id}" data-type="${c.customer_type}" data-points="${pts}">${c.name}${typeText} (Bal: $${c.outstanding_balance})${ptsText}</option>`;
                });
            }
        }
    });
}

function addToCart(id, name, retailPrice, wholesalePrice, unit, qty = 1, variation_id = null) {
    qty = parseFloat(qty);
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.quantity += qty;
    } else {
        let product_id = id;
        if (typeof id === 'string' && id.includes('-')) {
            product_id = parseInt(id.split('-')[0]); // Extract original product ID
        }
        cart.push({ 
            id, 
            product_id: product_id,
            variation_id: variation_id,
            name, 
            retail_price: parseFloat(retailPrice), 
            wholesale_price: parseFloat(wholesalePrice) || parseFloat(retailPrice),
            price: parseFloat(retailPrice), // Default to retail
            quantity: qty, 
            unit: unit || 'pcs',
            tax_rate: PRODUCTS_DATA[product_id] ? parseFloat(PRODUCTS_DATA[product_id].tax_rate) : globalTaxRate
        });
    }
    
    handleCustomerChange(); // recalculates and renders cart
    
    // Clear search boxes to allow rapid scanning/searching of next item
    const searchInput = document.getElementById('productSearch');
    const barcodeInput = document.getElementById('barcodeScanner');
    let needsReset = false;
    
    if (searchInput && searchInput.value !== '') {
        searchInput.value = '';
        needsReset = true;
    }
    if (barcodeInput && barcodeInput.value !== '') {
        barcodeInput.value = '';
        needsReset = true;
    }
    
    if (needsReset && typeof window.filterProductsGrid === 'function') {
        window.filterProductsGrid('');
    }
    if (barcodeInput) barcodeInput.focus();
}

function openProductSelectionModal(id) {
    const p = PRODUCTS_DATA[id];
    if (!p) { alert('Product not found in data! ID: ' + id); return; }

    document.getElementById('ps-id').value = id;
    document.getElementById('ps-name-display').innerText = p.name;
    document.getElementById('ps-name').value = p.name;
    document.getElementById('ps-retail').value = p.price;
    document.getElementById('ps-wholesale').value = p.wholesale_price || p.price;
    const stockVal = parseFloat(p.stock) || 0;
    const minStock = parseFloat(p.min_stock) || 10;
    const stockEl = document.getElementById('ps-stock-display');
    stockEl.innerText = p.stock + (stockVal <= minStock ? ' (Low Stock)' : '');
    stockEl.style.color = stockVal <= minStock ? '#ef4444' : '#10b981';
    stockEl.style.fontWeight = 'bold';
    document.getElementById('ps-unit').value = p.unit || 'pcs';
    document.getElementById('ps-unit-display').innerText = p.unit || 'pcs';
    document.getElementById('ps-price-display').innerText = '$' + parseFloat(p.price).toFixed(2);
    
    const varContainer = document.getElementById('ps-variations-container');
    const varSelect = document.getElementById('ps-variation');
    varSelect.innerHTML = '';
    
    if (p.variations && p.variations.length > 0) {
        varContainer.style.display = 'block';
        p.variations.forEach(v => {
            if (parseFloat(v.stock) >= 10) { // Apply 10 stock rule for variations too
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.dataset.price = v.price ? v.price : p.price;
                opt.dataset.stock = v.stock;
                opt.dataset.name = v.variation_name;
                opt.innerText = v.variation_name + " - " + opt.dataset.stock + " in stock";
                varSelect.appendChild(opt);
            }
        });
        if (varSelect.options.length === 0) {
            alert("No variations have enough stock (minimum 10 required).");
            return;
        }
        handleVariationChange(); // trigger initial load
    } else {
        varContainer.style.display = 'none';
        varSelect.innerHTML = '<option value="">None</option>';
    }

    const qtyInput = document.getElementById('ps-qty');
    qtyInput.value = 1;
    
    qtyInput.onkeypress = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmProductSelection();
        }
    };
    
    document.getElementById('productSelectionModal').style.display = 'flex';
    setTimeout(() => {
        qtyInput.focus();
        qtyInput.select();
    }, 100);
}

function adjustPsQty(amount) {
    let current = parseFloat(document.getElementById('ps-qty').value) || 0;
    let newQty = current + amount;
    if (newQty < 1) newQty = 1;
    document.getElementById('ps-qty').value = newQty;
}

function handleVariationChange() {
    const select = document.getElementById('ps-variation');
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value !== "") {
        document.getElementById('ps-price-display').innerText = '$' + parseFloat(opt.dataset.price).toFixed(2);
        document.getElementById('ps-stock-display').innerText = opt.dataset.stock;
        document.getElementById('ps-retail').value = opt.dataset.price;
        // Wholesale remains parent for now unless we add var wholesale
    }
}

function confirmProductSelection() {
    let id = document.getElementById('ps-id').value;
    let name = document.getElementById('ps-name').value;
    let retailPrice = document.getElementById('ps-retail').value;
    let wholesalePrice = document.getElementById('ps-wholesale').value;
    let unit = document.getElementById('ps-unit').value;
    let qty = parseFloat(document.getElementById('ps-qty').value) || 1;
    
    let varSelect = document.getElementById('ps-variation');
    let variation_id = null;
    if (varSelect.options.length > 0 && varSelect.value !== "") {
        variation_id = varSelect.value;
        let opt = varSelect.options[varSelect.selectedIndex];
        name = name + " (" + opt.dataset.name + ")";
        id = id + "-" + variation_id; // temporary composite ID for the cart array
    }
    
    addToCart(id, name, retailPrice, wholesalePrice, unit, qty, variation_id);
    document.getElementById('productSelectionModal').style.display = 'none';
}

function closeProductSelectionModal() {
    document.getElementById('productSelectionModal').style.display = 'none';
}

function openQeModal(id) {
    let item = cart.find(i => i.id === id);
    if (!item) return;

    document.getElementById('qe-id').value = item.id;
    document.getElementById('qe-name-display').innerText = item.name;
    document.getElementById('qe-price-input').value = parseFloat(item.price).toFixed(2);
    document.getElementById('qe-unit-display').innerText = item.unit || 'pcs';
    
    const qtyInput = document.getElementById('qe-qty');
    qtyInput.value = item.quantity;
    
    qtyInput.onkeypress = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmQeSelection();
        }
    };
    
    document.getElementById('quantityEditModal').style.display = 'flex';
    setTimeout(() => {
        qtyInput.focus();
        qtyInput.select();
    }, 100);
}

function adjustQeQty(amount) {
    let current = parseFloat(document.getElementById('qe-qty').value) || 0;
    let newQty = current + amount;
    if (newQty < 0.1) newQty = 0.1;
    document.getElementById('qe-qty').value = newQty;
}
function confirmQeSelection() {
    let id = document.getElementById('qe-id').value;
    let qty = parseFloat(document.getElementById('qe-qty').value) || 1;
    let price = parseFloat(document.getElementById('qe-price-input').value) || 0;
    
    let item = cart.find(i => i.id == id);
    if (item) {
        item.quantity = qty;
        item.price = price;
    }
    
    renderCart();
    document.getElementById('quantityEditModal').style.display = 'none';
}

function addToCart(id, name, retailPrice, wholesalePrice, unit, qty = 1, variation_id = null) {
    if (typeof window.clearAutoScanTimer === 'function') window.clearAutoScanTimer();
    qty = parseFloat(qty);
    const existingIndex = cart.findIndex(item => item.id == id);
    if (existingIndex > -1) {
        let existingItem = cart.splice(existingIndex, 1)[0];
        existingItem.quantity += qty;
        cart.unshift(existingItem);
    } else {
        let product_id = id;
        if (typeof id === 'string' && id.includes('-')) {
            product_id = parseInt(id.split('-')[0]); // Extract original product ID
        } else {
            product_id = parseInt(id);
        }
        cart.unshift({ 
            id: id, 
            product_id: product_id,
            variation_id: variation_id,
            name: name, 
            retail_price: parseFloat(retailPrice), 
            wholesale_price: parseFloat(wholesalePrice) || parseFloat(retailPrice),
            price: parseFloat(retailPrice), // Default to retail
            quantity: qty, 
            unit: unit || 'pcs',
            tax_rate: PRODUCTS_DATA[product_id] ? parseFloat(PRODUCTS_DATA[product_id].tax_rate) : globalTaxRate
        });
    }
    
    handleCustomerChange(); // recalculates and renders cart
    
    // Auto-scroll cart container to top so newly added item at top is immediately visible
    const cartEl = document.getElementById("cart-items");
    if (cartEl) cartEl.scrollTop = 0;
    
    // Clear search boxes to allow rapid scanning/searching of next item
    const searchInput = document.getElementById('productSearch');
    const barcodeInput = document.getElementById('barcodeScanner');
    let needsReset = false;
    
    if (searchInput && searchInput.value !== '') {
        searchInput.value = '';
        needsReset = true;
    }
    if (barcodeInput && barcodeInput.value !== '') {
        barcodeInput.value = '';
        needsReset = true;
    }
    
    if (needsReset && typeof window.filterProductsGrid === 'function') {
        window.filterProductsGrid('');
    }
    if (barcodeInput) barcodeInput.focus();
}

function openProductSelectionModal(id) {
    const p = PRODUCTS_DATA[id];
    if (!p) { alert('Product not found in data! ID: ' + id); return; }

    document.getElementById('ps-id').value = id;
    document.getElementById('ps-name-display').innerText = p.name;
    document.getElementById('ps-name').value = p.name;
    document.getElementById('ps-retail').value = p.price;
    document.getElementById('ps-wholesale').value = p.wholesale_price || p.price;
    document.getElementById('ps-stock-display').innerText = p.stock;
    document.getElementById('ps-unit').value = p.unit || 'pcs';
    document.getElementById('ps-unit-display').innerText = p.unit || 'pcs';
    document.getElementById('ps-price-display').innerText = '$' + parseFloat(p.price).toFixed(2);
    
    const varContainer = document.getElementById('ps-variations-container');
    const varSelect = document.getElementById('ps-variation');
    varSelect.innerHTML = '';
    
    if (p.variations && p.variations.length > 0) {
        varContainer.style.display = 'block';
        p.variations.forEach(v => {
            if (parseFloat(v.stock) >= 10) { // Apply 10 stock rule for variations too
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.dataset.price = v.price ? v.price : p.price;
                opt.dataset.stock = v.stock;
                opt.dataset.name = v.variation_name;
                opt.innerText = v.variation_name + " - " + opt.dataset.stock + " in stock";
                varSelect.appendChild(opt);
            }
        });
        if (varSelect.options.length === 0) {
            alert("No variations have enough stock (minimum 10 required).");
            return;
        }
        handleVariationChange(); // trigger initial load
    } else {
        varContainer.style.display = 'none';
        varSelect.innerHTML = '<option value="">None</option>';
    }

    const qtyInput = document.getElementById('ps-qty');
    qtyInput.value = 1;
    
    qtyInput.onkeypress = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmProductSelection();
        }
    };
    
    document.getElementById('productSelectionModal').style.display = 'flex';
    setTimeout(() => {
        qtyInput.focus();
        qtyInput.select();
    }, 100);
}

function adjustPsQty(amount) {
    let current = parseFloat(document.getElementById('ps-qty').value) || 0;
    let newQty = current + amount;
    if (newQty < 1) newQty = 1;
    document.getElementById('ps-qty').value = newQty;
}

function handleVariationChange() {
    const select = document.getElementById('ps-variation');
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value !== "") {
        document.getElementById('ps-price-display').innerText = '$' + parseFloat(opt.dataset.price).toFixed(2);
        document.getElementById('ps-stock-display').innerText = opt.dataset.stock;
        document.getElementById('ps-retail').value = opt.dataset.price;
        // Wholesale remains parent for now unless we add var wholesale
    }
}

function confirmProductSelection() {
    let id = document.getElementById('ps-id').value;
    let name = document.getElementById('ps-name').value;
    let retailPrice = document.getElementById('ps-retail').value;
    let wholesalePrice = document.getElementById('ps-wholesale').value;
    let unit = document.getElementById('ps-unit').value;
    let qty = parseFloat(document.getElementById('ps-qty').value) || 1;
    
    let varSelect = document.getElementById('ps-variation');
    let variation_id = null;
    if (varSelect.options.length > 0 && varSelect.value !== "") {
        variation_id = varSelect.value;
        let opt = varSelect.options[varSelect.selectedIndex];
        name = name + " (" + opt.dataset.name + ")";
        id = id + "-" + variation_id; // temporary composite ID for the cart array
    }
    
    addToCart(id, name, retailPrice, wholesalePrice, unit, qty, variation_id);
    document.getElementById('productSelectionModal').style.display = 'none';
}

function closeProductSelectionModal() {
    document.getElementById('productSelectionModal').style.display = 'none';
}

function openQeModal(id) {
    let item = cart.find(i => i.id === id);
    if (!item) return;

    document.getElementById('qe-id').value = item.id;
    document.getElementById('qe-name-display').innerText = item.name;
    document.getElementById('qe-price-input').value = parseFloat(item.price).toFixed(2);
    document.getElementById('qe-unit-display').innerText = item.unit || 'pcs';
    
    const qtyInput = document.getElementById('qe-qty');
    qtyInput.value = item.quantity;
    
    qtyInput.onkeypress = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmQeSelection();
        }
    };
    
    document.getElementById('quantityEditModal').style.display = 'flex';
    setTimeout(() => {
        qtyInput.focus();
        qtyInput.select();
    }, 100);
}

function adjustQeQty(amount) {
    let current = parseFloat(document.getElementById('qe-qty').value) || 0;
    let newQty = current + amount;
    if (newQty < 0.1) newQty = 0.1;
    document.getElementById('qe-qty').value = newQty;
}

function confirmQeSelection() {
    let id = document.getElementById('qe-id').value;
    let qty = parseFloat(document.getElementById('qe-qty').value) || 1;
    let price = parseFloat(document.getElementById('qe-price-input').value) || 0;
    
    let item = cart.find(i => i.id == id);
    if (item) {
        item.quantity = qty;
        item.price = price;
        // Optionally update the DB price via an API call in background here if needed, but for now we just change it in cart.
    }
    
    renderCart();
    document.getElementById('quantityEditModal').style.display = 'none';
}

function closeQeModal() {
    document.getElementById('quantityEditModal').style.display = 'none';
}

function getSelectedPaymentMethod() {
    let sel = document.getElementById('paymentMethodSelect');
    if (sel) return sel.value;
    let checked = document.querySelector('input[name="payment_method"]:checked');
    return checked ? checked.value : 'cash';
}

function setSelectedPaymentMethod(val) {
    let sel = document.getElementById('paymentMethodSelect');
    if (sel) {
        sel.value = val;
        return;
    }
    let radio = document.querySelector('input[name="payment_method"][value="' + val + '"]');
    if (radio) radio.checked = true;
}

function handleCustomerChange() {
    let sel = document.getElementById('customerSelect');
    if(!sel) return;
    let opt = sel.options[sel.selectedIndex];
    let type = opt ? opt.getAttribute('data-type') : 'walk_in';
    let pts = opt ? parseInt(opt.getAttribute('data-points')) || 0 : 0;
    let bal = opt ? parseFloat(opt.getAttribute('data-balance')) || 0 : 0;
    
    currentPoints = pts;
    
    let pointsDisplay = document.getElementById('customer-points-display');
    let pointsVal = document.getElementById('customer-points-val');
    let balanceDisplay = document.getElementById('customer-balance-display');
    let balanceVal = document.getElementById('customer-balance-val');
    
    if (pointsDisplay && pointsVal) {
        if (sel.value !== '' && pts > 0) {
            pointsDisplay.style.display = 'block';
            pointsVal.innerText = pts;
        } else {
            pointsDisplay.style.display = 'none';
        }
    }

    if (balanceDisplay && balanceVal) {
        if (sel.value !== '') {
            balanceDisplay.style.display = 'block';
            balanceVal.innerText = bal < 0 ? '+$' + Math.abs(bal).toFixed(2) + ' (Advance)' : '-$' + bal.toFixed(2) + ' (Owe)';
            balanceDisplay.style.color = bal < 0 ? '#10b981' : '#ef4444';
            balanceDisplay.style.background = bal < 0 ? '#dcfce7' : '#fee2e2';
        } else {
            balanceDisplay.style.display = 'none';
        }
    }
    
    // If type is credit (B2B), use wholesale price, else use retail price
    cart.forEach(item => {
        if (type === 'credit') {
            item.price = item.wholesale_price;
        } else {
            item.price = item.retail_price;
        }
    });
    
    renderCart();
}

function changeQty(id, delta) {
    let index = cart.findIndex(i => i.id == id);
    if (index > -1) {
        let item = cart.splice(index, 1)[0];
        let newQty = item.quantity + delta;
        if (newQty > 0) {
            item.quantity = newQty;
            cart.unshift(item); // Move to top of cart
        }
        renderCart();
    }
}

function setQty(id, value) {
    let index = cart.findIndex(i => i.id == id);
    if (index > -1) {
        let item = cart.splice(index, 1)[0];
        let newQty = parseFloat(value);
        if (newQty > 0) {
            item.quantity = newQty;
            cart.unshift(item); // Move to top of cart
        }
        renderCart();
    }
}

function removeItem(id) {
    cart = cart.filter(i => i.id != id);
    renderCart();
}

function clearCart() {
    if(cart.length > 0 && confirm("Are you sure you want to clear the current sale?")) {
        cart = [];
        document.getElementById('cart-discount').value = 0;
        let usePts = document.getElementById('usePointsCheckbox');
        if(usePts) usePts.checked = false;
        renderCart();
    }
}
let modalFinalTotal = 0;

function getSelectedPaymentMethod() {
    let sel = document.getElementById('paymentMethodSelect');
    if (sel) return sel.value;
    let checkedMethod = document.querySelector('input[name="payment_method"]:checked');
    return checkedMethod ? checkedMethod.value : 'cash';
}

function setSelectedPaymentMethod(val) {
    let sel = document.getElementById('paymentMethodSelect');
    if (sel) {
        sel.value = val;
    }
    let checkedMethod = document.querySelector('input[name="payment_method"][value="' + val + '"]');
    if (checkedMethod) checkedMethod.checked = true;
}

function toggleCashInput() {
    let method = getSelectedPaymentMethod();
    
    const cashDiv = document.getElementById('cashInputDiv');
    const khataNotice = document.getElementById('khata-notice');
    const takenByDiv = document.getElementById('takenByDiv');

    if (khataNotice) {
        khataNotice.style.display = (method === 'credit') ? 'block' : 'none';
    }
    
    if (takenByDiv) {
        takenByDiv.style.display = (method === 'credit') ? 'block' : 'none';
    }

    if (cashDiv) {
        if (method === 'credit') {
            cashDiv.style.display = 'none';
        } else {
            cashDiv.style.display = 'block';
            let receivedInput = document.getElementById('modal-received');
            if (receivedInput && method !== 'cash') {
                receivedInput.value = modalFinalTotal.toFixed(2);
            }
        }
    }
    calculateChange();
}

function recalculateModal() {
    let ptsInput = document.getElementById('modal-points-to-use');
    let pointsToUse = ptsInput ? (parseInt(ptsInput.value) || 0) : 0;
    let availablePts = typeof currentPoints !== 'undefined' ? currentPoints : 0;
    
    if (pointsToUse < 0) pointsToUse = 0;
    if (pointsToUse > availablePts) pointsToUse = availablePts;
    
    // 10 points = $1.00
    let pointsDiscount = pointsToUse / 10;
    let baseTotal = typeof currentTotal !== 'undefined' ? currentTotal : 0;
    
    modalFinalTotal = Math.round((baseTotal - pointsDiscount) * 100) / 100;
    if (modalFinalTotal < 0) modalFinalTotal = 0;
    
    let totalEl = document.getElementById('modal-total');
    if (totalEl) totalEl.innerText = `$${modalFinalTotal.toFixed(2)}`;
    if (ptsInput) ptsInput.value = pointsToUse;
    
    calculateChange();
}

function checkout() {
    if (!cart || cart.length === 0) {
        showToast("System Notice", "Cart is empty! Add products to proceed.", "info");
        return;
    }
    
    // Populate Cart Items Preview
    const previewContainer = document.getElementById('checkout-cart-items');
    if (previewContainer) {
        let previewHtml = '';
        cart.forEach((item, index) => {
            let itemTotal = item.price * item.quantity;
            let borderStyle = (index < cart.length - 1) ? 'border-bottom: 1px solid #f1f5f9;' : '';
            previewHtml += '<div style="display: flex; justify-content: space-between; align-items: center; padding: 0.55rem 0; ' + borderStyle + '">';
            previewHtml += '<div style="font-weight: 700; color: #0f172a; font-size: 0.9rem;">' + item.name + '</div>';
            previewHtml += '<div style="display: flex; align-items: center; gap: 0.85rem; font-size: 0.88rem;">';
            previewHtml += '<span style="color: #64748b;">' + item.quantity + ' × $' + item.price.toFixed(2) + '</span>';
            previewHtml += '<span style="font-weight: 800; color: #2563eb; font-size: 0.95rem; min-width: 55px; text-align: right;">$' + itemTotal.toFixed(2) + '</span>';
            previewHtml += '</div>';
            previewHtml += '</div>';
        });
        previewContainer.innerHTML = previewHtml;
    }
    
    let pointsSection = document.getElementById('modal-points-section');
    let availablePtsEl = document.getElementById('modal-available-points');
    let ptsToUseEl = document.getElementById('modal-points-to-use');
    
    if (typeof currentPoints !== 'undefined' && currentPoints > 0) {
        if (pointsSection) pointsSection.style.display = 'flex';
        if (availablePtsEl) availablePtsEl.innerText = currentPoints;
        if (ptsToUseEl) {
            ptsToUseEl.value = 0;
            ptsToUseEl.max = currentPoints;
        }
    } else {
        if (pointsSection) pointsSection.style.display = 'none';
        if (ptsToUseEl) ptsToUseEl.value = 0;
    }
    
    modalFinalTotal = typeof currentTotal !== 'undefined' ? currentTotal : 0;
    recalculateModal();
    
    let receivedInput = document.getElementById('modal-received');
    if (receivedInput) receivedInput.value = modalFinalTotal.toFixed(2);
    
    // Auto-select Khata for any registered customer (other than Walk-in)
    let sel = document.getElementById('customerSelect');
    let khataOption = document.getElementById('khata-option');
    let opt = (sel && sel.selectedIndex >= 0) ? sel.options[sel.selectedIndex] : null;
    let hasCustomer = (sel && sel.value !== '');
    
    if (hasCustomer) {
        if (khataOption) khataOption.style.display = 'block';
        setSelectedPaymentMethod('credit');
    } else {
        if (khataOption) khataOption.style.display = 'none';
        setSelectedPaymentMethod('cash');
    }
    
    // Auto-fill customer name & address inputs in modal if available
    let custNameInput = document.getElementById('modal-customer-name');
    if (custNameInput && opt && opt.value !== '') {
        let fullText = opt.text || '';
        let cleanName = fullText.split('(')[0].trim();
        custNameInput.value = cleanName;
    }
    
    toggleCashInput();
    
    let modalEl = document.getElementById('checkoutModal');
    if (modalEl) modalEl.style.display = 'flex';
    if (receivedInput && getSelectedPaymentMethod() === 'cash') {
        receivedInput.focus();
        receivedInput.select();
    }
    calculateChange();
}

function openCheckoutModal() {
    checkout();
}

function calculateChange() {
    let receivedInput = document.getElementById('modal-received');
    const received = receivedInput ? (parseFloat(receivedInput.value) || 0) : 0;
    const change = received - modalFinalTotal;
    const changeEl = document.getElementById('modal-change');
    const confirmBtn = document.getElementById('confirmBtn');
    
    if (changeEl) {
        if (change >= 0) {
            changeEl.innerText = `$${change.toFixed(2)}`;
            changeEl.style.color = 'var(--success)';
            if (confirmBtn) confirmBtn.disabled = false;
        } else {
            changeEl.innerText = "Insufficient Amount";
            changeEl.style.color = 'var(--danger)';
            if (confirmBtn) confirmBtn.disabled = true;
        }
    }
}

function closeModal() {
    let modalEl = document.getElementById('checkoutModal');
    if (modalEl) modalEl.style.display = 'none';
}

function confirmCheckout() {
    try {
        if (!cart || cart.length === 0) {
            showToast("System Notice", "Cart is empty!", "info");
            return;
        }
        
        let method = getSelectedPaymentMethod();
        
        let modalReceivedElem = document.getElementById('modal-received');
        let received = modalReceivedElem ? (parseFloat(modalReceivedElem.value) || modalFinalTotal) : modalFinalTotal;
        
        let customerSelectElem = document.getElementById('customerSelect');
        let customerId = customerSelectElem ? customerSelectElem.value : '';
        
        let custNameElem = document.getElementById('modal-customer-name');
        let customerName = custNameElem ? custNameElem.value : '';
        
        let custAddrElem = document.getElementById('modal-customer-address');
        let customerAddress = custAddrElem ? custAddrElem.value : '';
        
        let takenByInput = document.getElementById('modal-taken-by');
        let takenBy = (method === 'credit' && takenByInput) ? takenByInput.value.trim() : '';
        
        let cartDiscountElem = document.getElementById('cart-discount');
        let totalDiscount = cartDiscountElem ? (parseFloat(cartDiscountElem.value) || 0) : 0;
        
        let pointsUsed = 0;
        let ptsInput = document.getElementById('modal-points-to-use');
        if (ptsInput) {
            pointsUsed = parseInt(ptsInput.value) || 0;
        }

        if (method === 'cash' && received < modalFinalTotal) {
            showToast("System Notice", "Amount received cannot be less than total for cash payments.", "info");
            return;
        }

        let confirmBtn = document.getElementById('confirmBtn');
        if (confirmBtn) {
            confirmBtn.innerText = "Processing...";
            confirmBtn.disabled = true;
        }

        fetch('checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                cart: cart, 
                discount: totalDiscount, 
                points_used: pointsUsed,
                customer_id: customerId,
                customer_name: customerName,
                customer_address: customerAddress,
                taken_by: takenBy,
                payment_method: method,
                amount_received: received,
                change_returned: (received - modalFinalTotal) > 0 ? (received - modalFinalTotal) : 0
            })
        })
        .then(res => res.json())
        .then(data => {
            if (confirmBtn) {
                confirmBtn.innerText = "Confirm Sale";
                confirmBtn.disabled = false;
            }
            
            if (data.success) {
                closeModal();
                clearCart(true);
                if (typeof fetchCustomers === 'function') fetchCustomers(); 
                
                let popup = window.open('receipt.php?id=' + data.sale_id, '_blank');
                if (!popup || popup.closed || typeof popup.closed === 'undefined') {
                    window.location.href = 'receipt.php?id=' + data.sale_id;
                }
            } else {
                showToast("Error", data.message || "Failed to process sale.", "error");
            }
        })
        .catch(err => {
            console.error(err);
            showToast("Error", "A network error occurred.", "error");
            if (confirmBtn) {
                confirmBtn.innerText = "Confirm Sale";
                confirmBtn.disabled = false;
            }
        });
    } catch (err) {
        console.error("Checkout Exception:", err);
        showToast("Error", "Checkout error: " + err.message, "error");
        let confirmBtn = document.getElementById('confirmBtn');
        if (confirmBtn) {
            confirmBtn.innerText = "Confirm Sale";
            confirmBtn.disabled = false;
        }
    }
}


function generateQuotation() {
    if (cart.length === 0) {
        showToast("System Notice", "Cart is empty! Add products to generate a quotation.", "info");
        return;
    }
    let discount = parseFloat(document.getElementById('cart-discount').value) || 0;
    let customerId = document.getElementById('customerSelect').value;
    let walkinName = "";
    if(!customerId) {
        walkinName = prompt("Enter customer name for the Quotation:");
        if(!walkinName) return; 
    }
    
    fetch('api_quotations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cart: cart,
            discount: discount,
            customer_id: customerId,
            customer_name: walkinName, quote_id: window.loadedQuoteId || null
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.open('quotation_print.php?id=' + data.quotation_id, '_blank');
        } else {
            showToast("System Notice", "Error: " + data.message, "info");
        }
    })
    .catch(err => showToast("System Notice", "Error generating quotation", "info"));
}

function holdSalePrompt() {
    if (cart.length === 0) {
        showToast("System Notice", "Cart is empty!", "info");
        return;
    }
    document.getElementById('hold-ref').value = '';
    document.getElementById('hold-walkin').value = '';
    document.getElementById('hold-takenby').value = '';
    document.getElementById('holdSaleModal').style.display = 'flex';
}

function submitHoldSale() {
    let ref = document.getElementById('hold-ref').value.trim();
    if (!ref) {
        showToast("System Notice", "Reference note is required.", "info");
        return;
    }
    
    let walkin = document.getElementById('hold-walkin').value.trim();
    let takenby = document.getElementById('hold-takenby').value.trim();
    let discount = parseFloat(document.getElementById('cart-discount').value) || 0;
    let customerId = document.getElementById('customerSelect').value;
    
    fetch('api_held_sales.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'hold', 
            cart: cart, 
            reference_note: ref, 
            walkin_name: walkin, 
            taken_by: takenby, 
            discount: discount, 
            customer_id: customerId 
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            showToast("System Notice", "Sale held successfully.", "info");
            cart = [];
            document.getElementById('cart-discount').value = 0;
            document.getElementById('customerSelect').value = '';
            renderCart();
            document.getElementById('holdSaleModal').style.display = 'none';
        } else {
            showToast("System Notice", "Error: " + data.message, "info");
        }
    });
}

let heldSalesData = [];

function openHeldSales() {
    document.getElementById('heldSalesModal').style.display = 'flex';
    document.getElementById('heldSalesContainer').innerHTML = '<p style="text-align:center;">Loading...</p>';
    
    fetch('api_held_sales.php')
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            heldSalesData = data.held_sales;
            let html = '';
            if(heldSalesData.length === 0) {
                html = '<p style="text-align:center; color:var(--text-muted);">No held sales found.</p>';
            } else {
                heldSalesData.forEach(hs => {
                    let total = hs.items.reduce((sum, item) => sum + (item.price * item.quantity), 0) - hs.discount;
                    html += `
                        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="margin:0 0 0.25rem 0; color:var(--primary-color);">${hs.reference_note}</h4>
                                <div style="font-size:0.8rem; color:var(--text-muted);">
                                    Date: ${hs.created_at} | Items: ${hs.items.length} | Total: $${total.toFixed(2)}<br>
                                    ${hs.customer_name ? 'Customer: ' + hs.customer_name : (hs.walkin_name ? 'Walk-in: ' + hs.walkin_name : '')}
                                    ${hs.taken_by ? ' | Taken By: ' + hs.taken_by : ''}
                                </div>
                            </div>
                            <div style="display:flex; gap: 0.5rem;">
                                <button class="btn btn-primary" onclick="restoreHeldSale(${hs.id})">Restore</button>
                                <button class="btn" style="background:var(--danger); color:white;" onclick="deleteHeldSale(${hs.id})">Delete</button>
                            </div>
                        </div>
                    `;
                });
            }
            document.getElementById('heldSalesContainer').innerHTML = html;
        }
    });
}

function restoreHeldSale(id) {
    let hs = heldSalesData.find(x => x.id == id);
    if(hs) {
        if(cart.length > 0) {
            if(!confirm("Current cart will be cleared to restore this sale. Continue?")) return;
        }
        cart = hs.items.map(i => ({ id: i.id, name: i.name, price: i.price, quantity: i.quantity, unit: i.unit }));
        document.getElementById('cart-discount').value = hs.discount;
        if(hs.customer_id) document.getElementById('customerSelect').value = hs.customer_id;
        renderCart();
        deleteHeldSale(id, true); 
        document.getElementById('heldSalesModal').style.display = 'none';
    }
}

function deleteHeldSale(id, silent = false) {
    if(!silent && !confirm("Are you sure you want to delete this held sale?")) return;
    
    fetch('api_held_sales.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: id })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success && !silent) {
            openHeldSales(); 
        }
    });
}



function applyPromo() {
    let code = document.getElementById("modal-promo-code").value.trim();
    let msg = document.getElementById("promo-message");
    if(!code) return;
    
    msg.style.display = "block";
    msg.style.color = "#475569";
    msg.innerText = "Checking...";
    
    // We base it on current subtotal minus manual discount + tax
    let totalWithoutPromo = calculateSubtotal() - (parseFloat(document.getElementById("cart-discount").value) || 0) + calculateTaxAmount();
    
    fetch("validate_promo.php?code=" + code + "&total=" + totalWithoutPromo)
    .then(res => res.json())
    .then(data => {
        if(data.status == "success") {
            let discountVal = 0;
            if(data.type == "percentage") {
                discountVal = totalWithoutPromo * (parseFloat(data.value)/100);
            } else {
                discountVal = parseFloat(data.value);
            }
            
            document.getElementById("applied-promo-discount").value = discountVal;
            msg.style.color = "#166534";
            msg.innerText = "Promo applied! -$" + discountVal.toFixed(2);
            recalculateModal();
        } else {
            document.getElementById("applied-promo-discount").value = 0;
            msg.style.color = "#dc2626";
            msg.innerText = data.message;
            recalculateModal();
        }
    }).catch(err => {
        msg.style.color = "#dc2626";
        msg.innerText = "Network error";
    });
}



function saveQuote() {
    if (cart.length === 0) {
        showToast("System Notice", "Cart is empty! Add products to save as quote.", "info");
        return;
    }
    
    let customerId = document.getElementById("customerSelect") ? document.getElementById("customerSelect").value : "";
    let customerName = document.getElementById("walkinCustomer") ? document.getElementById("walkinCustomer").value : "";
    let manualDiscount = parseFloat(document.getElementById("cart-discount") ? document.getElementById("cart-discount").value : 0) || 0;
    
    let payload = {
        cart: cart,
        customer_id: customerId,
        customer_name: customerName,
        discount: manualDiscount
    };
    
    fetch("save_quote.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            showToast("Success", res.message, "success");
            clearCart(true);
        } else {
            showToast("Error", res.message || "Failed to save quote", "danger");
        }
    })
    .catch(e => {
        showToast("Error", "Network error saving quote", "danger");
    });
}




function renderCart(fullRender = true) {
    const cartEl = document.getElementById("cart-items");
    if (!cartEl) return;
    
    let subtotal = 0;

    if (fullRender) {
        cartEl.innerHTML = "";
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            
            const div = document.createElement("div");
            div.className = "cart-item";
            div.style.cssText = "display:flex; justify-content:space-between; align-items:center; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);";
            
            let taxBadge = item.tax_rate > 0 ? `<span style="font-size:0.7rem; color:#94a3b8;">(+${item.tax_rate}% tax)</span>` : "";
            
            div.innerHTML = `
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.15rem;">
                        <h4 style="margin:0; font-size: 0.95rem; color: #0f172a; font-weight: 600; cursor: pointer;" onclick="openQeModal('${item.id}')" title="Click to edit">${item.name}</h4>
                        <button onclick="removeItem('${item.id}')" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:1rem; padding: 0 0.2rem;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">✕</button>
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem;">${PRI_CURR}${item.price.toFixed(2)} ${taxBadge}</div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display:flex; align-items:center; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; background: #ffffff;">
                            <button onclick="changeQty('${item.id}', -1)" style="width:32px; height:30px; border:none; background:#f8fafc; cursor:pointer; font-weight: 600; font-size: 1rem; color: #475569; display: flex; align-items: center; justify-content: center;">−</button>
                            <input type="number" step="0.01" min="0.01" value="${item.quantity}" onchange="setQty('${item.id}', this.value)" style="width: 44px; height: 30px; text-align: center; border: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; font-weight: 600; font-size: 0.9rem; color: #0f172a; outline: none; padding: 0; appearance: none;">
                            <button onclick="changeQty('${item.id}', 1)" style="width:32px; height:30px; border:none; background:#f8fafc; cursor:pointer; font-weight: 600; font-size: 1rem; color: #475569; display: flex; align-items: center; justify-content: center;">+</button>
                        </div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 1rem;">
                            ${PRI_CURR}${itemTotal.toFixed(2)}
                        </div>
                    </div>
                </div>
            `;
            cartEl.appendChild(div);
        });
        
        if (cart.length === 0) {
            cartEl.innerHTML = '<p style="text-align:center; padding: 2rem; color: var(--text-muted); font-size: 1.2rem;">🛒<br>No items in your order<br><span style="font-size:0.9rem;">Scan a barcode or select a product</span></p>';
        }
    } else {
        cart.forEach(item => {
            subtotal += (item.price * item.quantity);
        });
    }

    let discount = parseFloat(document.getElementById("cart-discount").value) || 0;
    
    let totalDiscount = discount;
    if(totalDiscount < 0) totalDiscount = 0;
    if(totalDiscount > subtotal) totalDiscount = subtotal;
    
    let tax = 0; 
    cart.forEach(item => {
        let itemDiscRatio = subtotal > 0 ? ((item.price * item.quantity) / subtotal) : 0;
        let itemDiscount = totalDiscount * itemDiscRatio;
        let itemTotalAfterDisc = (item.price * item.quantity) - itemDiscount;
        if (itemTotalAfterDisc > 0) {
            tax += itemTotalAfterDisc * ((item.tax_rate || 0) / 100);
        }
    });

    let total = subtotal - totalDiscount + tax;
    currentTotal = Math.round(total * 100) / 100;

    document.getElementById("cart-subtotal").innerText = PRI_CURR + subtotal.toFixed(2);
    document.getElementById("cart-tax").innerText = PRI_CURR + tax.toFixed(2);
    document.getElementById("cart-discount").title = "";
    document.getElementById("cart-discount").style.borderColor = "#ccc";

    document.getElementById("cart-total").innerText = PRI_CURR + total.toFixed(2);
    document.getElementById("checkout-total-btn").innerText = PRI_CURR + total.toFixed(2);
    
    let secTotalEl = document.getElementById("cart-sec-total");
    if (secTotalEl && SEC_CURR) {
        let secTotal = total * EXCH_RATE;
        secTotalEl.innerText = "(" + SEC_CURR + " " + secTotal.toFixed(2) + ")";
    }
}

function calculateSubtotal() {
    let sub = 0;
    cart.forEach(i => sub += (i.price * i.quantity));
    return sub;
}

function calculateTaxAmount() {
    let sub = calculateSubtotal();
    let totalDiscount = parseFloat(document.getElementById("cart-discount").value) || 0;
    if(totalDiscount > sub) totalDiscount = sub;
    let tax = 0;
    cart.forEach(item => {
        let itemDiscRatio = sub > 0 ? ((item.price * item.quantity) / sub) : 0;
        let itemDiscount = totalDiscount * itemDiscRatio;
        let itemTotalAfterDisc = (item.price * item.quantity) - itemDiscount;
        if (itemTotalAfterDisc > 0) {
            tax += itemTotalAfterDisc * ((item.tax_rate || 0) / 100);
        }
    });
    return tax;
}
