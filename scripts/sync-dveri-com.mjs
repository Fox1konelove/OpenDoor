/**
 * Синхронизация каталога с официальным dveri.com.
 *
 * Запуск: node scripts/sync-dveri-com.mjs
 *
 * Скрипт не зависит от DOM-библиотек: использует HTML, который отдаёт сайт,
 * находит ссылки /catalog/, обходит страницы товаров и сохраняет данные в
 * js/data/dveri-com-catalog.json.
 */
import fs from 'node:fs/promises';
import path from 'node:path';

const ROOT = 'https://dveri.com';
const OUTPUT = path.resolve('js/data/dveri-com-catalog.json');
const START = [
  '/catalog/dveri-mezhkomnatnyye',
  '/catalog/vkhodnyye-dveri',
  '/catalog/skladnye-dveri',
  '/catalog/skrytyye-dveri',
  '/catalog/spetsialnyye-dveri',
  '/catalog/arki-i-portaly',
  '/catalog/furnitura-i-prochee'
];

const seen = new Set();
const queue = [...START];
const products = [];

const clean = value => value
  .replace(/<script[\s\S]*?<\/script>/gi, ' ')
  .replace(/<style[\s\S]*?<\/style>/gi, ' ')
  .replace(/<[^>]+>/g, ' ')
  .replace(/&nbsp;/g, ' ')
  .replace(/&amp;/g, '&')
  .replace(/\s+/g, ' ')
  .trim();

const links = html => [...html.matchAll(/href=["']([^"']*\/catalog\/[^"']*)["']/gi)]
  .map(m => m[1].split('#')[0].split('?')[0])
  .filter(Boolean)
  .map(u => u.startsWith('http') ? new URL(u).pathname : u);

function first(text, re) {
  const m = text.match(re);
  return m ? clean(m[1]) : '';
}

while (queue.length && products.length < 5000) {
  const current = queue.shift();
  const url = current.startsWith('http') ? current : ROOT + current;
  if (seen.has(url)) continue;
  seen.add(url);

  let html;
  try {
    const response = await fetch(url, { headers: { 'user-agent': 'DoorCatalogSync/1.0' } });
    if (!response.ok) continue;
    html = await response.text();
  } catch {
    continue;
  }

  for (const href of links(html)) {
    const absolute = href.startsWith('http') ? href : ROOT + href;
    if (!seen.has(absolute)) queue.push(href);
  }

  // Карточка товара обычно содержит «Артикул», «Розничная цена» и «Описание».
  if (!html.includes('Розничная цена') || !html.includes('Артикул')) continue;

  const text = clean(html);
  const title = first(html, /<h1[^>]*>([\s\S]*?)<\/h1>/i);
  const article = first(text, /Артикул\s+([\w.-]+)/i);
  const color = first(text, /Артикул\s+[\w.-]+\s+Цвет\s+([^Р]{2,80}?)\s+(?:Размер|Высота|Ширина|Розничная цена)/i);
  const price = first(text, /Розничная цена\s+За [^\d]{0,20}([\d\s]+)₽/i);
  const description = first(text, /Описание:\s*(.+?)(?:\s+Особенности:|\s+Комплектующие|\s+Документы|\s+Декор|\s+Где купить)/i);

  if (!title || !article) continue;
  products.push({
    title,
    article,
    color,
    price: Number(price.replace(/\s/g, '')) || 0,
    description,
    url: absolute,
    source: 'dveri.com'
  });

  if (products.length % 50 === 0) console.log(`Собрано: ${products.length}`);
}

await fs.writeFile(OUTPUT, JSON.stringify(products, null, 2), 'utf8');
console.log(`Готово: ${products.length} товаров → ${OUTPUT}`);
