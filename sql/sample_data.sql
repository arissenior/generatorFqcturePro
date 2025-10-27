-- Données d'exemple pour les tests
USE facture_pro;

-- Insertion d'un utilisateur de test
INSERT INTO users (email, password, company_name, address, phone, siret, website) VALUES
('test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Entreprise Test', '123 Rue Example\n75001 Paris', '01 23 45 67 89', '12345678901234', 'www.example.com');

-- Récupération de l'ID utilisateur
SET @user_id = LAST_INSERT_ID();

-- Insertion de clients de test
INSERT INTO clients (user_id, name, email, address, phone, notes) VALUES
(@user_id, 'Client A', 'client.a@example.com', '456 Avenue Client\n75002 Paris', '01 34 56 78 90', 'Client fidèle depuis 2020'),
(@user_id, 'Client B', 'client.b@example.com', '789 Boulevard Client\n75003 Paris', '01 45 67 89 01', 'Nouveau client 2023');

-- Insertion de factures de test
INSERT INTO invoices (user_id, client_id, invoice_number, invoice_date, due_date, tax_rate, currency, status) VALUES
(@user_id, 1, 'FACT-2023-001', '2023-01-15', '2023-02-15', 20.00, '€', 'paid'),
(@user_id, 2, 'FACT-2023-002', '2023-02-01', '2023-03-01', 20.00, '€', 'sent');

-- Insertion d'articles de facture
INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES
(1, 'Développement site web', 10, 75.00),
(1, 'Hébergement annuel', 1, 120.00),
(2, 'Consulting stratégique', 5, 150.00),
(2, 'Formation équipe', 2, 200.00);