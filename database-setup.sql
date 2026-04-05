CREATE DATABASE IF NOT EXISTS soundvision_db;
USE soundvision_db;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    product_id INT DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS interest_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT DEFAULT NULL,
    model VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    comments TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_submitted_at (submitted_at)
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin','manager','support') DEFAULT 'support',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

CREATE TABLE IF NOT EXISTS visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    tagline VARCHAR(300) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) DEFAULT NULL,
    description TEXT,
    features TEXT,
    image_url VARCHAR(500) DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'fa-spa',
    badge VARCHAR(50) DEFAULT NULL,
    badge_color VARCHAR(50) DEFAULT 'bg-rose-100 text-rose-600',
    gradient VARCHAR(100) DEFAULT 'from-rose-50 to-pink-50',
    status ENUM('active','inactive','coming_soon') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order)
);

CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
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

-- Default admin (password: admin123)
INSERT IGNORE INTO admin_users (username, password_hash, email, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@glowup.bd', 'admin');

-- Site settings
INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'GlowUp Beauty', 'general'),
('site_tagline', 'Premium Skincare & Beauty Products', 'general'),
('site_announcement', 'FREE DELIVERY ACROSS BANGLADESH ON ORDERS ABOVE ৳2,000', 'general'),
('phone_number', '+880 1886-556726', 'contact'),
('phone_raw', '01886556726', 'contact'),
('email', 'info@glowup.bd', 'contact'),
('address', 'Dhaka, Bangladesh', 'contact'),
('bkash_number', '01886556726', 'payment'),
('nagad_number', '01886556726', 'payment'),
('bank_name', 'City Bank', 'payment'),
('bank_account_name', 'ISHRAQ UDDIN CHOWDHURY', 'payment'),
('bank_account_number', '2103833949001', 'payment'),
('bank_routing', '225261732', 'payment'),
('free_delivery_threshold', '2000', 'shipping'),
('hero_title', 'Discover Your Natural Glow', 'homepage'),
('hero_subtitle', 'Premium skincare and beauty products crafted for radiant, healthy skin. Pre-order exclusive collections now available in Bangladesh.', 'homepage'),
('about_title', 'Beauty That Works For You', 'homepage'),
('about_text', 'We bring the finest skincare products from around the world directly to your doorstep in Bangladesh. Every product is carefully selected and verified for authenticity.', 'homepage'),
('facebook_url', '#', 'social'),
('instagram_url', '#', 'social'),
('whatsapp_url', '#', 'social');

-- Seed products
INSERT IGNORE INTO products (name, slug, tagline, price, original_price, description, features, image_url, icon, badge, badge_color, gradient, status, sort_order) VALUES
('Glow Essentials Kit', 'glow-essentials-kit', 'Cleanser + Toner + Moisturizer', 2999.00, 3999.00, 'Transform your daily skincare routine with our carefully curated essentials kit. Each product is formulated with premium ingredients to deliver visible results. Perfect for the humid Bangladesh climate and suitable for all skin types.', '["Gentle Daily Cleanser — removes impurities without stripping natural oils","Hydrating Rose Toner — balances pH and preps skin for moisturizer","SPF 30 Moisturizer — lightweight hydration with sun protection","Natural Ingredients — no parabens, sulfates, or artificial fragrances","Suitable for all skin types — dermatologist tested","Travel-friendly sizes — perfect for your daily routine"]', '', 'fa-spa', 'Bestseller', 'bg-rose-100 text-rose-600', 'from-rose-50 to-pink-50', 'active', 1),
('Advanced Glow Set', 'advanced-glow-set', 'Serum + Eye Cream + Night Repair', 5999.00, 7999.00, 'Take your skincare to the next level with our advanced collection. Featuring powerful serums and targeted treatments for visible transformation.', '["Vitamin C Brightening Serum — fades dark spots and evens skin tone","Anti-Aging Eye Cream — reduces puffiness and fine lines","Overnight Repair Mask — wakes up to glowing, refreshed skin","Dermatologist Tested — safe for sensitive skin","Korean Skincare Technology — advanced formulations","Visible results in 2-4 weeks"]', '', 'fa-magic', 'Coming Soon', 'bg-purple-100 text-purple-600', 'from-purple-50 to-violet-50', 'coming_soon', 2),
('Luxury Glow Collection', 'luxury-glow-collection', 'Complete 7-Step Skincare Ritual', 9999.00, 12999.00, 'The ultimate skincare experience. A complete 7-step ritual with premium ingredients, luxury packaging, and a personal skincare consultation.', '["Full 7-Step Routine Kit — cleanser, toner, essence, serum, eye cream, moisturizer, mask","24K Gold Face Mask — luxurious deep hydration treatment","Premium Jade Roller — reduces puffiness and improves circulation","Luxury Gift Packaging — perfect for gifting or self-care","Exclusive Access — limited edition collection","Personal Skincare Consultation included"]', '', 'fa-gem', 'Premium', 'bg-amber-100 text-amber-600', 'from-amber-50 to-yellow-50', 'coming_soon', 3);
