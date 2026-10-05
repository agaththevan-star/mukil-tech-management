-- =======================================================
-- MUKIL TECH - Billing, POS, Inventory & PC Repair System
-- Database: mukiltech
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+
-- Address: Vivekanandanagar, Kilinochchi, Sri Lanka
-- Phone: 077 479 5253 / 075 531 6840
-- Email: mukiltech7@gmail.com
-- =======================================================

CREATE DATABASE IF NOT EXISTS `mukiltech` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mukiltech`;

-- 1. System Settings Table
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(60) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('business_name', 'MUKIL TECH'),
('business_type', 'Computer Sales • PC/Laptop Repair • Accessories'),
('address', 'Vivekanandanagar, Kilinochchi, Sri Lanka'),
('email', 'mukiltech7@gmail.com'),
('phone', '077 479 5253 / 075 531 6840'),
('invoice_prefix', 'MT-2026-'),
('repair_prefix', 'REP-2026-'),
('currency', 'Rs.'),
('currency_code', 'LKR'),
('tax_percentage', '0'),
('footer_text', 'Thank you for choosing MUKIL TECH! We appreciate your business. Genuine parts & warranty guaranteed.'),
('receipt_printer_width', '80mm');

-- 2. Users Table (Admin, Cashier, Technician)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100),
  `phone` VARCHAR(30),
  `role` ENUM('admin', 'cashier', 'technician') NOT NULL DEFAULT 'cashier',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pre-seeded users:
-- admin / admin123
-- cashier / cashier123
-- technician / tech123
INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$nawZT5r1buC893lxHpOXFews1aT4cgMPLx8OJ3P3/qaRk.zyH/zjS', 'System Administrator', 'admin@mukiltech.com', '077 479 5253', 'admin', 'active'),
(2, 'cashier', '$2y$10$Jua8piU5kOdXciuMUkBvC.ogmkZ0bs3uk.Q2wqOSJ7i9CKYe0Vih2', 'Sales Cashier', 'cashier@mukiltech.com', '075 531 6840', 'cashier', 'active'),
(3, 'technician', '$2y$10$B9K0n59rpa0dZr9mpdV03uRXvA2jXzytNZH72LNWZINrwFjw/J6tS', 'Senior Hardware Technician', 'service@mukiltech.com', '077 123 4567', 'technician', 'active');

-- 3. Categories Table
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Computer Parts', 'Motherboards, Processors, Desktop RAM, Power Supplies, Internal Disks'),
(2, 'Laptop Parts', 'Laptop Screens, Laptop Keyboards, Laptop Batteries, Adapters & Fans'),
(3, 'Accessories', 'Bags, Cooling Pads, Mousepads, USB Hubs & Cases'),
(4, 'Networking', 'Wi-Fi Adapters, Routers, LAN Cables, Patch Cords & RJ45 Connectors'),
(5, 'Storage', 'NVMe SSDs, SATA SSDs, External Hard Drives & USB Flash Drives'),
(6, 'Cables', 'HDMI, VGA, Type-C, Micro USB, Power Cables & DisplayPort Cables'),
(7, 'Peripherals', 'Keyboards, Mice, Headsets, Webcams, Microphones & Desktop Speakers'),
(8, 'Software', 'Windows OS Licenses, Antivirus, Office Suites & Diagnostic Utilities'),
(9, 'Other', 'Thermal Paste, Cleaners, Screws, Tools & Repair Supplies');

-- 4. Suppliers Table
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `supplier_id` INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_name` VARCHAR(100) NOT NULL,
  `company_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30),
  `email` VARCHAR(100),
  `address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `company_name`, `phone`, `email`, `address`) VALUES
(1, 'K. Sivakumar', 'Lanka Tech Distributers', '011 258 7410', 'sales@lankatech.lk', 'Liberty Plaza, Colombo 03'),
(2, 'T. Ravi', 'Northern PC Wholesale', '021 222 3456', 'ravi@northernpc.lk', 'Hospital Road, Jaffna'),
(3, 'M. Nishantha', 'Apex Computer Solutions', '011 432 9876', 'apex@pcparts.lk', 'Unity Plaza, Colombo 04');

-- 5. Customers Table
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `customer_id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(100),
  `address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `customers` (`customer_id`, `customer_name`, `phone`, `email`, `address`) VALUES
(1, 'S. Agaththevan', '077 479 5253', 'agaththevan@gmail.com', 'Vivekanandanagar, Kilinochchi'),
(2, 'K. Thivakar', '077 890 1234', 'thivakar@gmail.com', 'Kandavalai, Kilinochchi'),
(3, 'M. Vimalan', '076 543 2198', 'vimalan@gmail.com', 'A9 Road, Paranthan'),
(4, 'R. Selvarajah', '075 678 9012', 'selva@yahoo.com', 'Station Road, Kilinochchi'),
(5, 'Walk-in Customer', '000 000 0000', '', 'Kilinochchi');

-- 6. Products Table
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_name` VARCHAR(150) NOT NULL,
  `category_id` INT,
  `brand` VARCHAR(50),
  `model` VARCHAR(60),
  `sku` VARCHAR(50) UNIQUE,
  `purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `minimum_stock` INT NOT NULL DEFAULT 5,
  `supplier_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cat` (`category_id`),
  INDEX `idx_supp` (`supplier_id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`product_id`, `product_name`, `category_id`, `brand`, `model`, `sku`, `purchase_price`, `selling_price`, `stock_quantity`, `minimum_stock`, `supplier_id`) VALUES
(1, '8GB DDR4 3200MHz Desktop RAM', 1, 'Kingston', 'Fury Beast', 'RAM-DDR4-8GB', 6500.00, 8500.00, 18, 5, 1),
(2, '16GB DDR4 3200MHz Desktop RAM', 1, 'Corsair', 'Vengeance LPX', 'RAM-DDR4-16GB', 11000.00, 14500.00, 12, 4, 1),
(3, '256GB NVMe M.2 SSD', 5, 'Lexar', 'NM620 Gen3', 'SSD-NVME-256GB', 5800.00, 7800.00, 14, 5, 1),
(4, '512GB NVMe M.2 SSD', 5, 'Kingston', 'NV2 Gen4', 'SSD-NVME-512GB', 9200.00, 12000.00, 8, 4, 1),
(5, '1TB 3.5\" SATA Internal HDD', 5, 'Seagate', 'Barracuda 7200RPM', 'HDD-SEA-1TB', 13500.00, 17500.00, 6, 3, 2),
(6, 'Wireless Optical Mouse', 7, 'Logitech', 'M185 Compact', 'MOU-LOG-M185', 2800.00, 3900.00, 25, 6, 1),
(7, 'Mechanical Gaming Keyboard RGB', 7, 'Fantech', 'Atom MK886', 'KB-FAN-MK886', 5800.00, 7900.00, 9, 3, 2),
(8, 'Over-Ear Gaming Headset with Mic', 7, 'Havit', 'H2002d', 'HST-HAV-2002', 4500.00, 6200.00, 11, 4, 2),
(9, '1080p Full HD USB Webcam', 7, 'Rapoo', 'C260 with Mic', 'CAM-RAP-C260', 4200.00, 5800.00, 7, 3, 1),
(10, '64GB USB 3.2 Flash Drive', 5, 'Kingston', 'DataTraveler Exodia', 'USB-KNG-64GB', 1500.00, 2200.00, 35, 10, 1),
(11, 'Universal 65W Laptop Power Charger', 2, 'Huntkey', '65W Slim Tip/Pin', 'CHR-UNT-65W', 3400.00, 4800.00, 8, 4, 2),
(12, 'Dell Inspiron Replacement Battery', 2, 'OEM Spec', 'Type WDX0R 42Wh', 'BAT-DEL-WDX', 6500.00, 9200.00, 4, 3, 2),
(13, 'HP Pavilion Replacement Keyboard', 2, 'OEM Spec', '15-cc / 15-cd series', 'KB-HP-15CC', 2600.00, 4200.00, 3, 2, 2),
(14, '15.6\" Slim 30-Pin FHD Laptop LED Display', 2, 'BOE', 'NT156FHM-N61', 'DIS-156-FHD', 14500.00, 19500.00, 2, 3, 2),
(15, '1.5 Meter Gold-Plated HDMI Cable', 6, 'Prolink', 'HDMI 2.0 4K', 'CBL-HDM-15M', 650.00, 1200.00, 40, 8, 3),
(16, 'Cat6 High-Speed RJ45 LAN Cable 5m', 4, 'D-Link', 'Cat6 Molded Patch', 'CBL-LAN-5M', 450.00, 850.00, 30, 8, 3),
(17, 'USB Type-C to USB-A Fast Cable 1m', 6, 'Baseus', 'SuperCharge 66W', 'CBL-USB-TYPC', 500.00, 950.00, 28, 5, 3),
(18, '300Mbps Mini Wireless USB Wi-Fi Adapter', 4, 'TP-Link', 'TL-WN823N', 'NET-TPL-W300', 2100.00, 3200.00, 15, 5, 1),
(19, 'USB Bluetooth 5.0 Dongle', 4, 'TP-Link', 'UB500 Nano', 'NET-TPL-BT50', 1800.00, 2700.00, 19, 5, 1),
(20, '550W 80 Plus Bronze Power Supply', 1, 'Corsair', 'CV550 ATX', 'PSU-COR-550W', 14000.00, 18500.00, 5, 3, 1),
(21, 'H610M DDR4 LGA1700 Motherboard', 1, 'Gigabyte', 'H610M H V2', 'MB-GIG-H610', 24500.00, 29500.00, 3, 2, 1),
(22, '120mm ARGB Hydraulic Case Fan', 1, 'Golden Field', 'Ring Frost 120', 'FAN-GF-120RGB', 1100.00, 1750.00, 16, 5, 2),
(23, 'Arctic MX-4 High-Performance Thermal Paste 4g', 9, 'Arctic', 'MX-4 Carbon Micro-particles', 'THM-ARC-MX4', 1600.00, 2500.00, 14, 5, 3),
(24, '15.6\" Water-Resistant Laptop Backpack', 3, 'Targus', 'Classic Slim', 'BAG-TAR-156', 3200.00, 4800.00, 7, 3, 3),
(25, 'Ergonomic Dual-Fan Laptop Cooling Pad', 3, 'DeepCool', 'Wind Pal Mini', 'COL-DPC-MINI', 2200.00, 3400.00, 6, 3, 3),
(26, '2.0 USB Stereo Desktop Multimedia Speakers', 7, 'Microlab', 'B-17 Compact', 'SPK-MIC-B17', 2400.00, 3600.00, 10, 4, 3);

-- 7. Purchases & Purchase Items Table
DROP TABLE IF EXISTS `purchase_items`;
DROP TABLE IF EXISTS `purchases`;

CREATE TABLE `purchases` (
  `purchase_id` INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id` INT,
  `invoice_no` VARCHAR(50),
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `purchase_date` DATE NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `purchase_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT NOT NULL,
  `product_id` INT,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`purchase_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Sales Table
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `sales`;

CREATE TABLE `sales` (
  `sale_id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT,
  `user_id` INT,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Card', 'Other') NOT NULL DEFAULT 'Cash',
  `sale_date` DATE NOT NULL,
  `status` ENUM('Completed', 'Pending', 'Cancelled') NOT NULL DEFAULT 'Completed',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sale_inv` (`invoice_no`),
  INDEX `idx_sale_cust` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sale_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT NOT NULL,
  `product_id` INT,
  `product_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`sale_id`) REFERENCES `sales`(`sale_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. PC / Laptop Repair Management Table
DROP TABLE IF EXISTS `repair_parts`;
DROP TABLE IF EXISTS `repairs`;

CREATE TABLE `repairs` (
  `repair_id` INT AUTO_INCREMENT PRIMARY KEY,
  `repair_code` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `user_id` INT,
  `device_type` VARCHAR(50) NOT NULL, -- Laptop, Desktop PC, All-in-One, Monitor, Printer, etc.
  `brand` VARCHAR(50) NOT NULL,
  `model` VARCHAR(60),
  `serial_number` VARCHAR(100),
  `problem_description` TEXT NOT NULL,
  `diagnosis` TEXT,
  `repair_notes` TEXT,
  `technician` VARCHAR(100),
  `received_date` DATE NOT NULL,
  `expected_date` DATE,
  `completed_date` DATE,
  `labour_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `parts_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `other_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estimated_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `final_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `advance_payment` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Received', 'Diagnosing', 'Waiting for Parts', 'Repairing', 'Ready', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Received',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_rep_code` (`repair_code`),
  INDEX `idx_rep_cust` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `repair_parts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `repair_id` INT NOT NULL,
  `product_id` INT,
  `part_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`repair_id`) REFERENCES `repairs`(`repair_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Invoices Table
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;

CREATE TABLE `invoices` (
  `invoice_id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('sale', 'repair') NOT NULL DEFAULT 'sale',
  `reference_id` INT NOT NULL, -- sale_id or repair_id
  `customer_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash',
  `status` ENUM('Paid', 'Partial', 'Unpaid') NOT NULL DEFAULT 'Paid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_inv_no` (`invoice_no`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`invoice_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Payments Table
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(50),
  `sale_id` INT,
  `repair_id` INT,
  `customer_id` INT,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Card', 'Other') NOT NULL DEFAULT 'Cash',
  `payment_date` DATE NOT NULL,
  `reference_no` VARCHAR(100),
  `received_by` VARCHAR(100),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Expenses Table
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `expense_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` ENUM('Rent', 'Electricity', 'Internet', 'Transport', 'Parts Purchase', 'Salary', 'Maintenance', 'Other') NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Card', 'Other') NOT NULL DEFAULT 'Cash',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Stock Movements Table
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `movement_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `movement_type` ENUM('SALE', 'REPAIR', 'PURCHASE', 'RETURN', 'ADJUSTMENT') NOT NULL,
  `quantity` INT NOT NULL, -- positive or negative
  `previous_stock` INT NOT NULL,
  `current_stock` INT NOT NULL,
  `reference_id` VARCHAR(50), -- e.g. MT-2026-00001, REP-2026-00001
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sm_prod` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial Seed Sales & Invoices
INSERT INTO `sales` (`sale_id`, `invoice_no`, `customer_id`, `user_id`, `subtotal`, `discount`, `grand_total`, `paid_amount`, `balance`, `payment_method`, `sale_date`, `status`, `notes`) VALUES
(1, 'MT-2026-00001', 1, 1, 15700.00, 700.00, 15000.00, 15000.00, 0.00, 'Cash', CURDATE(), 'Completed', 'Walk-in upgrade accessories'),
(2, 'MT-2026-00002', 2, 2, 7800.00, 0.00, 7800.00, 7800.00, 0.00, 'Card', CURDATE(), 'Completed', 'NVMe SSD Purchase');

INSERT INTO `sale_items` (`sale_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `discount`, `total`) VALUES
(1, 1, '8GB DDR4 3200MHz Desktop RAM', 1, 8500.00, 0.00, 8500.00),
(1, 7, 'Mechanical Gaming Keyboard RGB', 1, 7900.00, 700.00, 7200.00),
(2, 3, '256GB NVMe M.2 SSD', 1, 7800.00, 0.00, 7800.00);

INSERT INTO `invoices` (`invoice_id`, `invoice_no`, `type`, `reference_id`, `customer_id`, `date`, `subtotal`, `discount`, `total`, `paid`, `balance`, `payment_method`, `status`) VALUES
(1, 'MT-2026-00001', 'sale', 1, 1, CURDATE(), 15700.00, 700.00, 15000.00, 15000.00, 0.00, 'Cash', 'Paid'),
(2, 'MT-2026-00002', 'sale', 2, 2, CURDATE(), 7800.00, 0.00, 7800.00, 7800.00, 0.00, 'Card', 'Paid');

INSERT INTO `invoice_items` (`invoice_id`, `description`, `quantity`, `unit_price`, `discount`, `total`) VALUES
(1, '8GB DDR4 3200MHz Desktop RAM', 1, 8500.00, 0.00, 8500.00),
(1, 'Mechanical Gaming Keyboard RGB', 1, 7900.00, 700.00, 7200.00),
(2, '256GB NVMe M.2 SSD', 1, 7800.00, 0.00, 7800.00);

INSERT INTO `payments` (`invoice_no`, `sale_id`, `customer_id`, `amount`, `payment_method`, `payment_date`, `received_by`, `notes`) VALUES
('MT-2026-00001', 1, 1, 15000.00, 'Cash', CURDATE(), 'Admin', 'Full payment collected at counter'),
('MT-2026-00002', 2, 2, 7800.00, 'Card', CURDATE(), 'Cashier', 'POS card swipe confirmation');

-- Initial Seed PC & Laptop Repairs
INSERT INTO `repairs` (`repair_id`, `repair_code`, `customer_id`, `user_id`, `device_type`, `brand`, `model`, `serial_number`, `problem_description`, `diagnosis`, `repair_notes`, `technician`, `received_date`, `expected_date`, `completed_date`, `labour_cost`, `parts_cost`, `other_charges`, `discount`, `estimated_cost`, `final_cost`, `advance_payment`, `balance`, `status`) VALUES
(1, 'REP-2026-00001', 1, 1, 'Laptop', 'HP', 'Pavilion 15-cs', '5CD8391K3Z', 'Very slow boot, blue screen memory error, overheating fan noise', 'Defective mechanical hard drive and bad RAM module. Heatsink clogged with heavy dust.', 'Cleaned thermal heatsink, applied Arctic MX-4 paste, upgraded to 256GB NVMe SSD and added 8GB RAM. Fresh Windows 11 installation completed.', 'Senior Tech', CURDATE() - INTERVAL 2 DAY, CURDATE(), CURDATE(), 2500.00, 16300.00, 0.00, 800.00, 18000.00, 18000.00, 5000.00, 0.00, 'Ready'),
(2, 'REP-2026-00002', 2, 3, 'Desktop PC', 'Custom Built', 'Core i5 Gaming Rig', 'CUS-2024-099', 'No display on monitor, powers on then shuts off after 10 seconds', 'Faulty power supply 12V rail failing under initial load test.', 'Recommended replacing PSU with Corsair CV550 Bronze.', 'Senior Tech', CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 1 DAY, NULL, 1500.00, 18500.00, 0.00, 0.00, 20000.00, 20000.00, 3000.00, 17000.00, 'Repairing'),
(3, 'REP-2026-00003', 3, 3, 'Laptop', 'Dell', 'Inspiron 3593', '9H5G342', 'Liquid spill on keyboard, several keys not typing or typing repeated letters', 'Keypad matrix shorted. Motherboard protected by barrier, no corrosion on PCB.', 'Waiting for OEM replacement keyboard stock arrival.', 'Senior Tech', CURDATE(), CURDATE() + INTERVAL 3 DAY, NULL, 1500.00, 4200.00, 0.00, 0.00, 5700.00, 5700.00, 2000.00, 3700.00, 'Waiting for Parts'),
(4, 'REP-2026-00004', 4, 3, 'Laptop', 'Asus', 'VivoBook X512', 'K4N0CV02E8', 'Broken screen glass and flickering horizontal lines after drop', 'Cracked LCD panel.', 'Replaced with 15.6 FHD slim 30-pin LED panel. Tested color accuracy and brightness.', 'Senior Tech', CURDATE() - INTERVAL 4 DAY, CURDATE() - INTERVAL 1 DAY, CURDATE() - INTERVAL 1 DAY, 2000.00, 19500.00, 0.00, 500.00, 21000.00, 21000.00, 21000.00, 0.00, 'Delivered');

INSERT INTO `repair_parts` (`repair_id`, `product_id`, `part_name`, `quantity`, `unit_price`, `total`) VALUES
(1, 3, '256GB NVMe M.2 SSD', 1, 7800.00, 7800.00),
(1, 1, '8GB DDR4 3200MHz Desktop RAM', 1, 8500.00, 8500.00),
(2, 20, '550W 80 Plus Bronze Power Supply', 1, 18500.00, 18500.00),
(3, 13, 'HP Pavilion Replacement Keyboard', 1, 4200.00, 4200.00),
(4, 14, '15.6\" Slim 30-Pin FHD Laptop LED Display', 1, 19500.00, 19500.00);

-- Initial Seed Expenses
INSERT INTO `expenses` (`category`, `description`, `amount`, `expense_date`, `payment_method`) VALUES
('Rent', 'Shop Monthly Rental - Vivekanandanagar Building', 35000.00, CURDATE() - INTERVAL 5 DAY, 'Bank Transfer'),
('Electricity', 'CEB Commercial Electricity Bill', 8500.00, CURDATE() - INTERVAL 3 DAY, 'Cash'),
('Internet', 'SLT Fiber High-Speed Internet Subscription', 4200.00, CURDATE() - INTERVAL 2 DAY, 'Bank Transfer'),
('Maintenance', 'Service Station Soldering Iron Tips and Desoldering Braid', 2600.00, CURDATE(), 'Cash');
 
-- 14. Password Resets Table
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `otp` VARCHAR(10) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_reset_token` (`token`),
  INDEX `idx_reset_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
