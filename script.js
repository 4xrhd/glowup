let cmsProducts = [];
let cmsSettings = {};

async function loadSiteSettings() {
    try {
        const res = await fetch('/api/settings.php');
        const data = await res.json();
        if (data.success) {
            cmsSettings = data.settings;
            applySettings();
        }
    } catch (e) { console.warn('Settings load failed', e); }
}

function applySettings() {
    const s = cmsSettings;
    if (s.site_name) {
        document.getElementById('site-name').textContent = s.site_name;
        const fn = document.getElementById('footer-site-name');
        if (fn) fn.textContent = s.site_name;
    }
    if (s.site_announcement) {
        const bar = document.getElementById('announcement-bar');
        if (bar) bar.textContent = s.site_announcement;
    }
    if (s.hero_title) {
        const el = document.getElementById('hero-title');
        if (el) el.innerHTML = s.hero_title;
    }
    if (s.hero_subtitle) {
        const el = document.getElementById('hero-subtitle');
        if (el) el.textContent = s.hero_subtitle;
    }
    if (s.about_title) {
        const el = document.getElementById('about-title');
        if (el) el.textContent = s.about_title;
    }
    if (s.about_text) {
        const el = document.getElementById('about-text');
        if (el) el.textContent = s.about_text;
    }
    if (s.free_delivery_threshold) {
        const el = document.getElementById('free-delivery-label');
        if (el) el.textContent = 'Orders over ৳' + Number(s.free_delivery_threshold).toLocaleString();
    }
    if (s.phone_number) {
        const el = document.getElementById('contact-phone');
        if (el) el.textContent = s.phone_number;
    }
    if (s.email) {
        const el = document.getElementById('contact-email');
        if (el) el.textContent = s.email;
    }
    if (s.address) {
        const el = document.getElementById('contact-address');
        if (el) el.textContent = s.address;
    }
}

async function loadHomepageProducts() {
    try {
        const res = await fetch('/api/products.php?status=active');
        const data = await res.json();
        if (data.success && data.products) {
            cmsProducts = data.products;
            renderHomepageProducts(data.products);
        } else {
            document.getElementById('products-grid').innerHTML = '<div class="col-span-full text-center py-12 text-gray-400">No products available</div>';
        }
    } catch (e) {
        document.getElementById('products-grid').innerHTML = '<div class="col-span-full text-center py-12 text-red-400">Failed to load products</div>';
    }
}

function formatPriceBDT(price) {
    return '৳' + Number(price).toLocaleString('en-IN');
}

function calcDiscount(price, original) {
    if (!original || original <= price) return null;
    return Math.round(((original - price) / original) * 100);
}

function featureTitle(f) {
    return f.split(' — ')[0];
}

function featureDesc(f) {
    const parts = f.split(' — ');
    return parts.length > 1 ? parts.slice(1).join(' — ') : null;
}

function renderHomepageProducts(products) {
    const grid = document.getElementById('products-grid');
    if (!products.length) {
        grid.innerHTML = '<div class="col-span-full text-center py-12 text-gray-400">No products available</div>';
        return;
    }

    grid.innerHTML = products.map((p, i) => {
        const features = Array.isArray(p.features) ? p.features : [];
        const discount = calcDiscount(p.price, p.original_price);
        const delay = i * 200;
        const isComingSoon = p.status === 'coming_soon';
        const btnClass = isComingSoon ? 'btn-disabled' : 'bg-gradient-to-r from-rose-500 to-rose-600 text-white hover:shadow-lg hover:shadow-rose-500/30';
        const btnText = isComingSoon ? 'Coming Soon' : 'Pre-Order Now';
        const btnAction = isComingSoon ? `onclick="handleModelSelect('${p.slug}')"` : `onclick="handleModelSelect('${p.slug}')"`;
        const stockBadge = isComingSoon
            ? '<span class="absolute top-3 right-3 px-3 py-1 bg-purple-500 text-white text-xs font-semibold rounded-full">Coming Soon</span>'
            : '<span class="absolute top-3 right-3 px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">In Stock</span>';

        return `
        <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden border border-gray-100" data-delay="${delay}">
            <div class="relative overflow-hidden">
                <div class="w-full h-72 bg-gradient-to-br ${p.gradient || 'from-rose-50 to-pink-50'} flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                    <div class="text-center">
                        ${p.image_url
                            ? `<img src="${p.image_url}" alt="${p.name}" class="max-h-56 object-contain" onerror="this.parentElement.innerHTML='<i class=\\'fas ${p.icon || 'fa-spa'} text-5xl text-gray-300\\'></i>'">`
                            : `<div class="w-32 h-32 mx-auto bg-white rounded-full shadow-md flex items-center justify-center"><i class="fas ${p.icon || 'fa-spa'} text-4xl ${p.badge_color?.includes('rose') ? 'text-rose-400' : p.badge_color?.includes('purple') ? 'text-purple-400' : 'text-amber-400'}"></i></div>`
                        }
                    </div>
                </div>
                ${p.badge ? `<span class="absolute top-3 left-3 px-3 py-1 ${p.badge_color || 'bg-rose-100 text-rose-600'} text-xs font-semibold rounded-full">${p.badge}</span>` : ''}
                ${stockBadge}
            </div>
            <div class="p-6">
                <h3 class="font-display text-lg font-semibold text-gray-900 mb-1">${p.name}</h3>
                ${p.tagline ? `<p class="text-gray-500 text-sm mb-3">${p.tagline}</p>` : ''}
                <div class="flex items-center gap-2 mb-4">
                    <span class="${isComingSoon ? (p.badge_color?.includes('purple') ? 'text-purple-500' : 'text-amber-500') : 'text-rose-500'} font-bold text-xl">${formatPriceBDT(p.price)}</span>
                    ${p.original_price ? `<span class="text-gray-400 line-through text-sm">${formatPriceBDT(p.original_price)}</span>` : ''}
                    ${discount ? `<span class="px-2 py-0.5 bg-green-50 text-green-600 text-xs font-semibold rounded">${discount}% OFF</span>` : ''}
                </div>
                <ul class="text-gray-600 text-sm space-y-1.5 mb-6">
                    ${features.slice(0, 4).map(f => `<li class="flex items-center gap-2"><i class="fas fa-check text-xs ${p.badge_color?.includes('rose') ? 'text-rose-400' : p.badge_color?.includes('purple') ? 'text-purple-400' : 'text-amber-400'}"></i>${featureTitle(f)}</li>`).join('')}
                </ul>
                <button ${btnAction} class="w-full py-3 ${btnClass} font-semibold rounded-xl transition-all duration-300">
                    ${btnText}
                </button>
            </div>
        </div>`;
    }).join('');
}

function handleModelSelect(slug) {
    const product = cmsProducts.find(p => p.slug === slug);
    if (product && product.status === 'coming_soon') {
        alert('Coming Soon! We will notify you when it is available.');
        return;
    }
    window.location.href = `order-form.html?slug=${slug}`;
}

async function loadProductBySlug(slug) {
    try {
        const res = await fetch(`/api/products.php?slug=${slug}`);
        const data = await res.json();
        if (data.success && data.product) return data.product;
        return null;
    } catch (e) { console.error('Product load failed', e); return null; }
}

async function loadOrderForm(slug) {
    const product = cmsProducts.find(p => p.slug === slug) || await loadProductBySlug(slug);
    if (!product) {
        alert('Product not found.');
        window.location.href = 'index.html';
        return;
    }
    if (product.status === 'coming_soon') {
        alert('This product is not yet available for pre-order.');
        window.location.href = 'index.html#products';
        return;
    }

    document.getElementById('order-model').value = product.slug;
    document.getElementById('order-price').value = formatPriceBDT(product.price);
    document.getElementById('order-model-info').textContent = product.name;
    document.getElementById('order-price-display').textContent = formatPriceBDT(product.price);
    document.getElementById('order-product-id').value = product.id;

    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', updatePaymentInstructions);
    });
    updatePaymentInstructions();
    applyPaymentSettings();
}

function applyPaymentSettings() {
    const s = cmsSettings;
    if (s.bkash_number) {
        document.querySelectorAll('#bkash-instructions .font-semibold, #bkash-instructions .text-gray-900.font-semibold').forEach(el => {
            if (el.textContent.match(/\d{11}/)) el.textContent = s.bkash_number;
        });
    }
    if (s.nagad_number) {
        document.querySelectorAll('#nagad-instructions .font-semibold, #nagad-instructions .text-gray-900.font-semibold').forEach(el => {
            if (el.textContent.match(/\d{11}/)) el.textContent = s.nagad_number;
        });
    }
    if (s.bank_name) {
        document.querySelectorAll('#bank-instructions').forEach(el => {
            const bankNameEl = el.querySelector('p');
            if (bankNameEl && bankNameEl.textContent.includes('Bank:')) {
                const html = el.innerHTML;
                el.innerHTML = html.replace(/City Bank/g, s.bank_name)
                    .replace(/ISHRAQ UDDIN CHOWDHURY/g, s.bank_account_name || 'ISHRAQ UDDIN CHOWDHURY')
                    .replace(/2103833949001/g, s.bank_account_number || '2103833949001')
                    .replace(/225261732/g, s.bank_routing || '225261732');
            }
        });
    }
}

function updatePaymentInstructions() {
    const selected = document.querySelector('input[name="payment_method"]:checked').value;
    document.querySelectorAll('.payment-instruction').forEach(el => el.classList.add('hidden'));
    const el = document.getElementById(`${selected}-instructions`);
    if (el) el.classList.remove('hidden');
    if (selected === 'bank') {
        const orderId = document.getElementById('order-id').value;
        if (orderId) document.getElementById('bank-ref-order-id').textContent = orderId;
    }
}

function showInterestDialog() {
    document.getElementById('interest-modal').classList.remove('hidden');
    document.getElementById('interest-modal').classList.add('flex');
}

function hideInterestDialog() {
    document.getElementById('interest-modal').classList.add('hidden');
    document.getElementById('interest-modal').classList.remove('flex');
}

function generateOrderId() {
    const now = new Date();
    const dateStr = now.getFullYear().toString() +
        (now.getMonth() + 1).toString().padStart(2, '0') +
        now.getDate().toString().padStart(2, '0') +
        now.getHours().toString().padStart(2, '0') +
        now.getMinutes().toString().padStart(2, '0') +
        now.getSeconds().toString().padStart(2, '0');
    const random = Math.random().toString(36).substring(2, 8).toUpperCase();
    return `GU-${dateStr}-${random}`;
}

function nextStep() {
    const name = document.getElementById('order-name').value.trim();
    const email = document.getElementById('order-email').value.trim();
    const phone = document.getElementById('order-phone').value.trim();
    const address = document.getElementById('order-address').value.trim();

    if (!name || !email || !phone || !address) {
        showMessage('order-message', 'Please fill in all required fields.', 'error');
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showMessage('order-message', 'Please enter a valid email address.', 'error');
        return;
    }
    if (!/^(?:\+88)?01\d{9}$/.test(phone.replace(/[\s-]/g, ''))) {
        showMessage('order-message', 'Please enter a valid Bangladeshi phone number (e.g., 01XXXXXXXXX).', 'error');
        return;
    }

    const orderId = generateOrderId();
    document.getElementById('order-id').value = orderId;
    document.getElementById('order-id-display').textContent = `Order ID: ${orderId}`;
    document.getElementById('bank-ref-order-id').textContent = orderId;

    document.getElementById('step-1').classList.add('hidden');
    document.getElementById('step-2').classList.remove('hidden');
    document.getElementById('step-indicator-1').classList.remove('active');
    document.getElementById('step-indicator-1').classList.add('completed');
    document.getElementById('step-indicator-2').classList.add('active');
    document.getElementById('progress-bar').style.width = '100%';
    document.getElementById('order-message').classList.add('hidden');
}

function prevStep() {
    document.getElementById('step-2').classList.add('hidden');
    document.getElementById('step-1').classList.remove('hidden');
    document.getElementById('step-indicator-2').classList.remove('active');
    document.getElementById('step-indicator-1').classList.add('active');
    document.getElementById('step-indicator-1').classList.remove('completed');
    document.getElementById('progress-bar').style.width = '0%';
}

function validateOrderForm() {
    const pm = document.querySelector('input[name="payment_method"]:checked').value;
    let txn = '';
    if (pm === 'bkash') { txn = document.getElementById('transaction-id').value.trim(); if (!txn) { showMessage('order-message', 'Please enter your bKash Transaction ID.', 'error'); return false; } }
    else if (pm === 'nagad') { txn = document.getElementById('nagad-transaction-id').value.trim(); if (!txn) { showMessage('order-message', 'Please enter your Nagad Transaction ID.', 'error'); return false; } }
    else if (pm === 'bank') { txn = document.getElementById('bank-transaction-id').value.trim(); if (!txn) { showMessage('order-message', 'Please enter your Bank Transaction/Reference Number.', 'error'); return false; } }
    return true;
}

function showMessage(elementId, message, type) {
    const el = document.getElementById(elementId);
    el.textContent = message;
    el.className = `mt-4 text-sm ${type === 'error' ? 'text-red-500' : 'text-green-500'}`;
}

function copyOrderId() {
    const orderId = document.getElementById('order-id').value;
    navigator.clipboard.writeText(orderId).then(() => { alert('Order ID copied!'); }).catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
    const orderForm = document.getElementById('order-form');
    if (orderForm) {
        orderForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!validateOrderForm()) return;

            const pm = document.querySelector('input[name="payment_method"]:checked').value;
            let txn = '';
            if (pm === 'bkash') txn = document.getElementById('transaction-id').value.trim();
            else if (pm === 'nagad') txn = document.getElementById('nagad-transaction-id').value.trim();
            else if (pm === 'bank') txn = document.getElementById('bank-transaction-id').value.trim();

            const formData = new URLSearchParams();
            formData.append('product_id', document.getElementById('order-product-id').value);
            formData.append('model', document.getElementById('order-model').value);
            formData.append('price', document.getElementById('order-price').value);
            formData.append('name', document.getElementById('order-name').value.trim());
            formData.append('email', document.getElementById('order-email').value.trim());
            formData.append('phone', document.getElementById('order-phone').value.trim());
            formData.append('address', document.getElementById('order-address').value.trim());
            formData.append('payment_method', pm);
            formData.append('transaction_id', txn);

            try {
                const response = await fetch('/submit-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                const data = await response.json();
                if (data.success) {
                    sessionStorage.setItem('orderData', JSON.stringify({
                        model: document.getElementById('order-model').value,
                        price: document.getElementById('order-price').value,
                        payment_method: pm,
                        order_id: data.order_id,
                        name: document.getElementById('order-name').value.trim(),
                        email: document.getElementById('order-email').value.trim(),
                        phone: document.getElementById('order-phone').value.trim(),
                        address: document.getElementById('order-address').value.trim(),
                        transaction_id: txn
                    }));
                    window.location.href = 'order-confirmation.html';
                } else {
                    showMessage('order-message', data.message || 'Order submission failed. Please try again.', 'error');
                }
            } catch (err) {
                showMessage('order-message', 'Network error. Please check your connection and try again.', 'error');
            }
        });
    }

    const interestForm = document.getElementById('interest-form');
    if (interestForm) {
        interestForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new URLSearchParams();
            formData.append('model', document.getElementById('interest-model').value);
            formData.append('name', document.getElementById('interest-name').value.trim());
            formData.append('email', document.getElementById('interest-email').value.trim());
            formData.append('phone', document.getElementById('interest-phone').value.trim());
            formData.append('comments', document.getElementById('interest-comments').value.trim());

            try {
                const response = await fetch('/submit-interest.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                const data = await response.json();
                if (data.success) {
                    showMessage('interest-message', data.message, 'success');
                    setTimeout(() => { hideInterestDialog(); interestForm.reset(); }, 2000);
                } else {
                    showMessage('interest-message', data.message || 'Submission failed.', 'error');
                }
            } catch (err) {
                showMessage('interest-message', 'Network error. Please try again.', 'error');
            }
        });
    }
});
