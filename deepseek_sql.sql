-- database.sql
CREATE DATABASE IF NOT EXISTS gmao_db;
USE gmao_db;

-- Table des équipements
CREATE TABLE equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(100),
    location VARCHAR(200),
    supplier VARCHAR(100),
    purchase_date DATE,
    warranty_end DATE,
    technical_specs TEXT,
    qr_code VARCHAR(255),
    status ENUM('active', 'maintenance', 'broken', 'retired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des maintenances préventives
CREATE TABLE preventive_maintenance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT,
    frequency_days INT,
    last_done DATE,
    next_due DATE,
    instructions TEXT,
    assigned_team VARCHAR(100),
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

-- Table des interventions
CREATE TABLE interventions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT,
    type ENUM('corrective', 'preventive', 'emergency') DEFAULT 'corrective',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    title VARCHAR(200) NOT NULL,
    description TEXT,
    reported_by VARCHAR(100),
    assigned_to VARCHAR(100),
    start_date DATETIME,
    end_date DATETIME,
    duration_hours DECIMAL(5,2),
    cost DECIMAL(10,2),
    parts_used TEXT,
    resolution_notes TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id)
);

-- Table des pièces détachées
CREATE TABLE spare_parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200),
    quantity INT DEFAULT 0,
    min_quantity INT DEFAULT 5,
    location VARCHAR(100),
    supplier VARCHAR(100),
    unit_price DECIMAL(10,2),
    last_restock DATE
);

-- Table des mouvements de stock
CREATE TABLE stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part_id INT,
    movement_type ENUM('in', 'out'),
    quantity INT,
    intervention_id INT,
    reason TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES spare_parts(id),
    FOREIGN KEY (intervention_id) REFERENCES interventions(id)
);

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100),
    role ENUM('admin', 'supervisor', 'technician', 'viewer') DEFAULT 'technician',
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion d'un admin par défaut (password: admin123)
INSERT INTO users (username, password, fullname, role, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'admin', 'admin@gmao.com');