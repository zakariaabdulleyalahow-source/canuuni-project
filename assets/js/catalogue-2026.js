/* Canuuni 2026 catalogue interactions — browser-side only */
document.addEventListener('DOMContentLoaded', () => {
  const grid = document.getElementById('canuuni-products');
  if (!grid) return;

  const cards = Array.from(grid.querySelectorAll('[data-product-card]'));
  const sort = document.getElementById('cu-sort-products');
  const stockOnly = document.getElementById('cu-stock-only');
  const savedOnly = document.getElementById('cu-saved-only');
  const count = document.getElementById('cu-visible-count');
  const empty = document.getElementById('cu-filter-empty');
  const savedCount = document.getElementById('cu-saved-count');
  const storageKey = 'canuuni_saved_products_v1';
  let saved = [];

  try { saved = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (_) { saved = []; }
  if (!Array.isArray(saved)) saved = [];
  saved = saved.map(String);

  const save = () => {
    try { localStorage.setItem(storageKey, JSON.stringify(saved)); } catch (_) {}
    updateSavedUI();
  };

  const updateSavedUI = () => {
    cards.forEach(card => {
      const id = String(card.dataset.id);
      const button = card.querySelector('.cu-wishlist-btn');
      const active = saved.includes(id);
      if (!button) return;
      button.classList.toggle('is-saved', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      button.setAttribute('aria-label', active ? 'Remove from saved items' : 'Save item');
      button.innerHTML = active ? '<i class="bi bi-heart-fill"></i>' : '<i class="bi bi-heart"></i>';
    });
    if (savedCount) savedCount.textContent = saved.length;
  };

  const applyFilters = () => {
    let visible = 0;
    cards.forEach(card => {
      const isSaved = saved.includes(String(card.dataset.id));
      const inStock = card.dataset.stock === '1';
      const show = (!stockOnly || !stockOnly.checked || inStock) &&
                   (!savedOnly || !savedOnly.checked || isSaved);
      card.hidden = !show;
      if (show) visible++;
    });
    if (count) count.textContent = visible;
    if (empty) empty.hidden = visible !== 0;
  };

  const sortCards = () => {
    if (!sort) return;
    const mode = sort.value;
    const sorted = [...cards].sort((a, b) => {
      if (mode === 'price-low') return Number(a.dataset.price) - Number(b.dataset.price);
      if (mode === 'price-high') return Number(b.dataset.price) - Number(a.dataset.price);
      if (mode === 'name') return a.dataset.name.localeCompare(b.dataset.name);
      return Number(a.dataset.position) - Number(b.dataset.position);
    });
    sorted.forEach(card => grid.appendChild(card));
  };

  cards.forEach(card => {
    const wishlist = card.querySelector('.cu-wishlist-btn');
    if (wishlist) wishlist.addEventListener('click', () => {
      const id = String(card.dataset.id);
      saved = saved.includes(id) ? saved.filter(item => item !== id) : [...saved, id];
      save();
      if (savedOnly && savedOnly.checked) applyFilters();
    });

    const quickView = card.querySelector('.cu-quickview-btn');
    if (quickView) quickView.addEventListener('click', () => {
      const image = card.dataset.image;
      const name = card.dataset.name;
      const price = Number(card.dataset.price).toFixed(2);
      const description = card.dataset.description || 'Product details are available on the product page.';
      const href = card.dataset.href;
      const available = card.dataset.stock === '1';
      const modal = document.getElementById('cuQuickViewModal');
      if (!modal) return;
      modal.querySelector('[data-cu-qv-name]').textContent = name;
      modal.querySelector('[data-cu-qv-price]').textContent = '$' + price;
      modal.querySelector('[data-cu-qv-description]').textContent = description;
      modal.querySelector('[data-cu-qv-link]').href = href;
      modal.querySelector('[data-cu-qv-stock]').textContent = available ? 'In stock' : 'Currently unavailable';
      modal.querySelector('[data-cu-qv-stock]').className = 'badge rounded-pill ' + (available ? 'text-bg-success' : 'text-bg-secondary');
      const img = modal.querySelector('[data-cu-qv-image]');
      img.src = image || '';
      img.alt = name;
      img.hidden = !image;
      const placeholder = modal.querySelector('[data-cu-qv-placeholder]');
      if (placeholder) placeholder.hidden = !!image;
      const add = modal.querySelector('[data-cu-qv-add]');
      add.hidden = !available;
      add.dataset.id = card.dataset.id;
      add.dataset.name = name;
      add.dataset.price = price;
      bootstrap.Modal.getOrCreateInstance(modal).show();
    });
  });

  if (sort) sort.addEventListener('change', sortCards);
  if (stockOnly) stockOnly.addEventListener('change', applyFilters);
  if (savedOnly) savedOnly.addEventListener('change', applyFilters);
  updateSavedUI();
  applyFilters();

  // Quantity stepper on product details page.
  const qty = document.getElementById('product-qty');
  document.querySelectorAll('[data-qty-step]').forEach(button => {
    button.addEventListener('click', () => {
      if (!qty) return;
      const max = Number(qty.max) || 999;
      const next = Number(qty.value || 1) + Number(button.dataset.qtyStep);
      qty.value = String(Math.max(1, Math.min(max, next)));
      qty.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  // Save product on detail page using the same browser-local wishlist.
  const detailSave = document.querySelector('[data-cu-detail-save]');
  if (detailSave) {
    const id = String(detailSave.dataset.id);
    const paint = () => {
      const active = saved.includes(id);
      detailSave.classList.toggle('is-saved', active);
      detailSave.innerHTML = active ? '<i class="bi bi-heart-fill me-2"></i>Saved' : '<i class="bi bi-heart me-2"></i>Save for later';
      detailSave.setAttribute('aria-pressed', active ? 'true' : 'false');
    };
    paint();
    detailSave.addEventListener('click', () => {
      saved = saved.includes(id) ? saved.filter(item => item !== id) : [...saved, id];
      save();
      paint();
    });
  }
});
