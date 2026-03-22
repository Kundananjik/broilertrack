USE broilertrack;

INSERT INTO batches (batch_name, breed, start_date, expected_harvest_date, initial_chicks, chick_cost, total_chick_cost, current_alive, mortality_count, notes)
VALUES
('January Flock', 'Cobb 500', '2026-01-05', '2026-02-25', 500, 1.10, 550.00, 480, 20, 'Pilot batch for 2026.');

INSERT INTO expenses (batch_id, date, category, item_name, quantity, unit_cost, total_cost, supplier, notes) VALUES
(1, '2026-01-05', 'Brooding', 'Wood shavings', 10, 4.50, 45.00, 'Farm Supplies Ltd', NULL),
(1, '2026-01-06', 'Utilities', 'Electricity top-up', 1, 35.00, 35.00, 'Zesco', 'Brooder heaters');

INSERT INTO feed_usage (batch_id, date, feed_type, feed_kg, cost_per_kg, total_cost) VALUES
(1, '2026-01-07', 'Starter', 120.0, 0.75, 90.00),
(1, '2026-01-14', 'Grower', 160.0, 0.68, 108.80);

INSERT INTO growth_records (batch_id, date, average_weight_kg, birds_sampled) VALUES
(1, '2026-01-20', 1.200, 12),
(1, '2026-02-10', 2.150, 15);

INSERT INTO sales (batch_id, date, birds_sold, average_weight_kg, price_per_bird, total_weight, total_revenue, buyer) VALUES
(1, '2026-02-25', 450, 2.250, 3.10, 1012.50, 3138.75, 'FreshMart Butchery');

INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$0AhJfgp3nuHJww2yDzESSOfxQIZ6FzlfEgrj7eu0x/7aJUQoqmMc.', 'admin');
