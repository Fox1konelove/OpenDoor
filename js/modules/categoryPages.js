import { products, categories } from '../data/products.js';
import { renderProductCollection } from './renderers.js';

let currentCategory = null;
let currentSubFilter = 'all';

const descriptions = {
    'Межкомнатные двери': 'Модели для дома и интерьера: классические, современные, эмалированные и шпонированные решения.',
    'Входные двери': 'Надёжные входные двери для квартиры и дома с разными вариантами отделки.',
    'Фурнитура и прочее': 'Ручки, замки, защёлки и другие комплектующие для дверей.',
    'Складные двери': 'Компактные складные решения для экономии пространства.',
    'Специальные двери': 'Двери специального назначения и решения для особых условий эксплуатации.',
    'Арки и Порталы': 'Арки и порталы для оформления дверных проёмов.',
    'Скрытые двери': 'Минималистичные скрытые конструкции, которые становятся частью стены.'
};

export function initCategoryPages() {
    document.querySelectorAll('.nav-category:not(.info-link)').forEach(link => {
        link.addEventListener('click', () => {
            const category = link.dataset.section;
            category === 'all' ? showMainPageFromCategory() : openCategoryPage(category);
        });
    });

    document.querySelectorAll('.sidebar-category:not(.info-category)').forEach(item => {
        item.addEventListener('click', () => {
            const category = item.dataset.category;
            category === 'all' ? showMainPageFromCategory() : openCategoryPage(category);
            document.dispatchEvent(new CustomEvent('closeSidebar'));
        });
    });

    document.querySelectorAll('.footer-category').forEach(link => {
        link.addEventListener('click', () => openCategoryPage(link.dataset.cat));
    });

    document.addEventListener('click', event => {
        const chip = event.target.closest('.category-filter-chip');
        if (!chip) return;
        document.querySelectorAll('.category-filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        currentSubFilter = chip.dataset.filter;
        renderCategoryProducts();
    });

    document.addEventListener('showMainFromCategory', showMainPageFromCategory);
}

export function openCategoryPage(category) {
    if (!categories.some(c => c.name === category)) return;

    currentCategory = category;
    currentSubFilter = 'all';

    ['mainPage', 'productDetailPage', 'infoPage'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = 'none';
            element.classList.remove('active');
        }
    });

    const page = document.getElementById('categoryPage');
    if (!page) return;
    page.style.display = 'block';
    page.classList.add('active');

    const data = categories.find(c => c.name === category);
    document.getElementById('categoryBreadcrumb').textContent = category;
    document.getElementById('categoryPageTitle').textContent = `${data.icon} ${category}`;
    document.getElementById('categoryDescription').textContent =
        descriptions[category] || `Каталог: ${category}.`;

    renderCategoryProducts();
    syncActiveNavigation(category);

    history.pushState({ page: 'category', category }, '', `?category=${encodeURIComponent(category)}`);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderCategoryProducts() {
    const grid = document.getElementById('categoryProductsGrid');
    if (!grid || !currentCategory) return;

    let filtered = products.filter(p => p.category === currentCategory);

    if (currentSubFilter !== 'all') {
        filtered = filtered.filter(p =>
            p.material === currentSubFilter ||
            p.title.toLowerCase().includes(currentSubFilter.toLowerCase())
        );
    }

    const filters = [...new Set(products
        .filter(p => p.category === currentCategory)
        .map(p => p.material)
        .filter(Boolean))];

    const filterContainer = document.getElementById('categoryFilters');
    filterContainer.innerHTML = filters.length > 1
        ? `<button class="category-filter-chip active" data-filter="all">Все модели</button>
           ${filters.map(filter => `<button class="category-filter-chip" data-filter="${escapeHtml(filter)}">${escapeHtml(filter)}</button>`).join('')}`
        : '';

    renderProductCollection(filtered, grid);
    const stats = document.querySelector('.category-stats');
    if (stats) stats.innerHTML = `Найдено <span>${filtered.length}</span> моделей`;
}

function syncActiveNavigation(category) {
    document.querySelectorAll('.nav-category, .sidebar-category').forEach(el => {
        el.classList.toggle('active',
            el.dataset.section === category || el.dataset.category === category
        );
    });
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

export function showMainPageFromCategory() {
    ['categoryPage', 'productDetailPage', 'infoPage'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.style.display = 'none'; el.classList.remove('active'); }
    });

    const main = document.getElementById('mainPage');
    if (main) main.style.display = 'block';

    document.querySelectorAll('.nav-category, .sidebar-category').forEach(el => el.classList.remove('active'));
    document.querySelector('.nav-category[data-section="all"]')?.classList.add('active');
    document.querySelector('.sidebar-category[data-category="all"]')?.classList.add('active');

    history.pushState({ page: 'main' }, '', window.location.pathname);
}
