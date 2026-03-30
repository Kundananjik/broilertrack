CREATE DATABASE IF NOT EXISTS broilertrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE broilertrack;

CREATE TABLE IF NOT EXISTS batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(120) NOT NULL,
    breed VARCHAR(80) NOT NULL,
    start_date DATE NOT NULL,
    expected_harvest_date DATE NOT NULL,
    initial_chicks INT UNSIGNED NOT NULL,
    chick_cost DECIMAL(10,2) UNSIGNED NOT NULL,
    total_chick_cost DECIMAL(12,2) UNSIGNED NOT NULL,
    current_alive INT UNSIGNED NOT NULL DEFAULT 0,
    mortality_count INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_batches_start_date (start_date),
    CONSTRAINT chk_batches_initial_chicks_positive CHECK (initial_chicks > 0),
    CONSTRAINT chk_batches_chick_cost_positive CHECK (chick_cost > 0),
    CONSTRAINT chk_batches_total_chick_cost_non_negative CHECK (total_chick_cost >= 0),
    CONSTRAINT chk_batches_alive_mortality_bounds CHECK (current_alive + mortality_count <= initial_chicks)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    date DATE NOT NULL,
    category VARCHAR(80) NOT NULL,
    item_name VARCHAR(160) NOT NULL,
    quantity DECIMAL(10,2) UNSIGNED NOT NULL,
    unit_cost DECIMAL(10,2) UNSIGNED NOT NULL,
    total_cost DECIMAL(12,2) UNSIGNED NOT NULL,
    supplier VARCHAR(120) NULL,
    notes TEXT NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expenses_batch_date (batch_id, date),
    CONSTRAINT chk_expenses_quantity_positive CHECK (quantity > 0),
    CONSTRAINT chk_expenses_unit_cost_positive CHECK (unit_cost > 0),
    CONSTRAINT chk_expenses_total_cost_non_negative CHECK (total_cost >= 0),
    CONSTRAINT fk_expenses_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feed_usage (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    date DATE NOT NULL,
    feed_type VARCHAR(120) NOT NULL,
    feed_kg DECIMAL(10,2) UNSIGNED NOT NULL,
    cost_per_kg DECIMAL(10,2) UNSIGNED NOT NULL,
    total_cost DECIMAL(12,2) UNSIGNED NOT NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feed_usage_batch_date (batch_id, date),
    CONSTRAINT chk_feed_usage_feed_kg_positive CHECK (feed_kg > 0),
    CONSTRAINT chk_feed_usage_cost_per_kg_positive CHECK (cost_per_kg > 0),
    CONSTRAINT chk_feed_usage_total_cost_non_negative CHECK (total_cost >= 0),
    CONSTRAINT fk_feed_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS growth_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    date DATE NOT NULL,
    average_weight_kg DECIMAL(10,3) UNSIGNED NOT NULL,
    birds_sampled INT UNSIGNED NOT NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_growth_records_batch_date (batch_id, date),
    CONSTRAINT chk_growth_records_weight_positive CHECK (average_weight_kg > 0),
    CONSTRAINT chk_growth_records_sampled_positive CHECK (birds_sampled > 0),
    CONSTRAINT fk_growth_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    date DATE NOT NULL,
    birds_sold INT UNSIGNED NOT NULL,
    average_weight_kg DECIMAL(10,3) UNSIGNED NOT NULL,
    price_per_bird DECIMAL(10,2) UNSIGNED NOT NULL,
    total_weight DECIMAL(12,3) UNSIGNED NOT NULL,
    total_revenue DECIMAL(14,2) UNSIGNED NOT NULL,
    paid_amount DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0.00,
    balance_amount DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0.00,
    created_by INT NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    buyer VARCHAR(160) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sales_batch_date (batch_id, date),
    CONSTRAINT chk_sales_birds_sold_positive CHECK (birds_sold > 0),
    CONSTRAINT chk_sales_average_weight_positive CHECK (average_weight_kg > 0),
    CONSTRAINT chk_sales_price_per_bird_positive CHECK (price_per_bird > 0),
    CONSTRAINT chk_sales_total_weight_non_negative CHECK (total_weight >= 0),
    CONSTRAINT chk_sales_total_revenue_non_negative CHECK (total_revenue >= 0),
    CONSTRAINT chk_sales_paid_amount_non_negative CHECK (paid_amount >= 0),
    CONSTRAINT chk_sales_balance_amount_non_negative CHECK (balance_amount >= 0),
    CONSTRAINT chk_sales_paid_not_exceed_revenue CHECK (paid_amount <= total_revenue),
    CONSTRAINT fk_sales_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(14,2) UNSIGNED NOT NULL,
    notes VARCHAR(255) NULL,
    recorded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sales_payments_sale_date (sale_id, payment_date),
    CONSTRAINT chk_sales_payments_amount_positive CHECK (amount > 0),
    CONSTRAINT fk_sales_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_users_role CHECK (role IN ('admin', 'salesperson'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt TIMESTAMP NULL DEFAULT NULL,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_login_attempts_username_ip (username, ip_address),
    INDEX idx_login_attempts_locked_until (locked_until)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(60) NOT NULL,
    module VARCHAR(60) NOT NULL,
    action VARCHAR(40) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id INT NULL,
    details_json JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_logs_created_at (created_at),
    INDEX idx_audit_logs_module_action (module, action),
    INDEX idx_audit_logs_user_id (user_id)
) ENGINE=InnoDB;
