// modules/cart.js
import { products } from '../data/products.js';
import { showNotification } from './notifications.js';

let cart = [];

export function initCart() {
    const saved = localStorage.getItem('cart');
    if (saved) {
        try {
            cart = JSON.parse(saved);
        } catch (e) {
            cart = [];
        }
    }
    updateCartCount();
}

export function addToCart(productId, size = null, color = null) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const existing = cart.find(item =>
        item.id === productId &&
        item.size === (size || 'стандарт') &&
        item.color === (color || 'белый')
    );

    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            title: product.title,
            size: size || 'стандарт',
            color: color || 'белый',
            price: product.price,
            quantity: 1,
            image: product.variants?.find(v => v.name === (color || 'стандарт'))?.image || product.image
        });
    }

    updateCartCount();
    saveCart();
    showNotification(`${product.title} добавлен в корзину`);
}

export function addToCartSimple(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const existing = cart.find(item => item.id === productId && !item.size);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            ...product,
            quantity: 1,
            size: 'стандарт',
            color: 'белый',
            image: product.variants?.find(v => v.name === (color || 'стандарт'))?.image || product.image
        });
    }

    updateCartCount();
    saveCart();
    showNotification(`${product.title} добавлен в корзину`);
}

export function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartCount();
    saveCart();
}

export function updateQuantity(index, delta) {
    const newQ = cart[index].quantity + delta;
    if (newQ <= 0) {
        cart.splice(index, 1);
    } else {
        cart[index].quantity = newQ;
    }
    updateCartCount();
    saveCart();
}

export function getCart() {
    return cart;
}

export function getCartTotal() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    return { totalItems, totalPrice };
}

export function clearCart() {
    cart = [];
    updateCartCount();
    saveCart();
}

export function getCartCount() {
    return cart.reduce((sum, item) => sum + item.quantity, 0);
}

function updateCartCount() {
    const badge = document.getElementById('cartCount');
    if (badge) {
        badge.innerText = getCartCount();
    }
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

export function openCartModal() {
    const event = new CustomEvent('openCartModal');
    document.dispatchEvent(event);
}