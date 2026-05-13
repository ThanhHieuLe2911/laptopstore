/**
 * LaptopStore - Main JavaScript File
 * Handles cart, wishlist, and UI interactions
 */

// ========================================
// Utility Functions
// ========================================

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    const icon = toast.querySelector('i');
    
    toastMsg.textContent = message;
    
    // Change icon based on type
    icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    icon.style.color = type === 'success' ? 'var(--success)' : 'var(--danger)';
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// ========================================
// Cart Management
// ========================================

function getCart() {
    const cart = localStorage.getItem('laptopStoreCart');
    return cart ? JSON.parse(cart) : [];
}

function saveCart(cart) {
    localStorage.setItem('laptopStoreCart', JSON.stringify(cart));
    updateCartUI();

    // ✅ NEW: nếu đang ở trang cart.php và có renderCartPage thì render lại UI bảng giỏ hàng
    if (typeof renderCartPage === 'function') {
        renderCartPage();
    }
}

function clearCart() {
    localStorage.removeItem('laptopStoreCart');
    updateCartUI();

    // ✅ NEW: cập nhật lại trang giỏ hàng nếu đang mở
    if (typeof renderCartPage === 'function') {
        renderCartPage();
    }
}

function addToCart(productId, showToastMsg = true) {
    if (!window.PRODUCTS) {
        console.error('Products data not loaded');
        return;
    }
    
    const product = window.PRODUCTS.find(p => p.id == productId);
    if (!product) {
        showToast('Sản phẩm không tồn tại', 'error');
        return;
    }
    
    let cart = getCart();
    const existingIndex = cart.findIndex(item => item.id == productId);
    
    if (existingIndex > -1) {
        cart[existingIndex].quantity += 1;
        if (showToastMsg) {
            showToast(`Đã tăng số lượng "${product.name}"`);
        }
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            brand: product.brand,
            price: product.price,
            image: product.image,
            quantity: 1
        });
        if (showToastMsg) {
            showToast(`Đã thêm "${product.name}" vào giỏ`);
        }
    }
    
    saveCart(cart);
    
    // Auto open cart sidebar (giữ nguyên)
    const cartWrapper = document.getElementById('cartWrapper');
    if (cartWrapper) cartWrapper.classList.add('cart-active');
}

function changeQuantity(index, delta) {
    let cart = getCart();

    if (!cart[index]) return;

    cart[index].quantity += delta;
    
    if (cart[index].quantity <= 0) {
        if (confirm('Bạn có muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            cart.splice(index, 1);
        } else {
            cart[index].quantity = 1;
        }
    }
    
    // ✅ saveCart đã tự gọi updateCartUI + renderCartPage (nếu có)
    saveCart(cart);
}

function removeFromCart(index) {
    if (confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        let cart = getCart();
        cart.splice(index, 1);
        saveCart(cart);
        showToast('Đã xóa sản phẩm khỏi giỏ hàng');
        
        // Re-render cart page if on cart page (giữ lại, không hại)
        if (typeof renderCartPage === 'function') {
            renderCartPage();
        }
    }
}

function updateCartUI() {
    const cart = getCart();
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    // Update badge
    const badges = document.querySelectorAll('#cartCount, #cartItemCountHeader');
    badges.forEach(badge => {
        badge.textContent = totalItems;
        // Animation
        badge.style.transform = 'scale(1.3)';
        setTimeout(() => {
            badge.style.transform = 'scale(1)';
        }, 200);
    });
    
    // Update total
    const totalLabel = document.getElementById('cartTotal');
    if (totalLabel) {
        totalLabel.textContent = formatMoney(totalPrice);
    }
    
    // Update cart items
    const container = document.getElementById('cartItemsContainer');
    if (!container) return;
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <p>Giỏ hàng đang trống</p>
            </div>
        `;
    } else {
        container.innerHTML = '';
        cart.forEach((item, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'cart-item';
            itemDiv.innerHTML = `
                <img src="${item.image}" alt="${item.name}">
                <div class="cart-item-details">
                    <div class="cart-item-title">${item.name}</div>
                    <div style="color: var(--danger); font-weight: bold; font-size: 14px; margin: 5px 0;">
                        ${formatMoney(item.price)}
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="changeQuantity(${index}, -1)">-</button>
                        <span style="font-weight: 600; font-size: 14px; width: 20px; text-align: center;">
                            ${item.quantity}
                        </span>
                        <button class="qty-btn" onclick="changeQuantity(${index}, 1)">+</button>
                        <button onclick="removeFromCart(${index})" 
                                style="margin-left: auto; background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 14px;"
                                title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(itemDiv);
        });
    }
}

function toggleCart() {
    document.getElementById('cartWrapper').classList.toggle('cart-active');
}

// ========================================
// Wishlist Management
// ========================================

function getWishlist() {
    const wishlist = localStorage.getItem('laptopStoreWishlist');
    return wishlist ? JSON.parse(wishlist) : [];
}

function saveWishlist(wishlist) {
    localStorage.setItem('laptopStoreWishlist', JSON.stringify(wishlist));
    updateWishlistUI();
}

function toggleWishlist(btn, productId) {
    const icon = btn.querySelector('i');
    let wishlist = getWishlist();
    
    const index = wishlist.indexOf(productId);
    
    if (index > -1) {
        wishlist.splice(index, 1);
        icon.classList.remove('fas');
        icon.classList.add('far');
        icon.style.color = 'var(--dark)';
        showToast('Đã xóa khỏi yêu thích');
    } else {
        wishlist.push(productId);
        icon.classList.remove('far');
        icon.classList.add('fas');
        icon.style.color = '#ef4444';
        showToast('Đã thêm vào yêu thích');
    }
    
    saveWishlist(wishlist);
}

function updateWishlistUI() {
    const wishlist = getWishlist();
    const badge = document.getElementById('wishlistCount');
    if (badge) {
        badge.textContent = wishlist.length;
    }
}

// ========================================
// UI Interactions
// ========================================

function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('active');
}

function quickView(productId) {
    if (!window.PRODUCTS) return;
    
    const product = window.PRODUCTS.find(p => p.id == productId);
    if (!product) return;
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal open';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 900px; display: flex;">
            <div class="modal-close" onclick="this.closest('.modal').remove()">&times;</div>
            <div style="width: 50%; background: #f8fafc; display: flex; align-items: center; justify-content: center; padding: 40px;">
                <img src="${product.image}" alt="${product.name}" style="max-width: 100%; max-height: 350px;">
            </div>
            <div style="width: 50%; padding: 40px; overflow-y: auto; max-height: 90vh;">
                <div class="brand">${product.brand}</div>
                <h2 style="margin: 10px 0;">${product.name}</h2>
                <div class="price" style="font-size: 24px; margin: 15px 0;">${formatMoney(product.price)}</div>
                <p style="color: var(--text-gray); margin-bottom: 20px;">${product.desc}</p>
                <div style="margin-bottom: 20px;">
                    <strong>Thông số:</strong>
                    <p style="color: var(--text-gray); margin-top: 10px;">${product.specs}</p>
                </div>
                <button class="btn btn-primary" onclick="addToCart(${product.id}); this.closest('.modal').remove();">
                    <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                </button>
            </div>
        </div>
    `;
    
    // Close on overlay click
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.remove();
        }
    });
    
    document.body.appendChild(modal);
}

function handleHeaderSearch() {
    const searchTerm = document.getElementById('searchInput').value.trim();
    if (searchTerm) {
        window.location.href = `products.php?search=${encodeURIComponent(searchTerm)}`;
    }
}

// Add Enter key support for search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleHeaderSearch();
            }
        });
    }
});

// ========================================
// Initialize on Page Load
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart UI
    updateCartUI();
    updateWishlistUI();
    
    // Check wishlist items and update UI
    const wishlist = getWishlist();
    document.querySelectorAll('[onclick^="toggleWishlist"]').forEach(btn => {
        const productId = parseInt(btn.getAttribute('onclick').match(/\d+/)[0]);
        if (wishlist.includes(productId)) {
            const icon = btn.querySelector('i');
            icon.classList.remove('far');
            icon.classList.add('fas');
            icon.style.color = '#ef4444';
        }
    });
    
    // Close cart when clicking overlay
    const overlay = document.querySelector('.cart-overlay');
    if (overlay) {
        overlay.addEventListener('click', toggleCart);
    }
});

// ========================================
// Smooth Scroll for Anchor Links
// ========================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// ========================================
// Export Functions
// ========================================

// Make functions available globally
window.formatMoney = formatMoney;
window.showToast = showToast;
window.getCart = getCart;
window.saveCart = saveCart;
window.clearCart = clearCart;
window.addToCart = addToCart;
window.changeQuantity = changeQuantity;
window.removeFromCart = removeFromCart;
window.toggleCart = toggleCart;
window.toggleWishlist = toggleWishlist;
window.toggleMobileMenu = toggleMobileMenu;
window.quickView = quickView;
window.handleHeaderSearch = handleHeaderSearch;
