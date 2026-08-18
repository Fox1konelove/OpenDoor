// modules/infoPages.js — ИСПРАВЛЕННАЯ ВЕРСИЯ
import { infoArticles, openInfoModal } from './infoModal.js';

// Группировка статей по типам
const articlesList = Object.values(infoArticles).filter(a => a.type === 'article');
const tipsList = Object.values(infoArticles).filter(a => a.type === 'tip');

export function initInfoPages() {
    // Обработка кликов на информационные ссылки в шапке
    document.querySelectorAll('.nav-category.info-link').forEach(link => {
        link.addEventListener('click', () => {
            const section = link.dataset.section;
            openInfoPage(section);
        });
    });

    // Обработка кликов в боковом меню
    document.querySelectorAll('.sidebar-category.info-category').forEach(item => {
        item.addEventListener('click', () => {
            const section = item.dataset.category;
            openInfoPage(section);
            const event = new CustomEvent('closeSidebar');
            document.dispatchEvent(event);
        });
    });

    // Обработка кликов в футере
    document.querySelectorAll('.footer-info').forEach(link => {
        link.addEventListener('click', () => {
            const section = link.dataset.section;
            openInfoPage(section);
        });
    });
}

export function openInfoPage(section) {
    let title = '';
    let items = [];
    let subtitle = '';

    if (section === 'info') {
        title = '📖 Статьи о дверях';
        subtitle = 'Полезная информация о выборе, установке и уходе за дверями';
        items = articlesList;
    } else if (section === 'tips') {
        title = '💡 Советы по выбору дверей';
        subtitle = 'Практические рекомендации от наших экспертов';
        items = tipsList;
    } else if (section === 'faq') {
        openFaqPage();
        return;
    } else {
        return;
    }

    // ===== СКРЫВАЕМ ВСЁ С ПРОВЕРКАМИ =====
    // 1. Скрываем главную страницу
    const mainPage = document.getElementById('mainPage');
    if (mainPage) mainPage.style.display = 'none';

    // 2. Скрываем страницу товара
    const detailPage = document.getElementById('productDetailPage');
    if (detailPage) detailPage.style.display = 'none';

    // 3. Скрываем страницу категории (ЕСЛИ ОНА СУЩЕСТВУЕТ)
    const categoryPage = document.getElementById('categoryPage');
    if (categoryPage) {
        categoryPage.classList.remove('active');
        categoryPage.style.display = 'none';
    }

    // 4. Показываем страницу информации
    const page = document.getElementById('infoPage');
    if (!page) {
        console.error('❌ Элемент #infoPage не найден!');
        return;
    }
    page.classList.add('active');
    page.style.display = 'block';

    // 5. Заполняем контент
    const breadcrumb = document.getElementById('infoBreadcrumb');
    if (breadcrumb) breadcrumb.textContent = title;

    const titleEl = document.getElementById('infoPageTitle');
    if (titleEl) titleEl.textContent = title;

    const content = document.getElementById('infoContent');
    if (content) {
        content.innerHTML = `
            <p class="info-section-subtitle">${subtitle}</p>
            <div class="info-grid" id="infoGrid">
                ${items.map(item => `
                    <div class="info-card-item" data-id="${item.id}">
                        <div class="card-image">
                            <img src="${item.image}" alt="${item.title}" loading="lazy">
                        </div>
                        <div class="card-body">
                            <span class="card-tag">${item.tag}</span>
                            <h3 class="card-title">${item.title}</h3>
                            <p class="card-excerpt">${item.excerpt}</p>
                            <div class="card-meta">
                                <span>📅 ${item.date}</span>
                                <span>⏱️ ${item.readTime}</span>
                                <span class="read-more">Читать →</span>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    // Обработка кликов по карточкам
    document.querySelectorAll('.info-card-item').forEach(card => {
        card.addEventListener('click', () => {
            const id = card.dataset.id;
            openInfoModal(id);
        });
    });

    window.scrollTo(0, 0);

    // Обновляем активные ссылки
    document.querySelectorAll('.nav-category').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelectorAll('.nav-category.info-link').forEach(link => {
        if (link.dataset.section === section) {
            link.classList.add('active');
        }
    });

    document.querySelectorAll('.sidebar-category').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.category === section && item.classList.contains('info-category')) {
            item.classList.add('active');
        }
    });

    history.pushState({ page: 'info', section: section }, '', `?info=${section}`);
}

// Страница FAQ
function openFaqPage() {
    // ===== СКРЫВАЕМ ВСЁ С ПРОВЕРКАМИ =====
    const mainPage = document.getElementById('mainPage');
    if (mainPage) mainPage.style.display = 'none';

    const detailPage = document.getElementById('productDetailPage');
    if (detailPage) detailPage.style.display = 'none';

    const categoryPage = document.getElementById('categoryPage');
    if (categoryPage) {
        categoryPage.classList.remove('active');
        categoryPage.style.display = 'none';
    }

    const page = document.getElementById('infoPage');
    if (!page) {
        console.error('❌ Элемент #infoPage не найден!');
        return;
    }
    page.classList.add('active');
    page.style.display = 'block';

    const breadcrumb = document.getElementById('infoBreadcrumb');
    if (breadcrumb) breadcrumb.textContent = '❓ Часто задаваемые вопросы';

    const titleEl = document.getElementById('infoPageTitle');
    if (titleEl) titleEl.textContent = '❓ Часто задаваемые вопросы';

    const content = document.getElementById('infoContent');
    if (content) {
        content.innerHTML = `
            <div class="faq-container">
                <div class="faq-item active">
                    <div class="faq-question">Какие сроки изготовления дверей?</div>
                    <div class="faq-answer"><p>Стандартные модели — 3-5 дней. Эксклюзивные двери по индивидуальному заказу — от 14 до 30 дней.</p></div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Предоставляете ли вы гарантию?</div>
                    <div class="faq-answer"><p>Да, мы даём гарантию 1 лет на все двери и монтажные работы.</p></div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Есть ли бесплатная доставка?</div>
                    <div class="faq-answer"><p>при заказе от 30 тыс руб. рассматриваются спец. условия</p></div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Какой материал лучше — массив или шпон?</div>
                    <div class="faq-answer"><p>Массив — дороже и экологичнее. Шпон — доступнее и устойчивее к влажности.</p></div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Можно ли установить дверь самостоятельно?</div>
                    <div class="faq-answer"><p>Рекомендуем доверять монтаж профессионалам. Мы выполняем установку качественно и с гарантией.</p></div>
                </div>
            </div>
        `;
    }

    // Аккордеон для FAQ
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', () => {
            const item = q.closest('.faq-item');
            if (item) item.classList.toggle('active');
        });
    });

    window.scrollTo(0, 0);

    // Обновляем активные ссылки
    document.querySelectorAll('.nav-category').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelectorAll('.nav-category.info-link').forEach(link => {
        if (link.dataset.section === 'faq') {
            link.classList.add('active');
        }
    });

    history.pushState({ page: 'info', section: 'faq' }, '', '?info=faq');
}

export function showMainPageFromInfo() {
    // Скрываем страницу информации
    const infoPage = document.getElementById('infoPage');
    if (infoPage) {
        infoPage.classList.remove('active');
        infoPage.style.display = 'none';
    }

    // Скрываем страницу категории
    const categoryPage = document.getElementById('categoryPage');
    if (categoryPage) {
        categoryPage.classList.remove('active');
        categoryPage.style.display = 'none';
    }

    // Показываем главную страницу
    const mainPage = document.getElementById('mainPage');
    if (mainPage) mainPage.style.display = 'block';

    // Убираем активность с информационных ссылок
    document.querySelectorAll('.nav-category.info-link').forEach(link => {
        link.classList.remove('active');
    });

    // Убираем активность с категорий дверей
    document.querySelectorAll('.nav-category:not(.info-link)').forEach(link => {
        link.classList.remove('active');
    });

    // Активируем "Все двери"
    const allLink = document.querySelector('.nav-category[data-section="all"]');
    if (allLink) allLink.classList.add('active');

    // Сбрасываем активность в сайдбаре
    document.querySelectorAll('.sidebar-category').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.category === 'all') {
            item.classList.add('active');
        }
    });
}