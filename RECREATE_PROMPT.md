# Full Recreation Prompt: GlowUp Beauty — Smart Cosmetics E-Commerce Website

## Project Overview

Build **GlowUp Beauty** — a premium e-commerce website for beauty and skincare products targeted at the Bangladesh market. It collects pre-orders and interest form submissions for exclusive skincare and cosmetics products, and provides a full admin panel to manage orders, transactions, interests, products, and visitor analytics.

**Tech Stack**: Vanilla HTML/CSS/JS frontend + PHP backend + MySQL database. No build system, no package manager, no framework. Files served directly by Apache/Nginx.

**Local Development**: `php -S localhost:8000`

---

## 1. Database Schema

Create a MySQL database named `soundvision_db` with the following tables:

### Table: `orders`
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    model VARCHAR(50) NOT NULL,
    price VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    order_status ENUM('pending','confirmed','paid','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_email (email),
    INDEX idx_order_status (order_status),
    INDEX idx_created_at (created_at)
);
```

### Table: `interest_submissions`
```sql
CREATE TABLE interest_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    comments TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_submitted_at (submitted_at)
);
```

### Table: `admin_users`
```sql
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin','manager','support') DEFAULT 'support',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);
```
Seed with default admin: username=`admin`, password=`admin123` (bcrypt hashed), email=`admin@soundvision.app`, role=`admin`.

### Table: `visitor_logs`
```sql
CREATE TABLE visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Table: `products`
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    model VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) DEFAULT NULL,
    description TEXT,
    features TEXT,
    image_url VARCHAR(500) DEFAULT NULL,
    status ENUM('active','inactive','coming_soon') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```
Seed with 3 products:
- Sound Vision Basic (`basic`) — ৳2,999 (orig ৳3,999) — `active`
- Sound Vision Pro (`pro`) — ৳5,999 (orig ৳7,999) — `coming_soon`
- Sound Vision Ultra (`ultra`) — ৳9,999 (orig ৳12,999) — `coming_soon`

### Table: `payments` (optional)
```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    amount VARCHAR(20) NOT NULL,
    payment_status ENUM('pending','verified','failed') DEFAULT 'pending',
    payment_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);
```

---

## 2. Backend Configuration

### `config/database.php`
Define DB constants: `DB_HOST=localhost`, `DB_USER=root`, `DB_PASS=toor`, `DB_NAME=soundvision_db`. Provide `getDbConnection()` (returns mysqli connection) and `closeDbConnection($conn)` helpers. All PHP files connecting to DB use `require_once 'config/database.php'`.

---

## 3. Public-Facing Frontend Pages

### Page 1: `index.html` — Model Selection Page
- Dark glassmorphism theme (background: `#0a0a0f`, cards: `bg-white/5`, `backdrop-blur-xl`, `border-white/10`)
- Color palette: Cyan (`#06b6d4`), Purple (`#a855f7`), Pink (`#ec4899`)
- Hero section with logo, tagline, and CTA
- 3 model cards displayed in a grid:
  - **Basic (XV-03)**: ৳10,000 — clickable, cyan accent
  - **Pro**: ৳15,000 — disabled (has `btn-disabled` class + "Coming Soon" badge), purple accent
  - **Ultra**: ৳20,000 — disabled, pink accent
- Each card shows: model name, price, key features list, image, action button
- Clicking Basic: sets `sessionStorage.selectedModel = 'basic'`, navigates to `model-details.html?model=basic`
- Clicking Pro/Ultra: shows alert "Coming Soon"
- Brand slider with seamless infinite loop animation
- Footer with contact info, social links
- On page load: sends `fetch('/track-visit.php', {method:'POST', body:'page=home'})`
- Uses Tailwind CSS via CDN + custom `styles.css`

### Page 2: `model-details.html` — Model Detail View
- Reads `?model=` URL param (defaults to `basic`)
- Populates dynamically from inline `models` JavaScript object:
```js
const models = {
    basic: { id: 'basic', name: 'XV-03 (BASIC)', price: '৳10,000', features: ['Feature 1', 'Feature 2', ...], color: 'cyan', image: 'assets/product4.png' },
    pro: { id: 'pro', name: 'Pro', price: '৳15,000', features: [...], color: 'purple', image: 'assets/product-01.png' },
    ultra: { id: 'ultra', name: 'Ultra', price: '৳20,000', features: [...], color: 'pink', image: 'product-01.png' }
};
```
- Shows: model name, price, image, full features list, description
- Two action buttons:
  - "Pre-Order Now" → navigates to `order-form.html?model=basic`
  - "Express Interest" → opens interest dialog modal
- Interest dialog modal: form with fields `model` (hidden), `name`, `email`, `phone`, `comments` → POSTs to `submit-interest.php`
- Back button to `index.html`
- Tracks visit: `fetch('/track-visit.php', {method:'POST', body:'page=model-details'})`

### Page 3: `order-form.html` — 2-Step Pre-Order Form
- Forces `model=basic` if URL param is Pro/Ultra (redirects or alerts)
- **Progress indicator**: 2 steps with visual progress bar
  - Step 1: Personal Info (active)
  - Step 2: Payment (inactive)
- **Step 1 — Personal Info**:
  - Fields: Full Name (required), Email (required, format validated), Phone (required, Bangladeshi format: `/^(?:\+88)?01\d{9}$/`), Address (required, textarea)
  - "Next" button validates all fields before proceeding
- **Step 2 — Payment Method**:
  - Generates client-side order ID on step transition: format `SV-{year}-{timestamp}-{random}`
  - 3 payment method radio cards:
    - **bKash**: Shows instructions "Send Money to 01886556726", then input for bKash Transaction ID (required)
    - **Nagad**: Shows instructions "Send Money to 01886556726", then input for Nagad Transaction ID (required)
    - **Bank Transfer**: Shows full bank details:
      - Bank: City Bank
      - Account Name: ISHRAQ UDDIN CHOWDHURY
      - Account Number: 2103833949001
      - Routing Number: 225261732
      - Reference: Order ID (displayed with copy button)
      - Input for Bank Transaction/Reference Number (required)
  - "Previous" button goes back to Step 1
  - "Place Pre-Order" button submits form
- **Form Submission**:
  - POSTs to `submit-order.php` via fetch
  - On success: saves response to `sessionStorage.orderData`, redirects to `order-confirmation.html`
  - On error: shows error message
- Back button to `model-details.html`
- Tracks visit: `fetch('/track-visit.php', {method:'POST', body:'page=order-form'})`

### Page 4: `order-confirmation.html` — Success Page
- Reads `sessionStorage.orderData` (JSON string with: model, price, payment_method, order_id, name, email, phone, address, transaction_id)
- If no order data: redirects to `index.html`
- Displays: success message, order summary table, order ID prominently
- If payment method is `bank`: shows bank transfer instructions with account details
- "Print Receipt" button: calls `window.print()`
- "Back to Home" button → `index.html`
- Tracks visit: `fetch('/track-visit.php', {method:'POST', body:'page=order-confirmation'})`

---

## 4. Public Backend Endpoints

### `POST /submit-order.php`
- **Request body** (form-encoded or JSON): `model`, `price`, `name`, `email`, `phone`, `address`, `payment_method`, `transaction_id` (or `nagad_transaction_id` / `bank_transaction_id`)
- **Validation**: All required fields present, email format, phone format
- **Order ID generation**: `SV-{YmdHis}-{6-char-uniqid}` (e.g., `SV-20260405143022-A1B2C3`)
- **Transaction ID normalization**:
  ```php
  if ($payment_method === 'bkash') $final_transaction_id = $transaction_id;
  elseif ($payment_method === 'nagad') $final_transaction_id = $nagad_transaction_id;
  elseif ($payment_method === 'bank') $final_transaction_id = $bank_transaction_id;
  ```
- **Database**: INSERT into `orders` table with prepared statements
- **Emails**:
  - Customer confirmation email to user's email (from: `orders@soundvision.app`): Order details, payment method, transaction ID, delivery timeline, contact info. If bank payment, includes full bank details.
  - Admin notification email to `ceo@soundvision.app` (subject: `🛒 New Pre-Order #SV-...`): Full order details with "ACTION REQUIRED: Verify payment and update order status"
- **Response**: `{"success": true, "message": "Order placed successfully!", "order_id": "SV-..."}`

### `POST /submit-interest.php`
- **Request body**: `model`, `name`, `email`, `phone`, `comments`
- **Database**: INSERT into `interest_submissions` with prepared statements
- **Email**: Confirmation email to submitter (from: `noreply@soundvision.app`, subject: `Sound Vision Smart Glass - Interest Confirmed`)
- **Response**: `{"success": true, "message": "Thank you for your interest! We will contact you soon."}`

### `POST /track-visit.php`
- **Request body**: `page` (sanitized to `[a-zA-Z0-9\-_\.]`)
- **Database**: INSERT into `visitor_logs` with IP address and user agent
- **Response**: `{"success": true}` or `{"success": false}`

---

## 5. Admin Panel (`/admin/`)

### Authentication
- Session-based login (`$_SESSION['admin_logged_in'] = true`)
- `admin/login.php`: Login form with username/password → POSTs to `admin/api/auth.php`
- `admin/logout.php`: `session_destroy()`, redirect to login
- `admin/index.php`: Redirects to login or dashboard based on session
- Password verified with `password_verify()` against bcrypt hashes

### `admin/dashboard.php` — Single-Page Admin UI
- Sidebar navigation with links: Dashboard, Orders, Transactions, Interests, Visitors, Products
- All content loaded dynamically via fetch calls to API endpoints
- Header with admin username and logout button

#### Dashboard Page
- **9 stat cards**: Total Orders, Pending Orders, Interest Forms, Last 7 Days Orders, With Transaction ID, Missing Transaction ID, Total Page Views, Unique Visitors, Visits Today
- **3 bar charts**: Payment Methods with Transactions breakdown, Orders by Status, Orders by Model
- **Recent Transactions table**: Shows latest orders with missing transaction IDs
- **Daily Orders chart**: 30-day bar chart of order counts

#### Orders Management Page
- Full table: Order ID, Customer (name + email), Model, Price, Payment Method, Transaction ID, Status (color-coded badge), Date, Actions
- **Filters**: Status dropdown (all/pending/confirmed/paid/shipped/delivered/cancelled), Payment Method dropdown (all/bkash/nagad/bank), Transaction ID dropdown (all/with/without), free-text search input
- **Actions per row**: View (opens modal), Add Transaction ID (if missing), Delete
- **Detail modal**: Shows all order fields, status dropdown to update, add transaction ID button, delete button
- **Pagination**: limit/offset based
- **Export CSV** link

#### Transactions Management Page
- Table: Order ID, Customer, Payment Method (color-coded badge: bKash=pink, Nagad=orange, Bank=blue), Transaction ID, Amount, Status, Date, Actions
- **Filters**: Payment Method, Transaction ID presence, Date range (from/to), search
- **Actions**: Add/Update Transaction ID (modal), Delete
- **Export CSV** link

#### Interest Forms Management Page
- Table: ID, Name, Email, Phone, Model, Comments (truncated), Date, Actions
- **Filters**: Model dropdown (all/Basic/Pro/Ultra), search
- **Actions**: Delete
- **Export CSV** link

#### Products Management Page
- Table: Order (sort), Name, Model, Price, Original Price, Status badge, Created, Actions
- **Filters**: Status (active/inactive/coming_soon), Model, search
- **Actions**: Add Product (opens modal), Edit (opens modal), Delete
- **Product modal**: Fields — Name, Slug (auto-generated from name), Model, Price, Original Price, Description (textarea), Features (textarea, one per line), Image URL, Status dropdown, Sort Order (number)
- **Export CSV** link

#### Visitor Analytics Page
- **3 stat cards**: Total Page Views, Unique Visitors, Visits Today
- **Page breakdown table**: Page name, Total Views, Unique Visitors
- **Daily visits bar chart**: Last 30 days
- **Reset Stats** button: DELETE to `/admin/api/visitors.php` (truncates `visitor_logs`)

### Admin Styling (`admin/styles.css`)
- Fixed 260px sidebar with dark gradient (`#1e293b` → `#0f172a`)
- Active nav item has left border accent (cyan)
- Tables in white cards with hover highlighting
- Status badges: pending=yellow, confirmed=blue, paid=green, shipped=indigo, delivered=green, cancelled=red
- Payment badges: bKash=pink (`#e91e63`), Nagad=orange (`#ff9800`), Bank=blue (`#2196f3`)
- Action buttons: View (blue), Edit (green), Delete (red), Update (purple gradient)
- Charts: horizontal bar fills with gradient, daily vertical bars with hover tooltips
- Modals: centered overlay with backdrop blur, max-width 600px
- Forms: clean inputs with focus ring, primary/secondary button styles
- Responsive: sidebar collapses on mobile, tables scroll horizontally

---

## 6. Admin API Endpoints (`/admin/api/`)

All endpoints require `$_SESSION['admin_logged_in'] === true`. All return JSON.

### `GET/POST /admin/api/auth.php`
- **GET**: Returns `{"success": true, "authenticated": true/false, "user": {...}}`
- **POST**: Login with `username` + `password`. Returns `{"success": true, "message": "Login successful", "user": {...}}` or error.

### `GET /admin/api/orders.php`
- **Query params**: `status`, `payment_method`, `has_transaction` (yes/no), `search`, `limit` (default 50), `offset` (default 0)
- **Response**: `{"success": true, "orders": [...], "total": N, "limit": 50, "offset": 0}`

### `POST /admin/api/orders.php`
- **Body**: `order_id`, `action=update_status`, `status`
- **Response**: `{"success": true, "message": "Order status updated successfully", "order": {...}}`

### `PUT /admin/api/orders.php` (via POST with `action=update_transaction`)
- **Body**: `order_id`, `action=update_transaction`, `transaction_id`
- **Response**: `{"success": true, "message": "Transaction ID updated successfully", "order": {...}}`

### `DELETE /admin/api/orders.php`
- **Body**: `order_id`
- **Response**: `{"success": true, "message": "Order deleted successfully"}`

### `GET /admin/api/interests.php`
- **Query params**: `model`, `search`, `limit`, `offset`
- **Response**: `{"success": true, "interests": [...], "total": N, "limit": 50, "offset": 0}`

### `DELETE /admin/api/interests.php`
- **Body**: `id`
- **Response**: `{"success": true, "message": "Interest submission deleted successfully"}`

### `GET /admin/api/stats.php`
- **Response**:
```json
{
  "success": true,
  "stats": {
    "total_orders": 42,
    "total_interests": 15,
    "recent_orders": 5,
    "orders_by_status": {"pending": 10, "confirmed": 5, ...},
    "orders_by_model": {"Basic": 30, "Pro": 10, "Ultra": 2},
    "interests_by_model": {...},
    "orders_by_payment": {"bkash": 20, "nagad": 15, "bank": 7},
    "daily_orders": [{"date": "2026-04-01", "count": 3}, ...],
    "transaction_stats": {"with_transaction": 35, "without_transaction": 7},
    "payment_with_transaction": {
      "bkash": {"total": 20, "with_transaction": 18, "without_transaction": 2}
    },
    "recent_transactions": [{"order_id": "...", "payment_method": "bkash", "transaction_id": "...", "created_at": "..."}, ...]
  }
}
```

### `GET /admin/api/transaction-details.php`
- **Query params**: `payment_method`, `has_transaction`, `date_from`, `date_to`, `search`, `limit`, `offset`
- **Response**: `{"success": true, "transactions": [...], "total": N, "payment_stats": {...}}`

### `PUT /admin/api/transaction-details.php`
- **Body**: `order_id`, `transaction_id`
- Validates transaction ID format: `/^[A-Z0-9\-_]+$/i`
- Checks for duplicate transaction IDs
- **Response**: `{"success": true, "message": "Transaction ID updated successfully", "order": {...}}`

### `GET /admin/api/products.php`
- **Query params**: `status`, `model`, `search`
- **Response**: `{"success": true, "products": [...], "total": N}`

### `POST /admin/api/products.php`
- **Body**: `name`, `slug`, `model`, `price`, `original_price`, `description`, `features` (JSON array string), `image_url`, `status`, `sort_order`
- Also supports `action=update_status` and `action=update_sort` sub-actions
- **Response**: `{"success": true, "message": "Product created successfully", "id": N}`

### `PUT /admin/api/products.php`
- **Body**: Same as POST + `id`
- **Response**: `{"success": true, "message": "Product updated successfully"}`

### `DELETE /admin/api/products.php`
- **Body**: `id`
- **Response**: `{"success": true, "message": "Product deleted successfully"}`

### `GET /admin/api/visitors.php`
- **Response**:
```json
{
  "success": true,
  "stats": {
    "total_visits": 150,
    "unique_visitors": 85,
    "today_visits": 12,
    "visits_by_page": [{"page": "home", "visits": 80, "unique_visitors": 50}, ...],
    "daily_visits": [{"date": "2026-04-01", "visits": 5, "unique_visitors": 3}, ...]
  }
}
```

### `DELETE /admin/api/visitors.php`
- **Side effect**: `TRUNCATE TABLE visitor_logs`
- **Response**: `{"success": true, "message": "Visitor stats reset successfully"}`

### `GET /admin/export.php?type=orders|interests|transactions|products&format=csv|json`
- Streams CSV (with UTF-8 BOM) or JSON file download
- Requires admin session

### `GET /admin/backup-database.php`
- Streams full SQL dump as `soundvision_backup_YYYY-MM-DD_HHMMSS.sql`
- Requires admin session

---

## 7. Frontend JavaScript (`script.js`)

### Models Data Object
```js
const models = {
    basic: {
        id: 'basic',
        name: 'XV-03 (BASIC)',
        price: '৳10,000',
        features: ['Open-ear Design', 'Bluetooth 5.3', 'Directional Audio', 'IPX4 Water Resistant', '8hr Battery Life', 'Touch Controls'],
        color: 'cyan',
        image: 'assets/product4.png'
    },
    pro: {
        id: 'pro',
        name: 'Pro',
        price: '৳15,000',
        features: ['All Basic Features', 'Active Noise Cancellation', '12hr Battery', 'Premium Build', 'Voice Assistant'],
        color: 'purple',
        image: 'assets/product-01.png'
    },
    ultra: {
        id: 'ultra',
        name: 'Ultra',
        price: '৳20,000',
        features: ['All Pro Features', 'AR Display', 'Health Monitoring', '16hr Battery', 'Titanium Frame'],
        color: 'pink',
        image: 'product-01.png'
    }
};
```

### Key Functions
- `handleModelSelect(modelId)` — Routes to model-details.html or shows "Coming Soon" alert
- `loadModelDetails(modelId)` — Populates model-details.html DOM elements
- `loadOrderForm(modelId)` — Populates order-form.html DOM elements
- `showInterestDialog()` / `hideInterestDialog()` — Toggle interest form modal
- `nextStep()` / `prevStep()` / `goBack()` — Order form step navigation with progress indicator updates
- `validateOrderForm()` — Validates all fields (email format, Bangladeshi phone format, required fields)
- Order form submit handler — POSTs to `submit-order.php`, stores response in sessionStorage, redirects to confirmation
- Interest form submit handler — POSTs to `submit-interest.php`, shows success/error
- Brand slider animation — Duplicates content for seamless infinite loop
- Dynamic CSS injection for `.btn-disabled` styles

### State Passing Between Pages
- `sessionStorage.selectedModel` — the selected model ID
- `sessionStorage.orderData` — JSON string with full order details after submission
- URL param `?model=` used for model-details and order-form pages

---

## 8. CSS/Styling

### Frontend (`styles.css`)
- **Theme**: Dark glassmorphism — `#0a0a0f` background, `bg-white/5` cards, `backdrop-blur-xl`, `border-white/10`
- **Color palette**: Cyan (`#06b6d4`), Purple (`#a855f7`), Pink (`#ec4899`)
- **Background patterns**: Grid pattern (72px cells), radial gradient overlays
- **Animations**:
  - `pulse-slow` (4s ease-in-out infinite)
  - `fade-in` (1s ease-out)
  - `fade-in-up` (0.6s ease-out)
  - `scale-in` (0.5s cubic-bezier)
  - `slide` (40s linear infinite — brand slider)
- **Custom scrollbar**: 8px wide, cyan thumb
- **Step indicators**: Active = cyan-to-purple gradient, Completed = green gradient
- **Disabled buttons**: Gray gradient, `cursor: not-allowed`, `opacity: 0.7`
- **Responsive**: Mobile-first with `sm:`, `md:`, `lg:` Tailwind breakpoints
- **Staggered card animations**: `data-delay="0"`, `"200"`, `"400"` → 0.2s, 0.4s, 0.6s delays

---

## 9. External Dependencies

| Dependency | URL | Used In |
|------------|-----|---------|
| **Tailwind CSS** | `https://cdn.tailwindcss.com` | All 4 frontend HTML pages |
| **Font Awesome 6.4.0** | `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css` | Admin dashboard |
| **PHP** | Built-in | All backend logic |
| **MySQLi** | PHP extension | All database operations |
| **PHP `mail()`** | Built-in | Email notifications |

---

## 10. Order Status Lifecycle

```
pending → confirmed → paid → shipped → delivered
   ↓
cancelled (from any state)
```

- **`pending`**: Default state when order is placed
- **`confirmed`**: Admin has verified the order
- **`paid`**: Payment has been verified
- **`shipped`**: Order has been dispatched
- **`delivered`**: Order has been received by customer
- **`cancelled`**: Order has been cancelled (can happen from any state)

---

## 11. Payment Methods (Bangladesh)

### bKash
- Send Money to: `01886556726`
- User enters bKash transaction ID from SMS confirmation
- Form field: `transaction_id`

### Nagad
- Send Money to: `01886556726`
- User enters Nagad transaction ID
- Form field: `nagad_transaction_id`

### Bank Transfer
- Bank: City Bank
- Account Name: ISHRAQ UDDIN CHOWDHURY
- Account Number: `2103833949001`
- Routing Number: `225261732`
- Reference: Order ID
- Form field: `bank_transaction_id`

---

## 12. File Structure

```
/
├── index.html                    # Model selection page
├── model-details.html            # Model detail view
├── order-form.html               # 2-step pre-order form
├── order-confirmation.html       # Success page
├── script.js                     # Shared frontend JS
├── styles.css                    # Frontend custom CSS
├── submit-order.php              # Order submission endpoint
├── submit-interest.php           # Interest form endpoint
├── track-visit.php               # Visit tracking endpoint
├── database-setup.sql            # Full SQL schema + seed data
├── config/
│   ├── database.php              # DB config + connection helpers
│   └── index.php                 # 403 Forbidden guard
├── admin/
│   ├── index.php                 # Entry point (redirect to login/dashboard)
│   ├── login.php                 # Admin login UI
│   ├── logout.php                # Logout handler
│   ├── dashboard.php             # Single-page admin UI
│   ├── script.js                 # Admin JS (navigation, CRUD, charts)
│   ├── styles.css                # Admin panel CSS
│   ├── export.php                # CSV/JSON export handler
│   ├── backup-database.php       # SQL dump generator
│   ├── check-setup.php           # Dev setup verification
│   ├── create-password.php       # Password hash generator
│   └── api/
│       ├── index.php             # 403 Forbidden guard
│       ├── auth.php              # Auth check + login
│       ├── orders.php            # CRUD orders
│       ├── interests.php         # List/delete interests
│       ├── stats.php             # Dashboard statistics
│       ├── transaction-details.php # Transaction management
│       ├── products.php          # CRUD products
│       └── visitors.php          # Visitor analytics
└── assets/
    ├── product4.png              # Basic model image
    ├── product-01.png            # Pro/Ultra model image
    └── product.png               # Generic product image
```

---

## 13. Key Conventions

- All PHP files connecting to DB use `require_once 'config/database.php'` (paths relative to file location — admin API files use `../../config/database.php`)
- Prepared statements with `bind_param` throughout; no raw string interpolation into SQL
- Input sanitization uses `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` before DB writes
- Admin API endpoints do not re-sanitize on read (data returned as JSON)
- All API responses follow `{"success": true/false, ...}` pattern
- Order ID format: `SV-{YmdHis}-{6-char-uniqid}`
- Frontend uses Tailwind CSS via CDN + custom CSS in `styles.css`
- Admin panel uses Font Awesome icons + custom CSS in `admin/styles.css`
- No React/TypeScript files are used — ignore `/components/`, `App.tsx`, `main.tsx` (leftover from Figma export)

---

## 14. Admin Panel JavaScript (`admin/script.js`)

- **SPA-style navigation** between Dashboard, Orders, Transactions, Interests, Visitors, Products pages
- **Dashboard**: `loadDashboard()` fetches stats + visitor data, renders charts (payment-transaction, status, model, daily), recent transactions table
- **Orders**: `loadOrders()` with search + status + payment + transaction filters, `renderOrders()`, `viewOrder()` modal, `updateOrderStatus()`, `deleteOrder()`
- **Transactions**: `loadTransactions()` with payment + status + date range + search filters, `renderTransactions()`, `openTransactionModal()`, `submitTransactionUpdate()`
- **Interests**: `loadInterests()` with model + search filters, `renderInterests()`, `deleteInterest()`
- **Products**: `loadProducts()`, `renderProducts()`, `openProductModal()`, `editProduct()`, `submitProductForm()`, `deleteProduct()`
- **Visitors**: `loadVisitors()` with page breakdown table + daily bar chart, `resetVisitors()` (DELETE api)
- **Utilities**: `formatDate()`, `getPaymentMethodName()`, `showError()`, `showSuccess()`, `closeModal()`

---

## 15. Email Notifications

### Customer Order Confirmation
- **To**: Customer's email
- **Subject**: `Sound Vision Smart Glass - Order Confirmation #SV-...`
- **From**: `orders@soundvision.app`
- **Content**: Order details, payment method, transaction ID, delivery timeline, contact info. If bank payment, includes full bank account details.

### Admin Order Notification
- **To**: `ceo@soundvision.app`
- **Subject**: `🛒 New Pre-Order #SV-...`
- **Content**: Full order details with "ACTION REQUIRED: Verify payment and update order status"

### Interest Confirmation
- **To**: Submitter's email
- **Subject**: `Sound Vision Smart Glass - Interest Confirmed`
- **From**: `noreply@soundvision.app`
- **Content**: Thank you message with model name

---

## Implementation Notes

1. Start with database schema creation and seed data
2. Create `config/database.php` with connection helpers
3. Build public frontend pages (index → model-details → order-form → confirmation)
4. Implement public backend endpoints (submit-order, submit-interest, track-visit)
5. Build admin authentication (login/logout)
6. Build admin API endpoints (auth, orders, interests, stats, transactions, products, visitors)
7. Build admin dashboard UI with dynamic content loading
8. Add export and backup functionality
9. Test full user flow: select model → view details → place order → receive confirmation
10. Test admin flow: login → view dashboard → manage orders → export data
