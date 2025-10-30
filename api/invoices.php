<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Récupérer une facture spécifique
            $stmt = $pdo->prepare("
                SELECT i.*, c.name as client_name, c.email as client_email
                FROM invoices i 
                LEFT JOIN clients c ON i.client_id = c.id 
                WHERE i.id = ? AND i.user_id = ?
            ");
            $stmt->execute([$_GET['id'], $user_id]);
            $invoice = $stmt->fetch();

            if ($invoice) {
                $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
                $stmt->execute([$_GET['id']]);
                $invoice['items'] = $stmt->fetchAll();
                
                echo json_encode($invoice);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Facture non trouvée']);
            }
        } else {
            // Récupérer toutes les factures
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $offset = ($page - 1) * $limit;

            $stmt = $pdo->prepare("
                SELECT i.*, c.name as client_name 
                FROM invoices i 
                LEFT JOIN clients c ON i.client_id = c.id 
                WHERE i.user_id = ? 
                ORDER BY i.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $limit, $offset]);
            $invoices = $stmt->fetchAll();

            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoices WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total = $stmt->fetch()['total'];

            echo json_encode([
                'invoices' => $invoices,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO invoices (user_id, client_id, invoice_number, invoice_date, due_date, tax_rate, currency, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $data['client_id'] ?? null,
                $data['invoice_number'],
                $data['invoice_date'],
                $data['due_date'],
                $data['tax_rate'] ?? 20.00,
                $data['currency'] ?? '€',
                $data['notes'] ?? ''
            ]);

            $invoice_id = $pdo->lastInsertId();

            foreach ($data['items'] as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $invoice_id,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price']
                ]);
            }

            $pdo->commit();
            
            http_response_code(201);
            echo json_encode(['id' => $invoice_id, 'message' => 'Facture créée avec succès']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la création: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $invoice_id = $_GET['id'];

        // Vérifier que la facture appartient à l'utilisateur
        $stmt = $pdo->prepare("SELECT id FROM invoices WHERE id = ? AND user_id = ?");
        $stmt->execute([$invoice_id, $user_id]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Facture non trouvée']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE invoices 
                SET client_id = ?, invoice_number = ?, invoice_date = ?, due_date = ?, 
                    tax_rate = ?, currency = ?, notes = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['client_id'] ?? null,
                $data['invoice_number'],
                $data['invoice_date'],
                $data['due_date'],
                $data['tax_rate'] ?? 20.00,
                $data['currency'] ?? '€',
                $data['notes'] ?? '',
                $data['status'] ?? 'draft',
                $invoice_id
            ]);

            // Supprimer les anciens articles
            $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $stmt->execute([$invoice_id]);

            // Ajouter les nouveaux articles
            foreach ($data['items'] as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $invoice_id,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price']
                ]);
            }

            $pdo->commit();
            echo json_encode(['message' => 'Facture mise à jour avec succès']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $invoice_id = $_GET['id'];

        // Vérifier que la facture appartient à l'utilisateur
        $stmt = $pdo->prepare("SELECT id FROM invoices WHERE id = ? AND user_id = ?");
        $stmt->execute([$invoice_id, $user_id]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Facture non trouvée']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Supprimer les articles
            $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $stmt->execute([$invoice_id]);

            // Supprimer la facture
            $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
            $stmt->execute([$invoice_id]);

            $pdo->commit();
            echo json_encode(['message' => 'Facture supprimée avec succès']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?>