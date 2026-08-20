// modules/modals.js — ПОЛНАЯ ВЕРСИЯ
import { getCart, getCartTotal, updateQuantity, removeFromCart, clearCart } from './cart.js';
import { showNotification } from './notifications.js';

export function initModals() {
    // Закрытие модалок по крестику
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) closeModal(modal.id);
        });
    });

    // Закрытие по клику на оверлей
    window.addEventListener('click', (e) => {
        if (e.target.classList && e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });

    // Кнопка корзины
    document.getElementById('cartBtn')?.addEventListener('click', openCartModal);

    // Оформление заказа
    document.getElementById('checkoutBtn')?.addEventListener('click', checkout);

    // Слушаем событие открытия корзины из других модулей
    document.addEventListener('openCartModal', openCartModal);
}

export function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

export function closeModal(modalId) {   // ✅ ЭКСПОРТ ДОБАВЛЕН
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function openCartModal() {
    const itemsList = document.getElementById('cartItemsList');
    const summaryDiv = document.getElementById('cartSummary');
    const cart = getCart();

    if (cart.length === 0) {
        itemsList.innerHTML = '<p style="text-align:center; padding:40px;">Корзина пуста</p>';
        summaryDiv.innerHTML = '';
    } else {
        itemsList.innerHTML = cart.map((item, idx) => `
            <div class="cart-item">
                <div class="cart-item-image"><img src="${item.image}" alt="${item.title}"></div>
                <div class="cart-item-info">
                    <div class="cart-item-title">${item.title}</div>
                    <div class="cart-item-options">${item.size}, ${item.color}</div>
                    <div class="cart-item-price">${item.price.toLocaleString()} ₽</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" data-index="${idx}" data-delta="-1">−</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn" data-index="${idx}" data-delta="1">+</button>
                </div>
                <button class="cart-item-remove" data-index="${idx}">🗑</button>
            </div>
        `).join('');

        itemsList.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                const delta = parseInt(btn.dataset.delta);
                updateQuantity(idx, delta);
                openCartModal();
            });
        });

        itemsList.querySelectorAll('.cart-item-remove').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                removeFromCart(idx);
                openCartModal();
            });
        });

        const { totalItems, totalPrice } = getCartTotal();
        summaryDiv.innerHTML = `
            <div class="cart-summary-row"><span>Товаров (${totalItems} шт.)</span><span>${totalPrice.toLocaleString()} ₽</span></div>
            <div class="cart-summary-total"><span>Итого к оплате</span><span>${totalPrice.toLocaleString()} ₽</span></div>
        `;
    }

    openModal('cartModal');
}

function checkout() {
    const cart = getCart();
    if (!cart.length) return;

    const items = cart.map(item => ({
        product_id: item.id,
        title: item.title,
        quantity: item.quantity,
        price: item.price
    }));

    fetch('php/order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ items })
    })
        .then(r => r.json())
        .catch(() => ({}))
        .finally(() => {
            showNotification('Заказ оформлен! Менеджер свяжется с вами');
            clearCart();
            closeModal('cartModal');
        });
}