const API_BASE = 'api';
let currentPage = 'dashboard';
let ordersOffset = 0;
let interestsOffset = 0;
let transactionsOffset = 0;
const LIMIT = 50;

function loadPage(page) {
    currentPage = page;
    document.querySelectorAll('.nav-item[data-page]').forEach(item => {
        item.classList.toggle('active', item.dataset.page === page);
    });

    const titles = {
        dashboard: 'Dashboard',
        orders: 'Orders Management',
        transactions: 'Transactions Management',
        interests: 'Interest Forms',
        products: 'Products Management',
        visitors: 'Visitor Analytics',
        settings: 'Site Settings'
    };
    document.getElementById('page-title').textContent = titles[page] || 'Dashboard';

    switch (page) {
        case 'dashboard': loadDashboard(); break;
        case 'orders': loadOrders(); break;
        case 'transactions': loadTransactions(); break;
        case 'interests': loadInterests(); break;
        case 'products': loadProducts(); break;
        case 'visitors': loadVisitors(); break;
        case 'settings': loadSettings(); break;
    }
}

document.querySelectorAll('.nav-item[data-page]').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        loadPage(item.dataset.page);
    });
});

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function getPaymentMethodName(method) {
    const names = { bkash: 'bKash', nagad: 'Nagad', bank: 'Bank Transfer' };
    return names[method] || method;
}

function getPaymentBadgeClass(method) {
    const classes = { bkash: 'badge-bkash', nagad: 'badge-nagad', bank: 'badge-bank' };
    return classes[method] || '';
}

function showError(message) {
    const content = document.getElementById('page-content');
    content.innerHTML = `<div class="table-card"><div style="padding:32px;text-align:center;color:#ef4444;"><i class="fas fa-exclamation-triangle" style="font-size:2rem;margin-bottom:12px;display:block;"></i>${message}</div></div>`;
}

function closeModal() {
    document.getElementById('modal-overlay').classList.add('hidden');
}

document.getElementById('modal-overlay').addEventListener('click', (e) => {
    if (e.target.id === 'modal-overlay') closeModal();
});

async function loadDashboard() {
    const content = document.getElementById('page-content');
    content.innerHTML = '<div style="text-align:center;padding:40px;color:#64748b;">Loading...</div>';

    try {
        const [statsRes, visitorsRes] = await Promise.all([
            fetch(`${API_BASE}/stats.php`),
            fetch(`${API_BASE}/visitors.php`)
        ]);
        const statsData = await statsRes.json();
        const visitorsData = await visitorsRes.json();

        if (!statsData.success) { showError('Failed to load stats'); return; }

        const s = statsData.stats;
        const v = visitorsData.success ? visitorsData.stats : { total_visits: 0, unique_visitors: 0, today_visits: 0 };

        content.innerHTML = `
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-value">${s.total_orders}</div><div class="stat-label">Total Orders</div></div>
                <div class="stat-card"><div class="stat-value">${s.orders_by_status?.pending || 0}</div><div class="stat-label">Pending Orders</div></div>
                <div class="stat-card"><div class="stat-value">${s.total_interests}</div><div class="stat-label">Interest Forms</div></div>
                <div class="stat-card"><div class="stat-value">${s.daily_orders?.slice(-7).reduce((sum, d) => sum + d.count, 0) || 0}</div><div class="stat-label">Last 7 Days</div></div>
                <div class="stat-card"><div class="stat-value">${s.transaction_stats?.with_transaction || 0}</div><div class="stat-label">With Transaction ID</div></div>
                <div class="stat-card"><div class="stat-value">${s.transaction_stats?.without_transaction || 0}</div><div class="stat-label">Missing Transaction ID</div></div>
                <div class="stat-card"><div class="stat-value">${v.total_visits}</div><div class="stat-label">Total Page Views</div></div>
                <div class="stat-card"><div class="stat-value">${v.unique_visitors}</div><div class="stat-label">Unique Visitors</div></div>
                <div class="stat-card"><div class="stat-value">${v.today_visits}</div><div class="stat-label">Visits Today</div></div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <h3>Payment Methods with Transactions</h3>
                    <div class="bar-chart" id="chart-payment-txn"></div>
                </div>
                <div class="chart-card">
                    <h3>Orders by Status</h3>
                    <div class="bar-chart" id="chart-status"></div>
                </div>
                <div class="chart-card">
                    <h3>Orders by Model</h3>
                    <div class="bar-chart" id="chart-model"></div>
                </div>
                <div class="chart-card">
                    <h3>Daily Orders (30 Days)</h3>
                    <div class="bar-chart" id="chart-daily"></div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header"><h2>Recent Orders with Missing Transaction IDs</h2></div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Order ID</th><th>Customer</th><th>Payment</th><th>Transaction ID</th><th>Date</th></tr></thead>
                        <tbody id="recent-txn-table"></tbody>
                    </table>
                </div>
            </div>
        `;

        renderBarChart('chart-payment-txn', s.payment_with_transaction || {}, (key, val) => {
            const total = val.total || 0;
            const withTxn = val.with_transaction || 0;
            const pct = total > 0 ? Math.round((withTxn / total) * 100) : 0;
            return { label: getPaymentMethodName(key), value: `${withTxn}/${total}`, pct, color: key === 'bkash' ? '#e91e63' : key === 'nagad' ? '#ff9800' : '#2196f3' };
        });

        renderBarChart('chart-status', s.orders_by_status || {}, (key, val) => {
            const colors = { pending: '#f59e0b', confirmed: '#3b82f6', paid: '#22c55e', shipped: '#6366f1', delivered: '#10b981', cancelled: '#ef4444' };
            return { label: key.charAt(0).toUpperCase() + key.slice(1), value: val, pct: s.total_orders > 0 ? Math.round((val / s.total_orders) * 100) : 0, color: colors[key] || '#64748b' };
        });

        renderBarChart('chart-model', s.orders_by_model || {}, (key, val) => {
            const colors = { basic: '#06b6d4', pro: '#a855f7', ultra: '#ec4899' };
            return { label: key.charAt(0).toUpperCase() + key.slice(1), value: val, pct: s.total_orders > 0 ? Math.round((val / s.total_orders) * 100) : 0, color: colors[key] || '#64748b' };
        });

        renderBarChart('chart-daily', s.daily_orders || {}, (key, val) => {
            const maxCount = Math.max(...(s.daily_orders || []).map(d => d.count), 1);
            return { label: val.date?.slice(5) || key, value: val.count, pct: Math.round((val.count / maxCount) * 100), color: '#f43f5e' };
        }, true);

        const recentTable = document.getElementById('recent-txn-table');
        const recentTxns = s.recent_transactions || [];
        if (recentTxns.length === 0) {
            recentTable.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">No missing transaction IDs</td></tr>';
        } else {
            recentTable.innerHTML = recentTxns.map(t => `
                <tr>
                    <td class="font-mono">${t.order_id}</td>
                    <td>${t.customer || '—'}</td>
                    <td><span class="badge ${getPaymentBadgeClass(t.payment_method)}">${getPaymentMethodName(t.payment_method)}</span></td>
                    <td style="color:#ef4444;">Missing</td>
                    <td>${formatDate(t.created_at)}</td>
                </tr>
            `).join('');
        }
    } catch (err) {
        showError('Failed to load dashboard data');
    }
}

function renderBarChart(containerId, data, transform, reverse = false) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const items = Object.entries(data).map(([key, val]) => transform(key, val));
    const sorted = [...items].sort((a, b) => reverse ? a.pct - b.pct : b.pct - a.pct);
    container.innerHTML = sorted.map(item => `
        <div class="bar-row">
            <span class="bar-label">${item.label}</span>
            <div class="bar-track">
                <div class="bar-fill" style="width:${item.pct}%;background:${item.color};"></div>
                <span class="bar-value">${item.value}</span>
            </div>
        </div>
    `).join('');
}

async function loadOrders() {
    const content = document.getElementById('page-content');
    content.innerHTML = `
        <div class="table-card">
            <div class="table-header">
                <h2>Orders</h2>
                <div class="table-filters">
                    <select id="filter-status"><option value="">All Status</option><option value="pending">Pending</option><option value="confirmed">Confirmed</option><option value="paid">Paid</option><option value="shipped">Shipped</option><option value="delivered">Delivered</option><option value="cancelled">Cancelled</option></select>
                    <select id="filter-payment"><option value="">All Payment</option><option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="bank">Bank</option></select>
                    <select id="filter-txn"><option value="">All Transactions</option><option value="yes">With Transaction ID</option><option value="no">Without Transaction ID</option></select>
                    <input type="text" id="filter-search" placeholder="Search...">
                    <button class="btn btn-export" onclick="exportData('orders')"><i class="fas fa-download"></i> Export CSV</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Order ID</th><th>Customer</th><th>Model</th><th>Price</th><th>Payment</th><th>Transaction ID</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody id="orders-table"></tbody>
                </table>
            </div>
            <div id="orders-pagination" class="pagination"></div>
        </div>
    `;

    ['filter-status', 'filter-payment', 'filter-txn', 'filter-search'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => { ordersOffset = 0; fetchOrders(); });
        document.getElementById(id).addEventListener('input', () => { ordersOffset = 0; fetchOrders(); });
    });

    fetchOrders();
}

async function fetchOrders() {
    const status = document.getElementById('filter-status').value;
    const payment = document.getElementById('filter-payment').value;
    const txn = document.getElementById('filter-txn').value;
    const search = document.getElementById('filter-search').value;

    const params = new URLSearchParams({ limit: LIMIT, offset: ordersOffset });
    if (status) params.set('status', status);
    if (payment) params.set('payment_method', payment);
    if (txn) params.set('has_transaction', txn);
    if (search) params.set('search', search);

    try {
        const res = await fetch(`${API_BASE}/orders.php?${params}`);
        const data = await res.json();
        if (!data.success) { showError('Failed to load orders'); return; }
        renderOrders(data);
    } catch (err) { showError('Failed to load orders'); }
}

function renderOrders(data) {
    const tbody = document.getElementById('orders-table');
    if (!data.orders || data.orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:24px;">No orders found</td></tr>';
    } else {
        tbody.innerHTML = data.orders.map(o => `
            <tr>
                <td class="font-mono text-sm">${o.order_id}</td>
                <td><div class="font-medium">${o.name}</div><div class="text-xs text-gray-500">${o.email}</div></td>
                <td>${o.model}</td>
                <td>${o.price}</td>
                <td><span class="badge ${getPaymentBadgeClass(o.payment_method)}">${getPaymentMethodName(o.payment_method)}</span></td>
                <td class="font-mono text-sm">${o.transaction_id || '<span style="color:#ef4444;">Missing</span>'}</td>
                <td><span class="badge badge-${o.order_status}">${o.order_status}</span></td>
                <td class="text-sm">${formatDate(o.created_at)}</td>
                <td>
                    <button class="btn btn-view" onclick="viewOrder('${o.order_id}')"><i class="fas fa-eye"></i></button>
                    ${!o.transaction_id ? `<button class="btn btn-update" onclick="openAddTransactionModal('${o.order_id}')"><i class="fas fa-plus"></i></button>` : ''}
                    <button class="btn btn-delete" onclick="deleteOrder('${o.order_id}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    }

    const totalPages = Math.ceil(data.total / LIMIT);
    const currentPg = Math.floor(ordersOffset / LIMIT) + 1;
    document.getElementById('orders-pagination').innerHTML = `
        <button onclick="ordersOffset=Math.max(0,ordersOffset-LIMIT);fetchOrders();" ${currentPg <= 1 ? 'disabled' : ''}>Previous</button>
        <span class="page-info">Page ${currentPg} of ${totalPages || 1} (${data.total} total)</span>
        <button onclick="ordersOffset+=LIMIT;fetchOrders();" ${currentPg >= totalPages ? 'disabled' : ''}>Next</button>
    `;
}

async function viewOrder(orderId) {
    try {
        const res = await fetch(`${API_BASE}/orders.php?search=${encodeURIComponent(orderId)}&limit=1`);
        const data = await res.json();
        if (!data.success || !data.orders.length) return;
        const o = data.orders[0];

        document.getElementById('modal-content').innerHTML = `
            <div class="modal-header"><h2>Order Details</h2><button class="modal-close" onclick="closeModal()">&times;</button></div>
            <div class="modal-form">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                    <div><label>Order ID</label><div class="font-mono">${o.order_id}</div></div>
                    <div><label>Status</label>
                        <select id="modal-status" class="w-full">
                            <option value="pending" ${o.order_status==='pending'?'selected':''}>Pending</option>
                            <option value="confirmed" ${o.order_status==='confirmed'?'selected':''}>Confirmed</option>
                            <option value="paid" ${o.order_status==='paid'?'selected':''}>Paid</option>
                            <option value="shipped" ${o.order_status==='shipped'?'selected':''}>Shipped</option>
                            <option value="delivered" ${o.order_status==='delivered'?'selected':''}>Delivered</option>
                            <option value="cancelled" ${o.order_status==='cancelled'?'selected':''}>Cancelled</option>
                        </select>
                    </div>
                    <div><label>Name</label><div>${o.name}</div></div>
                    <div><label>Email</label><div>${o.email}</div></div>
                    <div><label>Phone</label><div>${o.phone}</div></div>
                    <div><label>Model</label><div>${o.model}</div></div>
                    <div><label>Price</label><div>${o.price}</div></div>
                    <div><label>Payment</label><div>${getPaymentMethodName(o.payment_method)}</div></div>
                    <div><label>Transaction ID</label><div class="font-mono">${o.transaction_id || '—'}</div></div>
                    <div><label>Date</label><div>${formatDate(o.created_at)}</div></div>
                </div>
                <div style="margin-bottom:16px;"><label>Address</label><div>${o.address}</div></div>
                <div class="modal-actions">
                    <button class="btn-cancel" onclick="closeModal()">Close</button>
                    <button class="btn-submit" onclick="updateOrderStatus('${o.order_id}')">Update Status</button>
                    ${!o.transaction_id ? `<button class="btn-update" onclick="closeModal();openAddTransactionModal('${o.order_id}')">Add Transaction ID</button>` : ''}
                </div>
            </div>
        `;
        document.getElementById('modal-overlay').classList.remove('hidden');
    } catch (err) { showError('Failed to load order'); }
}

async function updateOrderStatus(orderId) {
    const status = document.getElementById('modal-status').value;
    try {
        const formData = new URLSearchParams({ order_id: orderId, action: 'update_status', status });
        const res = await fetch(`${API_BASE}/orders.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) { closeModal(); fetchOrders(); } else { alert(data.message); }
    } catch (err) { alert('Failed to update status'); }
}

function openAddTransactionModal(orderId) {
    document.getElementById('modal-content').innerHTML = `
        <div class="modal-header"><h2>Add Transaction ID</h2><button class="modal-close" onclick="closeModal()">&times;</button></div>
        <div class="modal-form">
            <div class="form-group"><label>Order ID</label><div class="font-mono">${orderId}</div></div>
            <div class="form-group"><label>Transaction ID</label><input type="text" id="modal-txn-id" placeholder="Enter transaction ID"></div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-submit" onclick="submitTransactionUpdate('${orderId}')">Update</button>
            </div>
        </div>
    `;
    document.getElementById('modal-overlay').classList.remove('hidden');
}

async function submitTransactionUpdate(orderId) {
    const txnId = document.getElementById('modal-txn-id').value.trim();
    if (!txnId) { alert('Transaction ID is required'); return; }
    try {
        const formData = new URLSearchParams({ order_id: orderId, action: 'update_transaction', transaction_id: txnId });
        const res = await fetch(`${API_BASE}/orders.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) { closeModal(); if (currentPage === 'orders') fetchOrders(); else if (currentPage === 'transactions') fetchTransactions(); }
        else { alert(data.message); }
    } catch (err) { alert('Failed to update transaction'); }
}

async function deleteOrder(orderId) {
    if (!confirm('Are you sure you want to delete this order?')) return;
    try {
        const formData = new URLSearchParams({ order_id: orderId });
        const res = await fetch(`${API_BASE}/orders.php`, { method: 'DELETE', body: formData });
        const data = await res.json();
        if (data.success) { fetchOrders(); } else { alert(data.message); }
    } catch (err) { alert('Failed to delete order'); }
}

async function loadTransactions() {
    const content = document.getElementById('page-content');
    content.innerHTML = `
        <div class="table-card">
            <div class="table-header">
                <h2>Transactions</h2>
                <div class="table-filters">
                    <select id="filter-txn-payment"><option value="">All Payment</option><option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="bank">Bank</option></select>
                    <select id="filter-txn-status"><option value="">All</option><option value="yes">With Transaction ID</option><option value="no">Without Transaction ID</option></select>
                    <input type="date" id="filter-date-from" title="From Date">
                    <input type="date" id="filter-date-to" title="To Date">
                    <input type="text" id="filter-txn-search" placeholder="Search...">
                    <button class="btn btn-export" onclick="exportData('transactions')"><i class="fas fa-download"></i> Export CSV</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Order ID</th><th>Customer</th><th>Payment</th><th>Transaction ID</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody id="transactions-table"></tbody>
                </table>
            </div>
            <div id="transactions-pagination" class="pagination"></div>
        </div>
    `;

    ['filter-txn-payment', 'filter-txn-status', 'filter-date-from', 'filter-date-to', 'filter-txn-search'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => { transactionsOffset = 0; fetchTransactions(); });
    });

    fetchTransactions();
}

async function fetchTransactions() {
    const payment = document.getElementById('filter-txn-payment').value;
    const hasTxn = document.getElementById('filter-txn-status').value;
    const dateFrom = document.getElementById('filter-date-from').value;
    const dateTo = document.getElementById('filter-date-to').value;
    const search = document.getElementById('filter-txn-search').value;

    const params = new URLSearchParams({ limit: LIMIT, offset: transactionsOffset });
    if (payment) params.set('payment_method', payment);
    if (hasTxn) params.set('has_transaction', hasTxn);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    if (search) params.set('search', search);

    try {
        const res = await fetch(`${API_BASE}/transaction-details.php?${params}`);
        const data = await res.json();
        if (!data.success) { showError('Failed to load transactions'); return; }
        renderTransactions(data);
    } catch (err) { showError('Failed to load transactions'); }
}

function renderTransactions(data) {
    const tbody = document.getElementById('transactions-table');
    if (!data.transactions || data.transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:24px;">No transactions found</td></tr>';
    } else {
        tbody.innerHTML = data.transactions.map(t => `
            <tr>
                <td class="font-mono text-sm">${t.order_id}</td>
                <td><div class="font-medium">${t.name}</div><div class="text-xs text-gray-500">${t.email}</div></td>
                <td><span class="badge ${getPaymentBadgeClass(t.payment_method)}">${getPaymentMethodName(t.payment_method)}</span></td>
                <td class="font-mono text-sm">${t.transaction_id || '<span style="color:#ef4444;">Missing</span>'}</td>
                <td>${t.price}</td>
                <td><span class="badge badge-${t.order_status}">${t.order_status}</span></td>
                <td class="text-sm">${formatDate(t.created_at)}</td>
                <td>
                    <button class="btn btn-update" onclick="openAddTransactionModal('${t.order_id}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-delete" onclick="deleteOrder('${t.order_id}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    }

    const totalPages = Math.ceil(data.total / LIMIT);
    const currentPg = Math.floor(transactionsOffset / LIMIT) + 1;
    document.getElementById('transactions-pagination').innerHTML = `
        <button onclick="transactionsOffset=Math.max(0,transactionsOffset-LIMIT);fetchTransactions();" ${currentPg <= 1 ? 'disabled' : ''}>Previous</button>
        <span class="page-info">Page ${currentPg} of ${totalPages || 1} (${data.total} total)</span>
        <button onclick="transactionsOffset+=LIMIT;fetchTransactions();" ${currentPg >= totalPages ? 'disabled' : ''}>Next</button>
    `;
}

async function loadInterests() {
    const content = document.getElementById('page-content');
    content.innerHTML = `
        <div class="table-card">
            <div class="table-header">
                <h2>Interest Forms</h2>
                <div class="table-filters">
                    <select id="filter-interest-model"><option value="">All Models</option><option value="basic">Basic</option><option value="pro">Pro</option><option value="ultra">Ultra</option></select>
                    <input type="text" id="filter-interest-search" placeholder="Search...">
                    <button class="btn btn-export" onclick="exportData('interests')"><i class="fas fa-download"></i> Export CSV</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Model</th><th>Comments</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody id="interests-table"></tbody>
                </table>
            </div>
            <div id="interests-pagination" class="pagination"></div>
        </div>
    `;

    ['filter-interest-model', 'filter-interest-search'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => { interestsOffset = 0; fetchInterests(); });
        document.getElementById(id).addEventListener('input', () => { interestsOffset = 0; fetchInterests(); });
    });

    fetchInterests();
}

async function fetchInterests() {
    const model = document.getElementById('filter-interest-model').value;
    const search = document.getElementById('filter-interest-search').value;

    const params = new URLSearchParams({ limit: LIMIT, offset: interestsOffset });
    if (model) params.set('model', model);
    if (search) params.set('search', search);

    try {
        const res = await fetch(`${API_BASE}/interests.php?${params}`);
        const data = await res.json();
        if (!data.success) { showError('Failed to load interests'); return; }
        renderInterests(data);
    } catch (err) { showError('Failed to load interests'); }
}

function renderInterests(data) {
    const tbody = document.getElementById('interests-table');
    if (!data.interests || data.interests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:24px;">No interest submissions found</td></tr>';
    } else {
        tbody.innerHTML = data.interests.map(i => `
            <tr>
                <td>${i.id}</td>
                <td class="font-medium">${i.name}</td>
                <td>${i.email}</td>
                <td>${i.phone || '—'}</td>
                <td>${i.model}</td>
                <td class="max-w-[200px] truncate">${i.comments || '—'}</td>
                <td class="text-sm">${formatDate(i.submitted_at)}</td>
                <td><button class="btn btn-delete" onclick="deleteInterest(${i.id})"><i class="fas fa-trash"></i></button></td>
            </tr>
        `).join('');
    }

    const totalPages = Math.ceil(data.total / LIMIT);
    const currentPg = Math.floor(interestsOffset / LIMIT) + 1;
    document.getElementById('interests-pagination').innerHTML = `
        <button onclick="interestsOffset=Math.max(0,interestsOffset-LIMIT);fetchInterests();" ${currentPg <= 1 ? 'disabled' : ''}>Previous</button>
        <span class="page-info">Page ${currentPg} of ${totalPages || 1} (${data.total} total)</span>
        <button onclick="interestsOffset+=LIMIT;fetchInterests();" ${currentPg >= totalPages ? 'disabled' : ''}>Next</button>
    `;
}

async function deleteInterest(id) {
    if (!confirm('Are you sure you want to delete this interest submission?')) return;
    try {
        const formData = new URLSearchParams({ id });
        const res = await fetch(`${API_BASE}/interests.php`, { method: 'DELETE', body: formData });
        const data = await res.json();
        if (data.success) { fetchInterests(); } else { alert(data.message); }
    } catch (err) { alert('Failed to delete interest'); }
}

async function loadProducts() {
    const content = document.getElementById('page-content');
    content.innerHTML = `
        <div class="table-card">
            <div class="table-header">
                <h2>Products</h2>
                <div class="table-filters">
                    <select id="filter-product-status"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="coming_soon">Coming Soon</option></select>
                    <input type="text" id="filter-product-search" placeholder="Search...">
                    <button class="btn btn-primary-action" onclick="openProductModal()"><i class="fas fa-plus"></i> Add Product</button>
                    <button class="btn btn-export" onclick="exportData('products')"><i class="fas fa-download"></i> Export CSV</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Order</th><th>Name</th><th>Tagline</th><th>Price</th><th>Original Price</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody id="products-table"></tbody>
                </table>
            </div>
        </div>
    `;

    ['filter-product-status', 'filter-product-search'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => fetchProducts());
        document.getElementById(id).addEventListener('input', () => fetchProducts());
    });

    fetchProducts();
}

async function fetchProducts() {
    const status = document.getElementById('filter-product-status').value;
    const search = document.getElementById('filter-product-search').value;

    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (search) params.set('search', search);

    try {
        const res = await fetch(`${API_BASE}/products.php?${params}`);
        const data = await res.json();
        if (!data.success) { showError('Failed to load products'); return; }
        renderProducts(data);
    } catch (err) { showError('Failed to load products'); }
}

function renderProducts(data) {
    const tbody = document.getElementById('products-table');
    if (!data.products || data.products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:24px;">No products found</td></tr>';
    } else {
        tbody.innerHTML = data.products.map(p => `
            <tr>
                <td>${p.sort_order}</td>
                <td class="font-medium">${p.name}</td>
                <td class="text-sm text-gray-500 max-w-[200px] truncate">${p.tagline || '—'}</td>
                <td>৳${parseFloat(p.price).toLocaleString()}</td>
                <td>${p.original_price ? '৳' + parseFloat(p.original_price).toLocaleString() : '—'}</td>
                <td><span class="badge badge-${p.status}">${p.status}</span></td>
                <td class="text-sm">${formatDate(p.created_at)}</td>
                <td>
                    <button class="btn btn-edit" onclick="editProduct(${p.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-delete" onclick="deleteProduct(${p.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    }
}

function openProductModal(product = null) {
    const isEdit = !!product;
    const featuresVal = product?.features ? (Array.isArray(product.features) ? product.features.join('\n') : product.features) : '';
    document.getElementById('modal-content').innerHTML = `
        <div class="modal-header"><h2>${isEdit ? 'Edit' : 'Add'} Product</h2><button class="modal-close" onclick="closeModal()">&times;</button></div>
        <div class="modal-form">
            <div class="form-group"><label>Name</label><input type="text" id="product-name" value="${product?.name || ''}" oninput="autoGenerateSlug()"></div>
            <div class="form-group"><label>Slug</label><input type="text" id="product-slug" value="${product?.slug || ''}"></div>
            <div class="form-group"><label>Tagline</label><input type="text" id="product-tagline" value="${product?.tagline || ''}" placeholder="e.g. Cleanser + Toner + Moisturizer"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group"><label>Price (৳)</label><input type="number" id="product-price" value="${product?.price || ''}" step="0.01"></div>
                <div class="form-group"><label>Original Price (৳)</label><input type="number" id="product-original-price" value="${product?.original_price || ''}" step="0.01"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea id="product-description" rows="3">${product?.description || ''}</textarea></div>
            <div class="form-group"><label>Features (one per line)</label><textarea id="product-features" rows="5">${featuresVal}</textarea></div>
            <div class="form-group"><label>Image URL</label><input type="text" id="product-image-url" value="${product?.image_url || ''}" placeholder="e.g. assets/product.png"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group"><label>Icon (FontAwesome)</label><input type="text" id="product-icon" value="${product?.icon || 'fa-spa'}" placeholder="fa-spa"></div>
                <div class="form-group"><label>Badge Text</label><input type="text" id="product-badge" value="${product?.badge || ''}" placeholder="e.g. Bestseller"></div>
                <div class="form-group"><label>Badge Color Class</label><input type="text" id="product-badge-color" value="${product?.badge_color || 'bg-rose-100 text-rose-600'}" placeholder="bg-rose-100 text-rose-600"></div>
            </div>
            <div class="form-group"><label>Gradient Class</label><input type="text" id="product-gradient" value="${product?.gradient || 'from-rose-50 to-pink-50'}" placeholder="e.g. from-rose-50 to-pink-50"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group"><label>Status</label>
                    <select id="product-status">
                        <option value="active" ${product?.status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="inactive" ${product?.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                        <option value="coming_soon" ${product?.status === 'coming_soon' ? 'selected' : ''}>Coming Soon</option>
                    </select>
                </div>
                <div class="form-group"><label>Sort Order</label><input type="number" id="product-sort-order" value="${product?.sort_order || 0}"></div>
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-submit" onclick="submitProductForm(${product?.id || 'null'})">${isEdit ? 'Update' : 'Create'}</button>
            </div>
        </div>
    `;
    document.getElementById('modal-overlay').classList.remove('hidden');
}

function autoGenerateSlug() {
    const name = document.getElementById('product-name').value;
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    document.getElementById('product-slug').value = slug;
}

async function editProduct(id) {
    try {
        const res = await fetch(`${API_BASE}/products.php`);
        const data = await res.json();
        const product = data.products?.find(p => p.id === id);
        if (!product) { alert('Product not found'); return; }
        openProductModal(product);
    } catch (err) { alert('Failed to load product'); }
}

async function submitProductForm(id) {
    const name = document.getElementById('product-name').value.trim();
    const slug = document.getElementById('product-slug').value.trim();
    const tagline = document.getElementById('product-tagline').value.trim();
    const price = document.getElementById('product-price').value;
    const originalPrice = document.getElementById('product-original-price').value;
    const description = document.getElementById('product-description').value.trim();
    const featuresRaw = document.getElementById('product-features').value.trim();
    const features = featuresRaw ? featuresRaw.split('\n').map(f => f.trim()).filter(f => f) : [];
    const imageUrl = document.getElementById('product-image-url').value.trim();
    const icon = document.getElementById('product-icon').value.trim() || 'fa-spa';
    const badge = document.getElementById('product-badge').value.trim();
    const badgeColor = document.getElementById('product-badge-color').value.trim() || 'bg-rose-100 text-rose-600';
    const gradient = document.getElementById('product-gradient').value.trim() || 'from-rose-50 to-pink-50';
    const status = document.getElementById('product-status').value;
    const sortOrder = document.getElementById('product-sort-order').value;

    if (!name || !slug || !price) { alert('Name, slug, and price are required'); return; }

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('slug', slug);
    formData.append('tagline', tagline);
    formData.append('price', price);
    formData.append('original_price', originalPrice || '');
    formData.append('description', description);
    formData.append('features', JSON.stringify(features));
    formData.append('image_url', imageUrl);
    formData.append('icon', icon);
    formData.append('badge', badge);
    formData.append('badge_color', badgeColor);
    formData.append('gradient', gradient);
    formData.append('status', status);
    formData.append('sort_order', sortOrder);

    try {
        const method = id ? 'PUT' : 'POST';
        if (id) formData.append('id', id);
        const res = await fetch(`${API_BASE}/products.php`, { method, body: formData });
        const data = await res.json();
        if (data.success) { closeModal(); fetchProducts(); } else { alert(data.message); }
    } catch (err) { alert('Failed to save product'); }
}

async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    try {
        const formData = new URLSearchParams({ id });
        const res = await fetch(`${API_BASE}/products.php`, { method: 'DELETE', body: formData });
        const data = await res.json();
        if (data.success) { fetchProducts(); } else { alert(data.message); }
    } catch (err) { alert('Failed to delete product'); }
}

async function loadVisitors() {
    const content = document.getElementById('page-content');
    content.innerHTML = '<div style="text-align:center;padding:40px;color:#64748b;">Loading...</div>';

    try {
        const res = await fetch(`${API_BASE}/visitors.php`);
        const data = await res.json();
        if (!data.success) { showError('Failed to load visitor data'); return; }

        const s = data.stats;
        content.innerHTML = `
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-value">${s.total_visits}</div><div class="stat-label">Total Page Views</div></div>
                <div class="stat-card"><div class="stat-value">${s.unique_visitors}</div><div class="stat-label">Unique Visitors</div></div>
                <div class="stat-card"><div class="stat-value">${s.today_visits}</div><div class="stat-label">Visits Today</div></div>
            </div>

            <div class="table-card">
                <div class="table-header"><h2>Page Breakdown</h2></div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Page</th><th>Total Views</th><th>Unique Visitors</th></tr></thead>
                        <tbody>
                            ${(s.visits_by_page || []).map(p => `
                                <tr><td class="font-medium">${p.page}</td><td>${p.visits}</td><td>${p.unique_visitors}</td></tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="chart-card" style="margin-top:24px;">
                <h3>Daily Visits (Last 30 Days)</h3>
                <div class="bar-chart" id="chart-daily-visits"></div>
            </div>

            <div style="margin-top:24px;">
                <button class="btn btn-delete" onclick="resetVisitors()" style="padding:10px 20px;"><i class="fas fa-trash"></i> Reset Stats</button>
            </div>
        `;

        renderBarChart('chart-daily-visits', s.daily_visits || {}, (key, val) => {
            const maxVisits = Math.max(...(s.daily_visits || []).map(d => d.visits), 1);
            return { label: val.date?.slice(5) || key, value: val.visits, pct: Math.round((val.visits / maxVisits) * 100), color: '#f43f5e' };
        }, true);
    } catch (err) { showError('Failed to load visitor data'); }
}

async function resetVisitors() {
    if (!confirm('Are you sure you want to reset all visitor statistics? This cannot be undone.')) return;
    try {
        const res = await fetch(`${API_BASE}/visitors.php`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) { loadVisitors(); } else { alert(data.message); }
    } catch (err) { alert('Failed to reset visitor stats'); }
}

function exportData(type) {
    window.open(`export.php?type=${type}&format=csv`, '_blank');
}

async function loadSettings() {
    const content = document.getElementById('page-content');
    content.innerHTML = '<div style="text-align:center;padding:40px;color:#64748b;">Loading...</div>';

    try {
        const res = await fetch(`${API_BASE}/settings.php`);
        const data = await res.json();
        if (!data.success) { showError('Failed to load settings'); return; }

        const groups = {};
        data.settings.forEach(s => {
            if (!groups[s.setting_group]) groups[s.setting_group] = [];
            groups[s.setting_group].push(s);
        });

        const groupLabels = { general: 'General', contact: 'Contact Info', payment: 'Payment Details', shipping: 'Shipping', homepage: 'Homepage Content', social: 'Social Links' };
        const inputTypes = { phone_number: 'tel', phone_raw: 'tel', email: 'email', free_delivery_threshold: 'number', facebook_url: 'url', instagram_url: 'url', whatsapp_url: 'url' };

        let html = '<form id="settings-form" class="space-y-8">';
        for (const [group, settings] of Object.entries(groups)) {
            html += `<div class="table-card"><div class="table-header"><h2>${groupLabels[group] || group}</h2></div><div style="padding:24px;"><div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">`;
            settings.forEach(s => {
                const type = inputTypes[s.setting_key] || 'text';
                const isTextarea = ['about_text', 'hero_subtitle'].includes(s.setting_key);
                html += `<div class="form-group"><label style="display:block;font-size:0.8125rem;font-weight:500;color:#475569;margin-bottom:4px;">${s.setting_key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}</label>`;
                if (isTextarea) {
                    html += `<textarea id="setting-${s.setting_key}" data-key="${s.setting_key}" rows="3" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;">${s.setting_value || ''}</textarea>`;
                } else {
                    html += `<input type="${type}" id="setting-${s.setting_key}" data-key="${s.setting_key}" value="${s.setting_value || ''}" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;">`;
                }
                html += `</div>`;
            });
            html += `</div></div></div>`;
        }
        html += `<div style="text-align:right;"><button type="submit" class="btn-submit" style="padding:12px 32px;font-size:1rem;">Save All Settings</button></div></form>`;

        content.innerHTML = html;

        document.getElementById('settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const inputs = document.querySelectorAll('[data-key]');
            let success = true;
            for (const input of inputs) {
                try {
                    const formData = new URLSearchParams({ key: input.dataset.key, value: input.value });
                    const res = await fetch(`${API_BASE}/settings.php`, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (!data.success) success = false;
                } catch (err) { success = false; }
            }
            if (success) {
                alert('Settings saved successfully!');
            } else {
                alert('Some settings failed to save.');
            }
        });
    } catch (err) { showError('Failed to load settings'); }
}
