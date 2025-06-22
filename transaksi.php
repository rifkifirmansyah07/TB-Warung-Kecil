<?php
session_start();
require_once 'config/database.php';
require_once 'includes/layout.php';

startLayoutNoTopbar('Transaksi Penjualan', 'transaksi.php');

try {
    $conn = getConnection();
    
    // Ambil data produk untuk search
    $stmt = $conn->query("
        SELECT 
            p.id_produk,
            p.nama_produk,
            p.harga_jual,
            p.stok,
            p.satuan,
            p.barcode,
            COALESCE(k.nama_kategori, 'Tanpa Kategori') as nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
        WHERE p.status = 'aktif' AND p.stok > 0
        ORDER BY p.nama_produk ASC
    ");
    $products = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = 'Terjadi kesalahan dalam memuat data produk.';
}
?>

<!-- Custom CSS untuk POS -->
<style>
.pos-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 20px;
    height: calc(100vh - 100px);
    max-height: 800px;
}

.product-section {
    display: flex;
    flex-direction: column;
}

.search-bar {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border: 1px solid var(--border-color);
}

.search-input {
    position: relative;
}

.search-input input {
    width: 100%;
    padding: 15px 50px 15px 20px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 16px;
    background: #f8f9fa;
}

.search-input i {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    font-size: 18px;
}

.product-grid {
    flex: 1;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.product-grid-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: 15px 20px;
    font-weight: 600;
}

.product-items {
    padding: 20px;
    max-height: 500px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.product-item {
    background: #f8f9fa;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.product-item:hover {
    border-color: var(--primary-color);
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(198, 40, 40, 0.15);
}

.product-item.out-of-stock {
    opacity: 0.5;
    cursor: not-allowed;
}

.product-name {
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 8px;
    font-size: 14px;
}

.product-price {
    color: var(--primary-color);
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 5px;
}

.product-stock {
    color: var(--text-light);
    font-size: 12px;
}

.cart-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    max-height: 100%;
}

.cart-header {
    background: linear-gradient(135deg, var(--success-color), #66bb6a);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
    border-radius: 8px 8px 0 0;
}

.cart-items {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    max-height: 300px;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.cart-item:last-child {
    border-bottom: none;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: var(--text-color);
    font-size: 14px;
    margin-bottom: 4px;
}

.item-price {
    color: var(--text-light);
    font-size: 12px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 8px 0;
}

.qty-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    background: var(--primary-dark);
}

.qty-input {
    width: 50px;
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 4px;
    font-size: 12px;
}

.item-total {
    font-weight: bold;
    color: var(--primary-color);
    font-size: 14px;
}

.remove-item {
    background: var(--danger-color);
    color: white;
    border: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 10px;
    margin-left: 8px;
}

.cart-summary {
    padding: 20px;
    border-top: 2px solid var(--border-color);
    background: #f8f9fa;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    font-size: 18px;
    font-weight: bold;
    color: var(--primary-color);
    padding-top: 10px;
    border-top: 1px solid var(--border-color);
    margin-top: 10px;
}

.checkout-btn {
    width: 100%;
    background: var(--success-color);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 15px;
    transition: all 0.2s ease;
}

.checkout-btn:hover {
    background: #66bb6a;
    transform: translateY(-1px);
}

.checkout-btn:disabled {
    background: var(--text-light);
    cursor: not-allowed;
    transform: none;
}

.empty-cart {
    text-align: center;
    color: var(--text-light);
    padding: 40px 20px;
}

.empty-cart i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 1024px) {
    .pos-container {
        grid-template-columns: 1fr;
        gap: 15px;
        height: auto;
    }
    
    .cart-section {
        order: -1;
    }
    
    .product-items {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        max-height: 400px;
    }
}

@media (max-width: 768px) {
    .product-items {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }
    
    .pos-container {
        gap: 10px;
    }
}
</style>

<div class="pos-container">
    <!-- Section Produk -->
    <div class="product-section">
        <!-- Search Bar -->
        <div class="search-bar">
            <div class="search-input">
                <input type="text" id="product-search" placeholder="Cari produk atau scan barcode..." autocomplete="off">
                <i class="fas fa-search"></i>
            </div>
        </div>
        
        <!-- Grid Produk -->
        <div class="product-grid">
            <div class="product-grid-header">
                <i class="fas fa-box"></i> Pilih Produk (<span id="product-count"><?php echo count($products); ?></span> tersedia)
            </div>
            <div class="product-items" id="product-items">
                <?php foreach ($products as $product): ?>
                <div class="product-item" 
                     data-id="<?php echo $product['id_produk']; ?>"
                     data-name="<?php echo htmlspecialchars($product['nama_produk']); ?>"
                     data-price="<?php echo $product['harga_jual']; ?>"
                     data-stock="<?php echo $product['stok']; ?>"
                     data-barcode="<?php echo htmlspecialchars($product['barcode']); ?>"
                     data-category="<?php echo htmlspecialchars($product['nama_kategori']); ?>">
                    <div class="product-name"><?php echo htmlspecialchars($product['nama_produk']); ?></div>
                    <div class="product-price">Rp <?php echo number_format($product['harga_jual']); ?></div>
                    <div class="product-stock">Stok: <?php echo number_format($product['stok']); ?> <?php echo htmlspecialchars($product['satuan']); ?></div>
                    <?php if ($product['barcode']): ?>
                    <div style="font-size: 10px; color: var(--text-light); margin-top: 4px;">
                        <?php echo htmlspecialchars($product['barcode']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Section Keranjang -->
    <div class="cart-section">
        <div class="cart-header">
            <i class="fas fa-shopping-cart"></i> Keranjang Belanja (<span id="cart-count">0</span> item)
        </div>
        
        <div class="cart-items" id="cart-items">
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Keranjang masih kosong</p>
                <small>Pilih produk untuk memulai transaksi</small>
            </div>
        </div>
        
        <div class="cart-summary" id="cart-summary" style="display: none;">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">Rp 0</span>
            </div>
            <div class="summary-row">
                <span>Pajak (0%):</span>
                <span id="tax">Rp 0</span>
            </div>
            <div class="summary-total">
                <span>Total:</span>
                <span id="total">Rp 0</span>
            </div>
            
            <button class="checkout-btn" id="checkout-btn" disabled>
                <i class="fas fa-credit-card"></i> Proses Pembayaran
            </button>
            
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button class="btn btn-warning" style="flex: 1;" id="hold-transaction">
                    <i class="fas fa-pause"></i> Hold
                </button>
                <button class="btn btn-danger" style="flex: 1;" id="clear-cart">
                    <i class="fas fa-trash"></i> Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Checkout -->
<div id="checkout-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; width: 90%; max-width: 500px; max-height: 90%; overflow-y: auto;">
        <div style="background: var(--success-color); color: white; padding: 20px; border-radius: 8px 8px 0 0;">
            <h3 style="margin: 0;"><i class="fas fa-credit-card"></i> Proses Pembayaran</h3>
        </div>
        
        <div style="padding: 20px;">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span>Total Belanja:</span>
                    <strong id="modal-total" style="color: var(--primary-color); font-size: 18px;">Rp 0</strong>
                </div>
            </div>
              <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Jumlah Bayar:</label>
                <input type="number" id="cash-amount" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px;" placeholder="0" min="0">
                <div id="change-amount" style="margin-top: 10px; padding: 10px; background: #d4edda; border-radius: 4px; display: none;">
                    <strong>Kembalian: <span id="change-value">Rp 0</span></strong>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Catatan (Opsional):</label>
                <textarea id="transaction-notes" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; resize: vertical;" rows="3" placeholder="Catatan transaksi..."></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-secondary" style="flex: 1;" onclick="closeCheckoutModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button class="btn" style="flex: 1;" id="confirm-payment" disabled>
                    <i class="fas fa-check"></i> Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Cart state
let cart = [];
let cartTotal = 0;

// DOM elements
const productItems = document.getElementById('product-items');
const cartItems = document.getElementById('cart-items');
const cartSummary = document.getElementById('cart-summary');
const checkoutBtn = document.getElementById('checkout-btn');
const productSearch = document.getElementById('product-search');

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    updateCartDisplay();
});

function initializeEventListeners() {
    // Product click handlers
    productItems.addEventListener('click', function(e) {
        const productItem = e.target.closest('.product-item');
        if (productItem && !productItem.classList.contains('out-of-stock')) {
            addToCart(productItem);
        }
    });
    
    // Search functionality
    productSearch.addEventListener('input', function() {
        filterProducts(this.value);
    });
    
    // Checkout button
    checkoutBtn.addEventListener('click', openCheckoutModal);
    
    // Clear cart
    document.getElementById('clear-cart').addEventListener('click', function() {
        if (confirm('Yakin ingin mengosongkan keranjang?')) {
            clearCart();
        }
    });
      // Payment modal handlers
    document.getElementById('cash-amount').addEventListener('input', calculateChange);
    document.getElementById('confirm-payment').addEventListener('click', processPayment);
}

function addToCart(productElement) {
    const productData = {
        id: productElement.dataset.id,
        name: productElement.dataset.name,
        price: parseFloat(productElement.dataset.price),
        stock: parseInt(productElement.dataset.stock),
        barcode: productElement.dataset.barcode
    };
    
    // Check if product already in cart
    const existingItem = cart.find(item => item.id === productData.id);
    
    if (existingItem) {
        if (existingItem.quantity < productData.stock) {
            existingItem.quantity++;
            existingItem.total = existingItem.quantity * existingItem.price;
        } else {
            alert('Stok tidak mencukupi!');
            return;
        }
    } else {
        cart.push({
            ...productData,
            quantity: 1,
            total: productData.price
        });
    }
    
    updateCartDisplay();
    
    // Visual feedback
    productElement.style.transform = 'scale(0.95)';
    setTimeout(() => {
        productElement.style.transform = '';
    }, 150);
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
}

function updateQuantity(productId, newQuantity) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        if (newQuantity <= 0) {
            removeFromCart(productId);
        } else if (newQuantity <= item.stock) {
            item.quantity = newQuantity;
            item.total = item.quantity * item.price;
            updateCartDisplay();
        } else {
            alert('Stok tidak mencukupi!');
        }
    }
}

function updateCartDisplay() {
    const cartCount = document.getElementById('cart-count');
    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total');
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Keranjang masih kosong</p>
                <small>Pilih produk untuk memulai transaksi</small>
            </div>
        `;
        cartSummary.style.display = 'none';
        checkoutBtn.disabled = true;
    } else {
        let html = '';
        cartTotal = 0;
        
        cart.forEach(item => {
            cartTotal += item.total;
            html += `
                <div class="cart-item">
                    <div class="item-info">
                        <div class="item-name">${item.name}</div>
                        <div class="item-price">Rp ${item.price.toLocaleString()}</div>
                        <div class="quantity-controls">
                            <button class="qty-btn" onclick="updateQuantity('${item.id}', ${item.quantity - 1})">-</button>
                            <input type="number" class="qty-input" value="${item.quantity}" 
                                   onchange="updateQuantity('${item.id}', parseInt(this.value))" min="1" max="${item.stock}">
                            <button class="qty-btn" onclick="updateQuantity('${item.id}', ${item.quantity + 1})">+</button>
                            <button class="remove-item" onclick="removeFromCart('${item.id}')">×</button>
                        </div>
                    </div>
                    <div class="item-total">Rp ${item.total.toLocaleString()}</div>
                </div>
            `;
        });
        
        cartItems.innerHTML = html;
        cartSummary.style.display = 'block';
        subtotalEl.textContent = `Rp ${cartTotal.toLocaleString()}`;
        totalEl.textContent = `Rp ${cartTotal.toLocaleString()}`;
        checkoutBtn.disabled = false;
    }
    
    cartCount.textContent = cart.length;
}

function filterProducts(searchTerm) {
    const products = productItems.querySelectorAll('.product-item');
    let visibleCount = 0;
    
    products.forEach(product => {
        const name = product.dataset.name.toLowerCase();
        const barcode = product.dataset.barcode.toLowerCase();
        const category = product.dataset.category.toLowerCase();
        const search = searchTerm.toLowerCase();
        
        if (name.includes(search) || barcode.includes(search) || category.includes(search)) {
            product.style.display = 'block';
            visibleCount++;
        } else {
            product.style.display = 'none';
        }
    });
    
    document.getElementById('product-count').textContent = visibleCount;
}

function clearCart() {
    cart = [];
    updateCartDisplay();
}

function openCheckoutModal() {
    document.getElementById('modal-total').textContent = `Rp ${cartTotal.toLocaleString()}`;
    document.getElementById('checkout-modal').style.display = 'flex';
    document.getElementById('cash-amount').value = cartTotal;
    calculateChange();
}

function closeCheckoutModal() {
    document.getElementById('checkout-modal').style.display = 'none';
}

function calculateChange() {
    const cashAmount = parseFloat(document.getElementById('cash-amount').value) || 0;
    const changeDiv = document.getElementById('change-amount');
    const changeValue = document.getElementById('change-value');
    const confirmBtn = document.getElementById('confirm-payment');
    
    if (cashAmount >= cartTotal) {
        const change = cashAmount - cartTotal;
        changeValue.textContent = `Rp ${change.toLocaleString()}`;
        changeDiv.style.display = 'block';
        confirmBtn.disabled = false;
    } else {
        changeDiv.style.display = 'none';
        confirmBtn.disabled = true;
    }
}

function processPayment() {
    const cashAmount = parseFloat(document.getElementById('cash-amount').value) || 0;
    const notes = document.getElementById('transaction-notes').value;
    
    // Prepare transaction data
    const transactionData = {
        items: cart,
        total: cartTotal,
        cash_amount: cashAmount,
        change: cashAmount - cartTotal,
        notes: notes
    };
    
    // Send to server
    fetch('process-transaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(transactionData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Transaksi berhasil diproses!');
            clearCart();
            closeCheckoutModal();
            
            // Optionally redirect to receipt or transaction list
            if (confirm('Cetak struk?')) {
                window.open(`cetak-struk.php?id=${data.transaction_id}`, '_blank');
            }
        } else {
            alert('Terjadi kesalahan: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan dalam memproses transaksi');
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // F1 - Focus search
    if (e.key === 'F1') {
        e.preventDefault();
        productSearch.focus();
    }
    
    // F2 - Clear cart
    if (e.key === 'F2') {
        e.preventDefault();
        if (cart.length > 0 && confirm('Yakin ingin mengosongkan keranjang?')) {
            clearCart();
        }
    }
    
    // F3 - Checkout
    if (e.key === 'F3') {
        e.preventDefault();
        if (cart.length > 0) {
            openCheckoutModal();
        }
    }
    
    // Escape - Close modal
    if (e.key === 'Escape') {
        closeCheckoutModal();
    }
});

// Auto-focus search on load
productSearch.focus();
</script>

<?php endLayout(); ?>
