-- FAID exhibition demo dataset.
-- Safe to import repeatedly: no DROP, DELETE, UPDATE, or original-database use.
-- Creates and populates only the separate faidproject_demo database.

CREATE DATABASE IF NOT EXISTS faidproject_demo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;
USE faidproject_demo;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS admins (
    admin_id int(11) NOT NULL AUTO_INCREMENT,
    admin_name varchar(100) NOT NULL,
    email varchar(100) NOT NULL,
    password_hash varchar(255) NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (admin_id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS companies (
    company_id int(11) NOT NULL AUTO_INCREMENT,
    company_name varchar(150) NOT NULL,
    email varchar(150) NOT NULL,
    phone varchar(30) NOT NULL,
    address varchar(255) NOT NULL,
    password_hash varchar(255) NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (company_id),
    UNIQUE KEY email (email),
    KEY companies_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS materials (
    material_id int(11) NOT NULL AUTO_INCREMENT,
    company_id int(11) NOT NULL,
    material_name varchar(150) NOT NULL,
    category varchar(50) NOT NULL,
    description text DEFAULT NULL,
    quantity int(11) NOT NULL DEFAULT 0,
    price decimal(10,2) NOT NULL DEFAULT 0.00,
    image_path varchar(255) DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (material_id),
    KEY company_id (company_id),
    KEY materials_created_at (created_at),
    CONSTRAINT demo_materials_company_fk
        FOREIGN KEY (company_id) REFERENCES companies (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS orders (
    order_id int(11) NOT NULL AUTO_INCREMENT,
    buyer_id int(11) NOT NULL,
    order_date timestamp NOT NULL DEFAULT current_timestamp(),
    status varchar(30) NOT NULL DEFAULT 'Pending',
    PRIMARY KEY (order_id),
    KEY buyer_id (buyer_id),
    KEY orders_order_date (order_date),
    CONSTRAINT demo_orders_buyer_fk
        FOREIGN KEY (buyer_id) REFERENCES companies (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id int(11) NOT NULL AUTO_INCREMENT,
    order_id int(11) NOT NULL,
    material_id int(11) NOT NULL,
    quantity int(11) NOT NULL,
    unit_price decimal(10,2) NOT NULL,
    PRIMARY KEY (order_item_id),
    KEY order_id (order_id),
    KEY material_id (material_id),
    CONSTRAINT demo_order_items_order_fk
        FOREIGN KEY (order_id) REFERENCES orders (order_id),
    CONSTRAINT demo_order_items_material_fk
        FOREIGN KEY (material_id) REFERENCES materials (material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

START TRANSACTION;

-- Demo-only login for the administrator and all ten fictional companies:
-- Password for every demo account: FaidDemo!2026
INSERT IGNORE INTO admins (admin_name, email, password_hash, created_at) VALUES
    ('FAID Demo Administrator', 'admin@faid-demo.test',
     '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW',
     '2026-10-01 09:00:00');

INSERT IGNORE INTO companies
    (company_name, email, phone, address, password_hash, created_at)
VALUES
    ('Cedar & Saw Mill', 'cedar.saw@faid-demo.test', '+964 770 000 1001', 'Industrial District, Basra', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-01 09:15:00'),
    ('Wadi Steel Works', 'wadi.steel@faid-demo.test', '+964 770 000 1002', 'Al-Zubair Industrial Area, Basra', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-02 10:20:00'),
    ('ClearCycle Plastics', 'clearcycle@faid-demo.test', '+964 770 000 1003', 'Taji Industrial Zone, Baghdad', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-03 11:30:00'),
    ('Carton Loop Packaging', 'carton.loop@faid-demo.test', '+964 770 000 1004', 'Khor Al-Zubair, Basra', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-04 08:45:00'),
    ('Basra Electrical Supply', 'basra.electrical@faid-demo.test', '+964 770 000 1005', 'Ashar Commercial Quarter, Basra', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-05 13:10:00'),
    ('Tigris Joinery Supply', 'tigris.joinery@faid-demo.test', '+964 770 000 1006', 'Dora Industrial Street, Baghdad', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-06 09:35:00'),
    ('Gulf Metal Fabricators', 'gulf.metal@faid-demo.test', '+964 770 000 1007', 'Umm Qasr Road, Basra', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-07 14:25:00'),
    ('EcoPolymer Trading', 'ecopolymer@faid-demo.test', '+964 770 000 1008', 'Erbil Industrial Estate, Erbil', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-08 10:05:00'),
    ('ReBox Industrial Packaging', 'rebox.packaging@faid-demo.test', '+964 770 000 1009', 'Najaf Light Industry Park, Najaf', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-09 12:40:00'),
    ('Nahrain Power & Cable', 'nahrain.power@faid-demo.test', '+964 770 000 1010', 'Al-Mahmudiya Industrial Road, Baghdad', '$2y$10$qvfQlrTH24zPTSbrgdRyQu1CwsanGk4YIlme2ZoL1e5Unp4FUOSbW', '2026-10-10 15:15:00');

-- 30 listings: six for each existing category; image files are included locally
-- in WTproject/FaidProject/uploads and are copied by the accompanying setup steps.
INSERT INTO materials
    (company_id, material_name, category, description, quantity, price, image_path, created_at)
SELECT c.company_id, seed.material_name, seed.category, seed.description,
       seed.quantity, seed.price, seed.image_path, seed.created_at
FROM (
    SELECT 'cedar.saw@faid-demo.test' AS email, 'Kiln-dried pine pallet boards' AS material_name, 'wood' AS category,
           'Clean surplus pine boards from a packaging run; kiln-dried and suitable for pallets, shelving, or workshop projects.' AS description,
           180 AS quantity, 12500.00 AS price, 'uploads/demo-pine-pallet-boards.jpg' AS image_path, '2026-11-16 06:00:00' AS created_at
    UNION ALL SELECT 'cedar.saw@faid-demo.test', 'Oak hardwood offcuts', 'wood',
           'Sorted solid-oak offcuts, 40-90 cm long. Useful for furniture components, trim, and small craft projects.',
           74, 18500.00, 'uploads/demo-oak-offcuts.jpg', '2026-11-15 09:00:00'
    UNION ALL SELECT 'cedar.saw@faid-demo.test', 'Cedar battens 25 x 50 mm', 'wood',
           'Planed cedar battens left over from a completed interior fit-out; dry, straight, and bundled for easy collection.',
           210, 9000.00, 'uploads/demo-cedar-battens.jpg', '2026-11-14 08:30:00'
    UNION ALL SELECT 'wadi.steel@faid-demo.test', 'Galvanized steel sheet offcuts', 'metal',
           'Flat galvanized sheet offcuts, mixed rectangles between 0.5 and 1.2 m; clean edges and no heavy corrosion.',
           96, 22000.00, 'uploads/demo-galvanized-sheet.jpg', '2026-11-16 05:45:00'
    UNION ALL SELECT 'wadi.steel@faid-demo.test', 'Mild-steel round bar 12 mm', 'metal',
           'Short lengths of 12 mm mild-steel bar from a fabrication batch; sold by kilogram and suitable for general workshop use.',
           320, 1750.00, 'uploads/demo-mild-steel-bar.webp', '2026-11-13 12:00:00'
    UNION ALL SELECT 'wadi.steel@faid-demo.test', 'Steel pipe offcuts 1 inch', 'metal',
           'Reusable one-inch steel pipe offcuts, sorted by length and ready for racks, frames, or light fabrication.',
           58, 14000.00, 'uploads/demo-steel-pipe-offcuts.webp', '2026-11-10 10:15:00'
    UNION ALL SELECT 'clearcycle@faid-demo.test', 'Recycled HDPE pellets', 'plastic',
           'Washed, natural-color recycled HDPE regrind pellets from a clean production changeover; suitable for non-food molding.',
           420, 1600.00, 'uploads/demo-hdpe-pellets.jpg', '2026-11-16 05:30:00'
    UNION ALL SELECT 'clearcycle@faid-demo.test', 'LDPE film roll ends', 'plastic',
           'Clear low-density polyethylene film roll ends, 30-60 cm wide. Good for protective wrapping and non-food packaging.',
           64, 7500.00, 'uploads/demo-ldpe-film-rolls.jpg', '2026-11-12 09:20:00'
    UNION ALL SELECT 'clearcycle@faid-demo.test', 'Mixed-color PP regrind', 'plastic',
           'Clean polypropylene regrind from crate production, mixed industrial colors and screened for large contaminants.',
           275, 1350.00, 'uploads/demo-pp-regrind.jpg', '2026-11-09 14:10:00'
    UNION ALL SELECT 'carton.loop@faid-demo.test', 'Double-wall corrugated sheets', 'packaging',
           'Surplus double-wall corrugated sheets, approximately 1.0 x 1.2 m; sturdy stock suitable for protective packaging.',
           350, 2400.00, 'uploads/demo-corrugated-sheets.webp', '2026-11-16 05:15:00'
    UNION ALL SELECT 'carton.loop@faid-demo.test', 'Flat-pack shipping cartons', 'packaging',
           'Unused B-flute cartons, 40 x 30 x 25 cm, supplied flat in tied bundles; minor print variation between bundles.',
           520, 950.00, 'uploads/demo-shipping-cartons.jpg', '2026-11-11 08:00:00'
    UNION ALL SELECT 'carton.loop@faid-demo.test', 'Kraft paperboard divider blanks', 'packaging',
           'Die-cut kraft divider blanks from a canceled packaging order; unprinted and suitable for box inserts or prototyping.',
           1200, 350.00, 'uploads/demo-paperboard-dividers.webp', '2026-11-08 11:40:00'
    UNION ALL SELECT 'basra.electrical@faid-demo.test', 'Copper power cable offcuts 4 mm²', 'electrical',
           'Insulated copper power-cable offcuts from a commercial installation, assorted 2-8 m lengths; insulation intact.',
           145, 4200.00, 'uploads/demo-copper-cable.png', '2026-11-16 05:00:00'
    UNION ALL SELECT 'basra.electrical@faid-demo.test', 'PVC electrical conduit 20 mm', 'electrical',
           'Surplus 20 mm PVC conduit lengths, straight and clean, with a small matching batch of couplers.',
           230, 1800.00, 'uploads/demo-pvc-conduit.png', '2026-11-10 09:00:00'
    UNION ALL SELECT 'basra.electrical@faid-demo.test', 'LED high-bay fixtures 100 W', 'electrical',
           'New, unused 100 W LED high-bay fixtures from an over-order; each unit boxed and supplied with mounting hooks.',
           28, 38500.00, 'uploads/demo-led-high-bay.png', '2026-11-06 13:30:00'
    UNION ALL SELECT 'tigris.joinery@faid-demo.test', 'Birch plywood panels 12 mm', 'wood',
           'A/B-grade birch plywood panels, 1220 x 2440 mm, from a surplus cabinet run; dry-stored with minor edge marks.',
           46, 46000.00, 'uploads/demo-birch-plywood.jpg', '2026-11-15 14:00:00'
    UNION ALL SELECT 'tigris.joinery@faid-demo.test', 'Reclaimed hardwood beams', 'wood',
           'Recovered hardwood beams, visually inspected and denailed; dimensions vary, ideal for repair work and rustic builds.',
           31, 52000.00, 'uploads/demo-hardwood-beams.jpg', '2026-11-07 09:30:00'
    UNION ALL SELECT 'tigris.joinery@faid-demo.test', 'MDF boards 18 mm', 'wood',
           'Moisture-free 18 mm MDF boards from a discontinued furniture batch; full and half sheets available.',
           63, 22500.00, 'uploads/demo-mdf-boards.jpg', '2026-10-28 10:00:00'
    UNION ALL SELECT 'gulf.metal@faid-demo.test', 'Stainless-steel trim offcuts', 'metal',
           'Brushed stainless-steel trim offcuts, mostly 304 grade, deburred and sorted into manageable workshop lengths.',
           82, 28000.00, 'uploads/demo-stainless-trim.jpg', '2026-11-14 13:15:00'
    UNION ALL SELECT 'gulf.metal@faid-demo.test', 'Aluminium angle 40 x 40 mm', 'metal',
           'Lightweight 40 x 40 mm aluminium angle offcuts from a frame order; lengths range from 0.8 to 2 m.',
           110, 12500.00, 'uploads/demo-aluminium-angle.webp', '2026-11-05 08:45:00'
    UNION ALL SELECT 'gulf.metal@faid-demo.test', 'Reinforcing bar 10 mm', 'metal',
           'Straight 10 mm reinforcing bar surplus, stored under cover and sold in convenient bundles by kilogram.',
           490, 1600.00, 'uploads/demo-rebar.jpg', '2026-10-22 07:30:00'
    UNION ALL SELECT 'ecopolymer@faid-demo.test', 'Polypropylene granules, natural', 'plastic',
           'Virgin-grade natural polypropylene granules left after a production trial; sealed 25 kg sacks, non-food applications.',
           32, 34500.00, 'uploads/demo-pp-granules.jpg', '2026-11-13 15:00:00'
    UNION ALL SELECT 'ecopolymer@faid-demo.test', 'PET flake regrind, clear', 'plastic',
           'Clean clear PET flake regrind from a sorted industrial stream; washed and packed for downstream processing.',
           360, 2100.00, 'uploads/demo-pet-flakes.jpg', '2026-11-04 12:30:00'
    UNION ALL SELECT 'ecopolymer@faid-demo.test', 'HDPE resin sacks, 25 kg', 'plastic',
           'Factory-sealed 25 kg sacks of HDPE resin from an overstock lot; batch labels retained for buyer inspection.',
           41, 31500.00, 'uploads/demo-hdpe-sacks.jpg', '2026-10-20 09:15:00'
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', 'Corrugated carton corner protectors', 'packaging',
           'Paperboard corner protectors in assorted lengths, clean unused surplus from palletized appliance shipments.',
           860, 450.00, 'uploads/demo-carton-corners.jpg', '2026-11-12 07:45:00'
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', 'Recycled kraft carton blanks', 'packaging',
           'Flat recycled-kraft carton blanks with no branding, mixed medium sizes and intact scoring for easy folding.',
           640, 800.00, 'uploads/demo-kraft-carton-blanks.webp', '2026-11-03 10:30:00'
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', 'Paper honeycomb protective wrap', 'packaging',
           'Expandable kraft honeycomb wrap rolls left from a shipping contract; useful for cushioning non-food goods.',
           95, 6200.00, 'uploads/demo-honeycomb-wrap.webp', '2026-10-16 08:15:00'
    UNION ALL SELECT 'nahrain.power@faid-demo.test', 'Miniature circuit breakers, 16 A', 'electrical',
           'New 16 A miniature circuit breakers from a surplus electrical panel order; individually boxed, standard single-pole type.',
           75, 6800.00, 'uploads/demo-circuit-breakers.png', '2026-11-11 16:00:00'
    UNION ALL SELECT 'nahrain.power@faid-demo.test', 'Perforated aluminium cable tray', 'electrical',
           'Light-duty perforated aluminium cable tray sections, 2 m lengths, with minor handling marks and no fittings.',
           44, 26500.00, 'uploads/demo-cable-tray.png', '2026-10-30 11:20:00'
    UNION ALL SELECT 'nahrain.power@faid-demo.test', 'Industrial weatherproof sockets', 'electrical',
           'Unused IP-rated industrial wall sockets from an installation over-order; protective covers and mounting screws included.',
           38, 12500.00, 'uploads/demo-industrial-sockets.png', '2026-09-18 09:50:00'
) AS seed
INNER JOIN companies c ON c.email = seed.email
WHERE NOT EXISTS (
    SELECT 1
    FROM materials existing
    WHERE existing.company_id = c.company_id
      AND existing.material_name = seed.material_name
);

-- 20 orders cover dashboard, status, reservation, and reporting views.
INSERT INTO orders (buyer_id, order_date, status)
SELECT c.company_id, seed.order_date, seed.status
FROM (
    SELECT 'tigris.joinery@faid-demo.test' AS buyer_email, '2026-11-16 06:30:00' AS order_date, 'Pending' AS status
    UNION ALL SELECT 'clearcycle@faid-demo.test', '2026-11-16 06:15:00', 'Pending'
    UNION ALL SELECT 'gulf.metal@faid-demo.test', '2026-11-15 13:40:00', 'Approved'
    UNION ALL SELECT 'wadi.steel@faid-demo.test', '2026-11-15 10:10:00', 'Pending'
    UNION ALL SELECT 'ecopolymer@faid-demo.test', '2026-11-14 11:00:00', 'Rejected'
    UNION ALL SELECT 'carton.loop@faid-demo.test', '2026-11-13 09:25:00', 'Completed'
    UNION ALL SELECT 'cedar.saw@faid-demo.test', '2026-11-12 14:30:00', 'Approved'
    UNION ALL SELECT 'tigris.joinery@faid-demo.test', '2026-11-11 12:05:00', 'Pending'
    UNION ALL SELECT 'nahrain.power@faid-demo.test', '2026-11-10 08:50:00', 'Approved'
    UNION ALL SELECT 'clearcycle@faid-demo.test', '2026-11-09 16:10:00', 'Completed'
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', '2026-11-05 10:45:00', 'Rejected'
    UNION ALL SELECT 'nahrain.power@faid-demo.test', '2026-10-28 11:15:00', 'Completed'
    UNION ALL SELECT 'wadi.steel@faid-demo.test', '2026-10-22 09:35:00', 'Pending'
    UNION ALL SELECT 'gulf.metal@faid-demo.test', '2026-10-18 15:20:00', 'Rejected'
    UNION ALL SELECT 'basra.electrical@faid-demo.test', '2026-10-12 08:30:00', 'Approved'
    UNION ALL SELECT 'cedar.saw@faid-demo.test', '2026-09-29 12:45:00', 'Completed'
    UNION ALL SELECT 'ecopolymer@faid-demo.test', '2026-09-15 10:00:00', 'Pending'
    UNION ALL SELECT 'basra.electrical@faid-demo.test', '2026-08-20 13:10:00', 'Approved'
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', '2026-06-11 09:20:00', 'Rejected'
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', '2026-02-05 14:00:00', 'Completed'
) AS seed
INNER JOIN companies c ON c.email = seed.buyer_email
WHERE NOT EXISTS (
    SELECT 1
    FROM orders existing
    WHERE existing.buyer_id = c.company_id
      AND existing.order_date = seed.order_date
);

-- One material line per sample order; buyer and supplier are different companies.
INSERT INTO order_items (order_id, material_id, quantity, unit_price)
SELECT o.order_id, m.material_id, seed.quantity, seed.unit_price
FROM (
    SELECT 'tigris.joinery@faid-demo.test' AS buyer_email, '2026-11-16 06:30:00' AS order_date, 'cedar.saw@faid-demo.test' AS seller_email, 'Kiln-dried pine pallet boards' AS material_name, 24 AS quantity, 12500.00 AS unit_price
    UNION ALL SELECT 'clearcycle@faid-demo.test', '2026-11-16 06:15:00', 'wadi.steel@faid-demo.test', 'Galvanized steel sheet offcuts', 18, 22000.00
    UNION ALL SELECT 'gulf.metal@faid-demo.test', '2026-11-15 13:40:00', 'clearcycle@faid-demo.test', 'Recycled HDPE pellets', 35, 1600.00
    UNION ALL SELECT 'wadi.steel@faid-demo.test', '2026-11-15 10:10:00', 'carton.loop@faid-demo.test', 'Flat-pack shipping cartons', 60, 950.00
    UNION ALL SELECT 'ecopolymer@faid-demo.test', '2026-11-14 11:00:00', 'basra.electrical@faid-demo.test', 'LED high-bay fixtures 100 W', 4, 38500.00
    UNION ALL SELECT 'carton.loop@faid-demo.test', '2026-11-13 09:25:00', 'tigris.joinery@faid-demo.test', 'Birch plywood panels 12 mm', 8, 46000.00
    UNION ALL SELECT 'cedar.saw@faid-demo.test', '2026-11-12 14:30:00', 'gulf.metal@faid-demo.test', 'Aluminium angle 40 x 40 mm', 12, 12500.00
    UNION ALL SELECT 'tigris.joinery@faid-demo.test', '2026-11-11 12:05:00', 'ecopolymer@faid-demo.test', 'Polypropylene granules, natural', 3, 34500.00
    UNION ALL SELECT 'nahrain.power@faid-demo.test', '2026-11-10 08:50:00', 'rebox.packaging@faid-demo.test', 'Corrugated carton corner protectors', 80, 450.00
    UNION ALL SELECT 'clearcycle@faid-demo.test', '2026-11-09 16:10:00', 'nahrain.power@faid-demo.test', 'Miniature circuit breakers, 16 A', 10, 6800.00
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', '2026-11-05 10:45:00', 'cedar.saw@faid-demo.test', 'Oak hardwood offcuts', 9, 18500.00
    UNION ALL SELECT 'nahrain.power@faid-demo.test', '2026-10-28 11:15:00', 'wadi.steel@faid-demo.test', 'Mild-steel round bar 12 mm', 70, 1750.00
    UNION ALL SELECT 'wadi.steel@faid-demo.test', '2026-10-22 09:35:00', 'clearcycle@faid-demo.test', 'LDPE film roll ends', 6, 7500.00
    UNION ALL SELECT 'gulf.metal@faid-demo.test', '2026-10-18 15:20:00', 'carton.loop@faid-demo.test', 'Double-wall corrugated sheets', 45, 2400.00
    UNION ALL SELECT 'basra.electrical@faid-demo.test', '2026-10-12 08:30:00', 'nahrain.power@faid-demo.test', 'Perforated aluminium cable tray', 5, 26500.00
    UNION ALL SELECT 'cedar.saw@faid-demo.test', '2026-09-29 12:45:00', 'tigris.joinery@faid-demo.test', 'MDF boards 18 mm', 7, 22500.00
    UNION ALL SELECT 'ecopolymer@faid-demo.test', '2026-09-15 10:00:00', 'clearcycle@faid-demo.test', 'LDPE film roll ends', 8, 7500.00
    UNION ALL SELECT 'basra.electrical@faid-demo.test', '2026-08-20 13:10:00', 'nahrain.power@faid-demo.test', 'Industrial weatherproof sockets', 3, 12500.00
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', '2026-06-11 09:20:00', 'carton.loop@faid-demo.test', 'Kraft paperboard divider blanks', 60, 350.00
    UNION ALL SELECT 'rebox.packaging@faid-demo.test', '2026-02-05 14:00:00', 'basra.electrical@faid-demo.test', 'Copper power cable offcuts 4 mm²', 12, 4200.00
) AS seed
INNER JOIN companies buyer ON buyer.email = seed.buyer_email
INNER JOIN orders o
    ON o.buyer_id = buyer.company_id
   AND o.order_date = seed.order_date
INNER JOIN companies seller ON seller.email = seed.seller_email
INNER JOIN materials m
    ON m.company_id = seller.company_id
   AND m.material_name = seed.material_name
WHERE seed.buyer_email <> seed.seller_email
  AND NOT EXISTS (
      SELECT 1
      FROM order_items existing
      WHERE existing.order_id = o.order_id
        AND existing.material_id = m.material_id
  );

COMMIT;

-- Matching demo-*.jpg/.webp/.png files are supplied in
-- WTproject/FaidProject/uploads. Keep those local files in place so cards
-- can resolve the image_path values; the SQL does not write to the filesystem.
