CREATE TABLE IF NOT EXISTS pro_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('admin', 'ceo', 'lab_manager', 'user') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pro_chemicals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    cas_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pro_containers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemical_id INT NOT NULL,
    container_code VARCHAR(80) NOT NULL UNIQUE,
    owner_name VARCHAR(150) NOT NULL,
    qty_remaining DECIMAL(12,2) NOT NULL DEFAULT 0,
    reorder_point DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL DEFAULT 'mL',
    model_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chemical_id) REFERENCES pro_chemicals(id) ON DELETE CASCADE,
    CONSTRAINT chk_qty_non_negative CHECK (qty_remaining >= 0)
);

CREATE TABLE IF NOT EXISTS pro_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    container_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type ENUM('borrow','return') NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (container_id) REFERENCES pro_containers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES pro_users(id) ON DELETE CASCADE,
    CONSTRAINT chk_tx_qty_positive CHECK (quantity > 0)
);

CREATE INDEX idx_pro_containers_chemical_id ON pro_containers (chemical_id);
CREATE INDEX idx_pro_transactions_container_time ON pro_transactions (container_id, created_at);
CREATE INDEX idx_pro_transactions_user_time ON pro_transactions (user_id, created_at);

-- Password123!
INSERT IGNORE INTO pro_users (id, username, full_name, role, password_hash) VALUES
(1, 'admin', 'System Admin', 'admin', '$2y$12$WjM9Da5RAeyl8aoSR307OOIgn//ejLi3Z6Qa/qSj/sahdAI76Bpza'),
(2, 'ceo', 'Executive CEO', 'ceo', '$2y$12$WjM9Da5RAeyl8aoSR307OOIgn//ejLi3Z6Qa/qSj/sahdAI76Bpza'),
(3, 'labmanager', 'Lab Manager', 'lab_manager', '$2y$12$WjM9Da5RAeyl8aoSR307OOIgn//ejLi3Z6Qa/qSj/sahdAI76Bpza'),
(4, 'user', 'Lab User', 'user', '$2y$12$WjM9Da5RAeyl8aoSR307OOIgn//ejLi3Z6Qa/qSj/sahdAI76Bpza');

INSERT IGNORE INTO pro_chemicals (id, name, cas_number) VALUES
(1, 'Hydrochloric Acid', '7647-01-0'),
(2, 'Ethanol', '64-17-5');

INSERT IGNORE INTO pro_containers (id, chemical_id, container_code, owner_name, qty_remaining, reorder_point, unit, model_url) VALUES
(1, 1, 'CHEM-HCL-0001', 'Dr. Anan', 500.00, 120.00, 'mL', 'https://modelviewer.dev/shared-assets/models/Astronaut.glb'),
(2, 2, 'CHEM-ETOH-0001', 'Lab QA Team', 2200.00, 500.00, 'mL', NULL);
