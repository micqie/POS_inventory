<?php
// Inventory transactions in/out and stock sync.
$products = $conn->query('SELECT product_id, product_name FROM products ORDER BY product_name')->fetch_all(MYSQLI_ASSOC);
$suppliers = $conn->query('SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name')->fetch_all(MYSQLI_ASSOC);

if (is_post()) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $supplierId = $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
    $qty = (int)($_POST['quantity'] ?? 0);
    $type = $_POST['transaction_type'] === 'out' ? 'out' : 'in';

    if ($productId === 0 || $qty <= 0) {
        flash('error', 'Product and positive quantity required.');
    } else {
        // Insert transaction
        $stmt = $conn->prepare('INSERT INTO inventory_transactions (product_id, supplier_id, quantity, transaction_type) VALUES (?,?,?,?)');
        $stmt->bind_param('iiis', $productId, $supplierId, $qty, $type);
        $stmt->execute();

        // Update stock
        $sign = $type === 'in' ? '+' : '-';
        $conn->query("UPDATE products SET stock = stock {$sign} {$qty} WHERE product_id = {$productId}");

        flash('success', 'Inventory transaction recorded.');
    }
    header('Location: index.php?page=inventory');
    exit;
}

$transactions = $conn->query('
    SELECT it.*, p.product_name, s.supplier_name
    FROM inventory_transactions it
    JOIN products p ON p.product_id = it.product_id
    LEFT JOIN suppliers s ON s.supplier_id = it.supplier_id
    ORDER BY it.transaction_id DESC
    LIMIT 50
')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <h3>Inventory Transactions</h3>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">Product</label>
                    <select class="form-select" name="product_id" required>
                        <option value="">Select product</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['product_id']; ?>"><?php echo sanitize($p['product_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Supplier (for IN)</label>
                    <select class="form-select" name="supplier_id">
                        <option value="">None</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?php echo $s['supplier_id']; ?>"><?php echo sanitize($s['supplier_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Quantity</label>
                    <input class="form-control" type="number" name="quantity" min="1" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="transaction_type">
                        <option value="in">In</option>
                        <option value="out">Out</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Record</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Product</th><th>Supplier</th><th>Qty</th><th>Type</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?php echo $t['transaction_id']; ?></td>
                            <td><?php echo sanitize($t['product_name']); ?></td>
                            <td><?php echo sanitize($t['supplier_name']); ?></td>
                            <td><?php echo $t['quantity']; ?></td>
                            <td><?php echo strtoupper($t['transaction_type']); ?></td>
                            <td><?php echo $t['transaction_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

