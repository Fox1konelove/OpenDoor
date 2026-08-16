import { products, categories } from '../data/products.js';
import { addToCartSimple } from './cart.js';
import { openProductDetail } from './productDetail.js';

const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'
}[char]));

export function renderCategories() {
    const grid = document.getElementById('categoriesGrid');
    if (!grid) return;

    grid.innerHTML = categories.map(category => {
        const first = products.find(p => p.category === category.name);
        return `
            <article class="category-card" data-cat="${esc(category.name)}">
                <div class="category-image">
                    ${first ? `<img src="${esc(first.image)}" alt="${esc(category.name)}" loading="lazy">` : ''}
                    <span class="category-count">${category.count} моделей</span>
                </div>
                <div class="category-card-body">
                    <h3>${category.icon} ${esc(category.name)}</h3>
                    <p>Смотреть каталог <span>→</span></p>
                </div>
            </article>
        `;
    }).join('');

    grid.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', () => {
            import('./categoryPages.js').then(module => module.openCategoryPage(card.dataset.cat));
        });
    });
}

function priceLabel(price) {
    return price > 0 ? `${price.toLocaleString('ru-RU')} ₽` : 'Цена по запросу';
}

function productCard(p) {
    const variants = p.variants || [{ name: 'Вариант', image: p.image }];
    return `
        <article class="product-card" data-id="${p.id}">
            <div class="product-image">
                <img class="product-main-image" src="${esc(p.image)}" alt="${esc(p.title)}" loading="lazy">
                ${variants.length > 1 ? `
                    <div class="product-variant-count">${variants.length} варианта</div>
                ` : ''}
            </div>
            <div class="product-info">
                <div class="product-card-top">
                    <div>
                        <div class="product-title">${esc(p.title)}</div>
                        <div class="product-material">${esc(p.material)}</div>
                    </div>
                </div>
                ${variants.length > 1 ? `
                    <div class="product-swatches" aria-label="Варианты отделки">
                        ${variants.slice(0, 8).map((v, i) => `
                            <button class="product-swatch ${i === 0 ? 'active' : ''}"
                                    type="button"
                                    title="${esc(v.name)}"
                                    aria-label="${esc(v.name)}"
                                    data-image="${esc(v.image)}">
                                <img src="${esc(v.image)}" alt="" loading="lazy">
                            </button>
                        `).join('')}
                        ${variants.length > 8 ? `<span class="product-more-variants">+${variants.length - 8}</span>` : ''}
                    </div>
                ` : ''}
                <div class="product-card-footer">
                    <div class="product-price">${priceLabel(p.price)}</div>
                    <button class="btn btn-primary product-cart-btn" type="button" data-id="${p.id}">В корзину</button>
                </div>
            </div>
        </article>
    `;
}

export function renderProducts(category = 'all') {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    const filtered = category === 'all' ? products : products.filter(p => p.category === category);
    grid.innerHTML = filtered.map(productCard).join('');
    bindProductCards(grid);
}

export function renderProductCollection(productsList, grid) {
    if (!grid) return;
    grid.innerHTML = productsList.map(productCard).join('');
    bindProductCards(grid);
}

function bindProductCards(grid) {
    grid.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', event => {
            const swatch = event.target.closest('.product-swatch');
            if (swatch) {
                event.stopPropagation();
                card.querySelectorAll('.product-swatch').forEach(b => b.classList.remove('active'));
                swatch.classList.add('active');
                const img = card.querySelector('.product-main-image');
                if (img) img.src = swatch.dataset.image;
                return;
            }

            const button = event.target.closest('.product-cart-btn');
            if (button) {
                event.stopPropagation();
                addToCartSimple(Number(button.dataset.id));
                return;
            }

            openProductDetail(Number(card.dataset.id));
        });
    });
}
