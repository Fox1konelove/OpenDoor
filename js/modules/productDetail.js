import { products, sizeOptions } from '../data/products.js';
import { addToCart } from './cart.js';

let currentProduct = null;
let selectedSize = sizeOptions[0];
let selectedVariant = null;

const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
}[c]));

export function openProductDetail(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    currentProduct = product;
    selectedSize = sizeOptions[0];
    selectedVariant = product.variants?.[0] || { name: 'Стандарт', image: product.image };

    document.getElementById('breadcrumbCategory').textContent = product.category;
    document.getElementById('breadcrumbTitle').textContent = product.title;
    document.getElementById('detailTitle').textContent = product.title;
    document.getElementById('detailDescription').textContent = product.description;

    renderGallery();
    renderSizeOptions();
    renderColorOptions();
    renderSpecs();
    updateDetailPriceAndSku();

    ['mainPage', 'categoryPage', 'infoPage'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.style.display = 'none'; el.classList.remove('active'); }
    });

    const page = document.getElementById('productDetailPage');
    page.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    history.pushState({ page: 'product', id: productId }, '', `?product=${productId}`);
}

function renderGallery() {
    const main = document.getElementById('detailMainImg');
    main.src = selectedVariant.image;
    main.alt = currentProduct.title;

    const thumbs = document.getElementById('detailThumbnails');
    const variants = currentProduct.variants || [{ name: 'Вариант', image: currentProduct.image }];

    thumbs.innerHTML = variants.map((variant, i) => `
        <button type="button" class="thumbnail ${i === 0 ? 'active' : ''}" data-variant-index="${i}">
            <img src="${esc(variant.image)}" alt="${esc(variant.name)}" loading="lazy">
        </button>
    `).join('');

    thumbs.querySelectorAll('.thumbnail').forEach(button => {
        button.addEventListener('click', () => {
            selectedVariant = variants[Number(button.dataset.variantIndex)];
            thumbs.querySelectorAll('.thumbnail').forEach(b => b.classList.remove('active'));
            button.classList.add('active');
            main.src = selectedVariant.image;
            updateDetailPriceAndSku();
        });
    });
}

function renderSizeOptions() {
    const container = document.getElementById('detailSizeOptions');
    container.innerHTML = sizeOptions.map((size, i) => `
        <button type="button" class="size-btn ${i === 0 ? 'active' : ''}" data-size-index="${i}">
            ${esc(size.name)}
        </button>
    `).join('');

    container.querySelectorAll('.size-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            container.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedSize = sizeOptions[Number(btn.dataset.sizeIndex)];
            updateDetailPriceAndSku();
        });
    });
}

function renderColorOptions() {
    const container = document.getElementById('detailColorOptions');
    const variants = currentProduct.variants || [{ name: 'Вариант', image: currentProduct.image }];

    container.innerHTML = variants.map((variant, i) => `
        <button type="button" class="color-btn ${i === 0 ? 'active' : ''}" data-variant-index="${i}">
            <span class="color-preview"><img src="${esc(variant.image)}" alt="" loading="lazy"></span>
            <span>${esc(variant.name)}</span>
        </button>
    `).join('');

    container.querySelectorAll('.color-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const index = Number(btn.dataset.variantIndex);
            selectedVariant = variants[index];
            container.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('detailMainImg').src = selectedVariant.image;
            document.querySelectorAll('#detailThumbnails .thumbnail').forEach((thumb, i) =>
                thumb.classList.toggle('active', i === index)
            );
            updateDetailPriceAndSku();
        });
    });
}

function renderSpecs() {
    const specs = currentProduct.specs || {};
    document.getElementById('detailSpecs').innerHTML = Object.entries(specs)
        .map(([key, value]) => `<tr><td>${esc(key)}</td><td>${esc(value)}</td></tr>`)
        .join('');
}

function updateDetailPriceAndSku() {
    if (!currentProduct) return;
    const price = Number(currentProduct.price || 0) + Number(selectedSize.priceMod || 0);
    const priceEl = document.getElementById('detailCurrentPrice');
    const oldEl = document.getElementById('detailOldPrice');

    priceEl.textContent = price > 0 ? `${price.toLocaleString('ru-RU')} ₽` : 'Цена по запросу';
    oldEl.style.display = 'none';

    document.getElementById('detailSku').textContent =
        `Артикул: DM-${String(currentProduct.id).padStart(4, '0')} · ${selectedVariant?.name || 'Стандарт'}`;
}

export function initProductDetail() {
    document.getElementById('detailAddToCartBtn')?.addEventListener('click', () => {
        if (!currentProduct) return;
        addToCart(currentProduct.id, selectedSize.name, selectedVariant?.name || 'Стандарт');
    });

    document.querySelectorAll('#productDetailPage .tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#productDetailPage .tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('#productDetailPage .tab-content').forEach(t => t.style.display = 'none');
            btn.classList.add('active');
            document.getElementById(`${btn.dataset.tab}Tab`).style.display = 'block';
        });
    });
}

export function showMainPage() {
    document.getElementById('mainPage').style.display = 'block';
    document.getElementById('productDetailPage').style.display = 'none';
    history.pushState({ page: 'main' }, '', window.location.pathname);
}
