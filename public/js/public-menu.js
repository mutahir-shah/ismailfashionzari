/**
 * SalePro Public Menu – Vanilla JS
 * Handles cart (localStorage), AJAX category loading with skeleton loaders,
 * infinite scroll, UI interactions, and WhatsApp checkout.
 */

(function () {
    'use strict';

    // ─── Config ──────────────────────────────────────────────────────────────
    const CART_KEY = 'salepro_cart_' + (window.MENU_CONFIG?.warehouse_id || '0');
    const baseUrl = window.MENU_CONFIG?.baseUrl || '';

    // ─── State ───────────────────────────────────────────────────────────────
    let cart = loadCart();

    // Variant modal state
    let selectedProduct = null;   // { id, name, basePrice, image }
    let selectedVariants = {};     // keyed by group index → { id, name, additional_price }

    // AJAX / lazy-load state
    let categoryState = {}; // { categoryId: { page: 1, loading: false, loaded: false, finished: false } }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function loadCart() {
        try {
            return JSON.parse(localStorage.getItem(CART_KEY)) || {};
        } catch (e) {
            return {};
        }
    }

    function saveCart() {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
    }

    function getTotal() {
        return Object.values(cart).reduce(function (sum, item) {
            return sum + item.price * item.qty;
        }, 0);
    }

    function getTotalItems() {
        return Object.values(cart).reduce(function (sum, item) {
            return sum + item.qty;
        }, 0);
    }

    function formatPrice(amount) {
        return typeof window.formatCurrency === 'function'
            ? window.formatCurrency(amount)
            : amount;
    }

    function parsePrice(raw) {
        return parseFloat(String(raw).replace(/,/g, '').trim()) || 0;
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ─── Cart Operations ─────────────────────────────────────────────────────
    function addItem(id, name, price, image) {
        const parsedPrice = parsePrice(price);
        if (cart[id]) {
            cart[id].qty += 1;
            cart[id].price = parsedPrice;
            cart[id].name = name;
            cart[id].image = image;
        } else {
            cart[id] = { id: id, name: name, price: parsedPrice, image: image, qty: 1 };
        }
        saveCart();
        renderAll();
        animateCartBadge();
    }

    function removeItem(id) {
        if (!cart[id]) return;
        cart[id].qty -= 1;
        if (cart[id].qty <= 0) {
            delete cart[id];
        }
        saveCart();
        renderAll();
    }

    function deleteItem(id) {
        delete cart[id];
        saveCart();
        renderAll();
    }

    // ─── Render ──────────────────────────────────────────────────────────────
    function renderAll() {
        renderProductButtons();
        renderFloatingBar();
        renderCartModal();
    }

    function renderProductButtons() {
        document.querySelectorAll('.pm-add-btn').forEach(function (btn) {
            const id = btn.dataset.id;
            const row = btn.closest('.pm-item-card');
            const qtyBox = row ? row.querySelector('.pm-qty-box') : null;
            if (!qtyBox) return;
            if (cart[id] && cart[id].qty > 0) {
                btn.style.display = 'none';
                qtyBox.style.display = 'flex';
                qtyBox.querySelector('.pm-qty-value').textContent = cart[id].qty;
            } else {
                btn.style.display = '';
                qtyBox.style.display = 'none';
            }
        });
    }

    function renderFloatingBar() {
        const bar = document.getElementById('pm-floating-bar');
        const total = getTotalItems();
        if (!bar) return;
        if (total > 0) {
            bar.classList.add('pm-visible');
            document.getElementById('pm-bar-count').textContent = total + ' item' + (total > 1 ? 's' : '');
            document.getElementById('pm-bar-total').textContent = formatPrice(getTotal());
        } else {
            bar.classList.remove('pm-visible');
        }
        const badge = document.getElementById('pm-header-cart-count');
        if (badge) {
            badge.textContent = total;
            badge.style.display = total > 0 ? '' : 'none';
        }
    }

    function renderCartModal() {
        const listEl = document.getElementById('pm-cart-list');
        const totalEl = document.getElementById('pm-cart-total');
        if (!listEl) return;
        const items = Object.values(cart);
        if (items.length === 0) {
            listEl.innerHTML = '<p class="pm-empty-cart">Your cart is empty.</p>';
            if (totalEl) totalEl.textContent = formatPrice(0);
            return;
        }
        listEl.innerHTML = items.map(function (item) {
            return '<div class="pm-cart-item" data-id="' + item.id + '">' +
                '<div class="pm-cart-item-info">' +
                (item.image ? '<img src="' + item.image + '" alt="' + escHtml(item.name) + '" class="pm-cart-img">' : '<div class="pm-cart-img-placeholder"></div>') +
                '<div class="pm-cart-item-text">' +
                '<span class="pm-cart-name">' + escHtml(item.name) + '</span>' +
                '<span class="pm-cart-unit-price">' + formatPrice(item.price) + ' each</span>' +
                '</div>' +
                '</div>' +
                '<div class="pm-cart-item-controls">' +
                '<div class="pm-qty-box pm-qty-box--cart" style="display:flex;">' +
                '<button class="pm-qty-btn pm-cart-minus" data-id="' + item.id + '">−</button>' +
                '<span class="pm-qty-value">' + item.qty + '</span>' +
                '<button class="pm-qty-btn pm-cart-plus" data-id="' + item.id + '">+</button>' +
                '</div>' +
                '<span class="pm-cart-subtotal">' + formatPrice(item.price * item.qty) + '</span>' +
                '<button class="pm-remove-btn" data-id="' + item.id + '" title="Remove">✕</button>' +
                '</div>' +
                '</div>';
        }).join('');
        if (totalEl) totalEl.textContent = formatPrice(getTotal());
    }

    function animateCartBadge() {
        const badge = document.getElementById('pm-header-cart-count');
        if (!badge) return;
        const total = getTotalItems();
        badge.textContent = total;
        badge.style.display = total > 0 ? '' : 'none';
        badge.classList.remove('pm-badge-bounce');
        void badge.offsetWidth;
        badge.classList.add('pm-badge-bounce');
    }

    // ─── Skeleton Helpers ─────────────────────────────────────────────────────
    function skeletonCardHTML() {
        return '<div class="pm-skeleton-card">' +
            '<div class="pm-skeleton-img"></div>' +
            '<div class="pm-skeleton-body">' +
            '<div class="pm-skeleton-line wide"></div>' +
            '<div class="pm-skeleton-line mid"></div>' +
            '<div class="pm-skeleton-line short"></div>' +
            '</div>' +
            '<div class="pm-skeleton-action"></div>' +
            '</div>';
    }

    function showSkeletons(categoryId, count) {
        const grid = document.getElementById('pm-items-' + categoryId);
        if (!grid) return;
        grid.innerHTML = Array(count || 4).fill(skeletonCardHTML()).join('');
        hideLoadMoreSpinner(categoryId);
    }

    function showLoadMoreSpinner(categoryId) {
        const el = document.getElementById('pm-spinner-' + categoryId);
        if (el) el.classList.add('active');
    }

    function hideLoadMoreSpinner(categoryId) {
        const el = document.getElementById('pm-spinner-' + categoryId);
        if (el) el.classList.remove('active');
    }

    // ─── Product Card HTML Builder ─────────────────────────────────────────────
    function buildProductCardHTML(p) {
        const variantsJson = escHtml(JSON.stringify(p.variants_data || []));
        const images = Array.isArray(p.images) && p.images.length ? p.images : (p.image ? [p.image] : []);

        let imageHtml;
        if (images.length > 1) {
            // Multi-image slideshow
            const slides = images.map(function (url, idx) {
                return '<div class="pm-slide" data-slide-idx="' + idx + '"' + (idx === 0 ? ' style="opacity:1"' : '') + '>' +
                    '<img src="' + escHtml(url) + '" alt="" loading="lazy">' +
                    '</div>';
            }).join('');
            const dots = images.map(function (_, idx) {
                return '<span class="pm-slide-dot' + (idx === 0 ? ' active' : '') + '" data-dot-idx="' + idx + '"></span>';
            }).join('');
            imageHtml = '<div class="pm-slideshow" data-slideshow="true" data-slide-count="' + images.length + '" data-current="0">' +
                slides +
                '<div class="pm-slide-dots">' + dots + '</div>' +
                '</div>';
        } else if (images.length === 1) {
            imageHtml = '<img src="' + escHtml(images[0]) + '" alt="' + escHtml(p.name) + '" loading="lazy">';
        } else {
            imageHtml = '<div class="pm-item-img-placeholder"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg></div>';
        }

        const descHtml = p.short_description
            ? '<div class="pm-item-desc">' + escHtml(p.short_description) + '</div>'
            : '';

        const isOutOfStock = p.qty <= 0;
        const oosClass = isOutOfStock ? ' pm-out-of-stock' : '';
        const oosBadge = isOutOfStock ? '<div class="pm-item-badge-oos">Out of Stock</div>' : '';

        return '<div class="pm-item-card' + oosClass + '" data-name="' + escHtml(p.name) + '">' +
            oosBadge +
            '<div class="pm-item-img-wrap">' + imageHtml + '</div>' +
            '<div class="pm-item-info">' +
            '<div class="pm-item-name">' + escHtml(p.name) + '</div>' +
            descHtml +
            '<div class="pm-item-price">' + formatPrice(p.price) + '</div>' +
            '</div>' +
            '<div class="pm-item-action">' +
            '<button class="pm-add-btn"' +
            ' data-id="' + p.id + '"' +
            ' data-name="' + escHtml(p.name) + '"' +
            ' data-price="' + p.price + '"' +
            ' data-image="' + (images[0] ? escHtml(images[0]) : '') + '"' +
            ' data-has-variants="' + (p.has_variants ? 'true' : 'false') + '"' +
            ' data-variants="' + variantsJson + '"' +
            ' aria-label="Add ' + escHtml(p.name) + ' to cart"' +
            (isOutOfStock ? ' disabled' : '') +
            '>' + (isOutOfStock ? '✕' : '+') + '</button>' +
            '<div class="pm-qty-box">' +
            '<button class="pm-qty-btn pm-qty-minus" data-id="' + p.id + '" aria-label="Decrease">−</button>' +
            '<span class="pm-qty-value">0</span>' +
            '<button class="pm-qty-btn pm-qty-plus" data-id="' + p.id + '" aria-label="Increase">+</button>' +
            '</div>' +
            '</div>' +
            '</div>';
    }

    // ─── Slideshow Initialiser ─────────────────────────────────────────────────
    // Tracks interval IDs so we don't double-start on the same element.
    var _slideshowIntervals = new WeakMap();

    function initSlideshows(container) {
        var root = container || document;
        root.querySelectorAll('.pm-slideshow[data-slideshow="true"]').forEach(function (el) {
            if (_slideshowIntervals.has(el)) return; // already running
            var count   = parseInt(el.dataset.slideCount, 10) || 1;
            var current = 0;

            // Show first slide immediately
            var slides = el.querySelectorAll('.pm-slide');
            if (slides.length) slides[0].style.opacity = '1';

            var timer = setInterval(function () {
                // Hide current
                slides[current].style.opacity = '0';
                var dots = el.querySelectorAll('.pm-slide-dot');
                if (dots[current]) dots[current].classList.remove('active');

                // Advance
                current = (current + 1) % count;

                // Show next
                slides[current].style.opacity = '1';
                if (dots[current]) dots[current].classList.add('active');
            }, 2500);

            _slideshowIntervals.set(el, timer);
        });
    }

    // ─── Append products to the grid ──────────────────────────────────────────
    function appendProducts(categoryId, products) {
        const grid = document.getElementById('pm-items-' + categoryId);
        if (!grid) return;

        const state = categoryState[categoryId];

        // Clear skeletons on first page
        if (state && state.page === 1) {
            grid.innerHTML = '';
        }

        if (products.length === 0 && state && state.page === 1) {
            grid.innerHTML = '<p class="pm-empty-cart" style="padding:24px 0;text-align:center;">No products in this category.</p>';
            return;
        }

        const fragment = document.createDocumentFragment();
        const tmp = document.createElement('div');
        tmp.innerHTML = products.map(buildProductCardHTML).join('');
        while (tmp.firstChild) {
            fragment.appendChild(tmp.firstChild);
        }
        grid.appendChild(fragment);

        // Sync cart button states for newly rendered cards
        renderProductButtons();

        // Boot slideshows for any multi-image cards just added
        initSlideshows(grid);
    }

    // ─── AJAX: Fetch one page of products ────────────────────────────────────
    function fetchPage(categoryId, page) {
        const slug = window.MENU_CONFIG?.slug || '';
        const url = baseUrl + '/menu/' + encodeURIComponent(slug) + '/products' +
            '?category_id=' + categoryId + '&page=' + page;

        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            });
    }

    // ─── Load a category (first page) ────────────────────────────────────────
    function loadCategory(categoryId) {
        if (!categoryState[categoryId]) {
            categoryState[categoryId] = { page: 1, loading: false, loaded: false, finished: false };
        }
        let state = categoryState[categoryId];

        if (state.loaded || state.loading) return;

        state.loading = true;
        showSkeletons(categoryId, 4);

        fetchPage(categoryId, 1)
            .then(function (data) {
                state.page = 1;
                state.loaded = true;
                state.loading = false;

                appendProducts(categoryId, data.data);

                if (data.current_page >= data.last_page) {
                    state.finished = true;
                }

                attachHorizontalScroll(categoryId);
                checkAndFillSpace(categoryId);
            })
            .catch(function (err) {
                state.loading = false;
                console.error('[PublicMenu] Failed to load products:', err);
                const grid = document.getElementById('pm-items-' + categoryId);
                if (grid) grid.innerHTML = '<p style="padding: 10px;">Failed to load products.</p>';
            });
    }

    // ─── Horizontal Infinite Scroll ───────────────────────────────────────────
    function attachHorizontalScroll(categoryId) {
        const list = document.getElementById('pm-items-' + categoryId);
        if (!list) return;

        list.addEventListener('scroll', function () {
            const state = categoryState[categoryId];
            if (!state || state.loading || state.finished) return;

            const nearEnd = list.scrollLeft + list.clientWidth >= list.scrollWidth - 50;
            if (nearEnd) {
                loadMore(categoryId);
            }
        });
    }

    // ─── Load More Products ───────────────────────────────────────────────────
    function loadMore(categoryId) {
        let state = categoryState[categoryId];
        if (!state || state.loading || state.finished) return;

        state.loading = true;
        showLoadMoreSpinner(categoryId);

        fetchPage(categoryId, state.page + 1)
            .then(function (data) {
                state.page++;
                appendProducts(categoryId, data.data);

                if (data.current_page >= data.last_page) {
                    state.finished = true;
                }

                state.loading = false;
                hideLoadMoreSpinner(categoryId);
                checkAndFillSpace(categoryId);
            })
            .catch(function (err) {
                state.loading = false;
                hideLoadMoreSpinner(categoryId);
                console.error('[PublicMenu] Failed to load more products:', err);
            });
    }

    // ─── Auto-fill empty space ────────────────────────────────────────────────
    function checkAndFillSpace(categoryId) {
        setTimeout(function () {
            const list = document.getElementById('pm-items-' + categoryId);
            const state = categoryState[categoryId];
            if (!list || !state || state.loading || state.finished) return;

            // If the grid hasn't overflowed the viewport yet, load more
            if (list.scrollWidth <= list.clientWidth + 50) {
                loadMore(categoryId);
            }
        }, 100);
    }

    // ─── Category Navigation ──────────────────────────────────────────────────
    function initCategoryNav() {
        const tabs = document.querySelectorAll('.pm-cat-tab');
        if (!tabs.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const categoryId = tab.dataset.id;
                const section = document.querySelector('.pm-category-section[data-category-id="' + categoryId + '"]');
                if (section) {
                    const headerH = document.querySelector('.pm-sticky-header')?.offsetHeight || 0;
                    const searchH = document.querySelector('.pm-search-wrap')?.offsetHeight || 0;
                    const catH = document.querySelector('.pm-cat-nav')?.offsetHeight || 0;
                    const y = section.getBoundingClientRect().top + window.scrollY - headerH - searchH - catH - 8;
                    window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
                }
            });
        });
    }

    // ─── Intersection Observer for Categories ─────────────────────────────────
    function initCategoryObserver() {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const categoryId = entry.target.dataset.categoryId;
                    if (categoryId) {
                        loadCategory(categoryId);
                    }
                }
            });
        }, { rootMargin: '200px', threshold: 0 });

        document.querySelectorAll('.pm-category-section').forEach(function (section) {
            observer.observe(section);
        });

        const spyObserver = new IntersectionObserver(function (entries) {
            let intersectingId = null;
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    intersectingId = entry.target.dataset.categoryId;
                }
            });
            if (intersectingId) {
                const tabs = document.querySelectorAll('.pm-cat-tab');
                tabs.forEach(function (t) { t.classList.remove('active'); });
                const activeTab = document.querySelector('.pm-cat-tab[data-id="' + intersectingId + '"]');
                if (activeTab) {
                    activeTab.classList.add('active');
                    activeTab.scrollIntoView({ inline: 'nearest', block: 'nearest' });
                }
            }
        }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });

        document.querySelectorAll('.pm-category-section').forEach(function (section) {
            spyObserver.observe(section);
        });
    }

    // ─── Search ───────────────────────────────────────────────────────────────
    function initSearch() {
        const input = document.getElementById('pm-search-input');
        if (!input) return;

        input.addEventListener('input', function () {
            const q = input.value.toLowerCase().trim();
            document.querySelectorAll('.pm-item-card').forEach(function (card) {
                const name = card.dataset.name || '';
                card.style.display = (!q || name.toLowerCase().includes(q)) ? '' : 'none';
            });

            document.querySelectorAll('.pm-category-section').forEach(function (section) {
                const visibleCards = section.querySelectorAll('.pm-item-card[style=""]').length;
                const hasCards = section.querySelectorAll('.pm-item-card').length > 0;
                // If searching and no visible cards in this section, hide section.
                if (q && visibleCards === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = '';
                }
            });
        });
    }

    // ─── Modal Helpers ────────────────────────────────────────────────────────
    function openCartModal() {
        const modal = document.getElementById('pm-cart-modal');
        if (modal) {
            renderCartModal();
            modal.classList.add('pm-open');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeCartModal() {
        const modal = document.getElementById('pm-cart-modal');
        if (modal) {
            modal.classList.remove('pm-open');
            document.body.style.overflow = '';
        }
    }

    // ─── Variant Modal ────────────────────────────────────────────────────────
    function openVariantModal(btn) {
        let groups = [];
        try { groups = JSON.parse(btn.dataset.variants || '[]'); } catch (e) { }
        if (!groups.length) return;

        selectedProduct = {
            id: btn.dataset.id,
            name: btn.dataset.name,
            basePrice: parsePrice(btn.dataset.price),
            image: btn.dataset.image || ''
        };
        selectedVariants = {};

        const optContainer = document.getElementById('pm-variant-options');
        const addBtn = document.getElementById('pm-variant-add-btn');
        const heading = document.getElementById('pm-variant-heading');
        if (!optContainer || !addBtn) return;

        if (heading) heading.textContent = selectedProduct.name;
        addBtn.disabled = true;

        optContainer.innerHTML = groups.map(function (group, gi) {
            const chips = group.options.map(function (opt) {
                const priceLabel = opt.additional_price > 0
                    ? ' <span class="pm-chip-price">+' + formatPrice(opt.additional_price) + '</span>'
                    : (opt.additional_price < 0
                        ? ' <span class="pm-chip-price">' + formatPrice(opt.additional_price) + '</span>'
                        : '');
                return '<button class="pm-chip"' +
                    ' data-group-idx="' + gi + '"' +
                    ' data-opt-id="' + opt.id + '"' +
                    ' data-opt-name="' + escHtml(opt.name) + '"' +
                    ' data-opt-additional="' + opt.additional_price + '">' +
                    escHtml(opt.name) + priceLabel +
                    '</button>';
            }).join('');

            return '<div class="pm-variant-group">' +
                '<div class="pm-variant-group-label">' + escHtml(group.group) + '</div>' +
                '<div class="pm-variant-chips" data-group-idx="' + gi + '">' + chips + '</div>' +
                '</div>';
        }).join('');

        const modal = document.getElementById('pm-variant-modal');
        if (modal) {
            modal.classList.add('pm-open');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeVariantModal() {
        const modal = document.getElementById('pm-variant-modal');
        if (modal) {
            modal.classList.remove('pm-open');
            document.body.style.overflow = '';
        }
        selectedProduct = null;
        selectedVariants = {};
    }

    function selectChip(chipEl) {
        const groupIdx = chipEl.dataset.groupIdx;
        document.querySelectorAll('.pm-chip[data-group-idx="' + groupIdx + '"]').forEach(function (c) {
            c.classList.remove('selected');
        });
        chipEl.classList.add('selected');
        selectedVariants[groupIdx] = {
            id: chipEl.dataset.optId,
            name: chipEl.dataset.optName,
            additional_price: parseFloat(chipEl.dataset.optAdditional) || 0
        };
        const totalGroups = document.querySelectorAll('.pm-variant-chips').length;
        const selectedCount = Object.keys(selectedVariants).length;
        const addBtn = document.getElementById('pm-variant-add-btn');
        if (addBtn) addBtn.disabled = selectedCount < totalGroups;
    }

    function confirmVariant() {
        if (!selectedProduct) return;
        const totalGroups = document.querySelectorAll('.pm-variant-chips').length;
        if (Object.keys(selectedVariants).length < totalGroups) return;

        const selections = Object.values(selectedVariants);
        const additionalSum = selections.reduce(function (sum, s) { return sum + s.additional_price; }, 0);
        const finalPrice = selectedProduct.basePrice + additionalSum;
        const ids = selections.map(function (s) { return s.id; }).join('_');
        const key = selectedProduct.id + '_' + ids;
        const label = selections.map(function (s) { return s.name; }).join(', ');
        const name = selectedProduct.name + ' (' + label + ')';

        addItem(key, name, finalPrice, selectedProduct.image);
        closeVariantModal();
    }

    // ─── WhatsApp Checkout ────────────────────────────────────────────────────
    // ─── WhatsApp Checkout ────────────────────────────────────────────────────
    function buildWhatsAppMessage(customer, reference) {
        const cfg = window.MENU_CONFIG || {};
        const divider     = '━━━━━━━━━━━━━━━━━━━━━━';
        const thinDivider = '┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄';

        let message = '*NEW ORDER RECEIVED*\n';
        message += divider + '\n\n';

        // ── Customer Info ──
        message += '*CUSTOMER DETAILS*\n';
        message += thinDivider + '\n';
        message += 'Name    : ' + (customer.name || 'N/A') + '\n';
        message += 'Phone   : ' + (customer.phone || 'N/A') + '\n';
        message += 'Address : ' + (customer.address || 'N/A') + '\n';
        if (cfg.table_name) {
            message += 'Table   : ' + cfg.table_name + '\n';
        }
        message += divider + '\n\n';

        // ── Order Items ──
        message += '*ORDER ITEMS*\n';
        message += thinDivider + '\n';
        
        const items = Object.values(cart);
        items.forEach(function (item, index) {
            message += (index + 1) + '. *' + item.name + '*\n';
            message += '    Qty   : ' + item.qty + '\n';
            message += '    Price : ' + formatPrice(item.price) + '\n';
            message += thinDivider + '\n';
        });

        // ── Pricing Summary ──
        message += '\n*PRICE SUMMARY*\n';
        message += thinDivider + '\n';
        message += 'Grand Total: ' + formatPrice(getTotal()) + '\n';
        message += divider + '\n';
        
        if (reference) {
            message += '\n*Order Ref:* #' + reference;
            message += '\n' + divider;
        }

        return message;
    }

    function sendViaWhatsApp() {
        const items = Object.values(cart);
        if (items.length === 0) { alert('Your cart is empty!'); return; }

        const nameField = document.getElementById('pm-name');
        const phoneField = document.getElementById('pm-phone');
        const addressField = document.getElementById('pm-address');

        const name = nameField ? nameField.value.trim() : '';
        const phoneInput = phoneField ? phoneField.value.trim() : '';
        const address = addressField ? addressField.value.trim() : '';

        if (!name || !phoneInput) {
            alert('Please enter your name and phone number to place the order.');
            if (!name && nameField) nameField.focus();
            else if (!phoneInput && phoneField) phoneField.focus();
            return;
        }

        const cfg = window.MENU_CONFIG || {};
        const storePhone = (cfg.whatsapp_number || '').replace(/\D/g, '');
        if (!storePhone) { alert('WhatsApp number not configured for this business.'); return; }

        const btn = document.getElementById('pm-whatsapp-btn');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span>Processing Order...</span>';

        // Prepare data for backend
        const orderData = {
            billing_name: name,
            billing_phone: phoneInput,
            billing_address: address,
            warehouse_id: cfg.warehouse_id,
            item_count: items.length,
            total_qty: items.reduce(function(sum, i) { return sum + i.qty; }, 0),
            sub_total: getTotal(),
            grand_total: getTotal(),
            cart: items.map(function(i) {
                const parts = i.id.toString().split('_');
                return {
                    id: parts[0],
                    variant_id: parts[1] || null, // Simplified: take the first variant ID if present
                    qty: i.qty,
                    price: i.price,
                    name: i.name
                };
            })
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch(cfg.place_order_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                const message = buildWhatsAppMessage({name: name, phone: phoneInput, address: address}, data.reference);
                window.open('https://wa.me/' + storePhone + '?text=' + encodeURIComponent(message), '_blank');
                
                // Success - clear cart
                localStorage.removeItem(CART_KEY);
                for (let k in cart) delete cart[k];
                renderAll();
                closeCartModal();
                
                // Show success notice
                const notice = document.getElementById('pm-end-notice');
                if (notice) {
                    notice.textContent = 'Order #' + data.reference + ' placed successfully!';
                    notice.classList.add('active');
                    setTimeout(function() { notice.classList.remove('active'); }, 5000);
                }
            } else {
                alert('Failed to place order: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('An error occurred while placing your order. Please check your connection and try again.');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    // ─── Event Delegation ─────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const target = e.target;

        if (target.classList.contains('pm-add-btn')) {
            const hasVariants = target.dataset.hasVariants === 'true';
            if (hasVariants) {
                openVariantModal(target);
            } else {
                addItem(target.dataset.id, target.dataset.name, target.dataset.price, target.dataset.image || '');
            }
            return;
        }

        if (target.classList.contains('pm-qty-plus')) {
            const card = target.closest('.pm-item-card');
            const addBtn = card ? card.querySelector('.pm-add-btn') : null;
            if (addBtn) {
                if (addBtn.dataset.hasVariants === 'true') {
                    openVariantModal(addBtn);
                } else {
                    addItem(addBtn.dataset.id, addBtn.dataset.name, addBtn.dataset.price, addBtn.dataset.image || '');
                }
            } else {
                const id = target.dataset.id;
                if (cart[id]) { cart[id].qty += 1; saveCart(); renderAll(); }
            }
            return;
        }

        if (target.classList.contains('pm-qty-minus')) {
            removeItem(target.dataset.id);
            return;
        }

        if (target.classList.contains('pm-cart-plus')) {
            const id = target.closest('[data-id]').dataset.id;
            if (cart[id]) { cart[id].qty += 1; saveCart(); renderAll(); }
            return;
        }

        if (target.classList.contains('pm-cart-minus')) {
            removeItem(target.closest('[data-id]').dataset.id);
            return;
        }

        if (target.classList.contains('pm-remove-btn')) {
            deleteItem(target.dataset.id);
            return;
        }

        if (target.closest('#pm-floating-bar') || target.closest('#pm-cart-icon-btn')) {
            openCartModal();
            return;
        }

        if (target.id === 'pm-cart-modal') { closeCartModal(); return; }
        if (target.id === 'pm-cart-close') { closeCartModal(); return; }

        const chipEl = target.closest('.pm-chip');
        if (chipEl) { selectChip(chipEl); return; }

        if (target.id === 'pm-variant-modal') { closeVariantModal(); return; }
        if (target.id === 'pm-variant-close') { closeVariantModal(); return; }
        if (target.id === 'pm-variant-add-btn') { confirmVariant(); return; }

        if (target.id === 'pm-whatsapp-btn' || target.closest('#pm-whatsapp-btn')) {
            sendViaWhatsApp();
            return;
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeCartModal();
            closeVariantModal();
        }
    });

    // ─── Boot ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        renderFloatingBar();   // Restore cart badge from localStorage
        renderCartModal();     // Pre-fill cart modal
        initCategoryNav();     // Sets up tabs click listeners
        initCategoryObserver();// Sets up lazy load + spy
        initSearch();
    });

})();
