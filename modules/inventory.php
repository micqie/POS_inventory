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
    <div class="page-header">
        <h1 class="page-title">Inventory Transactions</h1>
        <p class="page-subtitle">Track stock movements in and out</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle"></i> Record New Transaction
        </div>
        <div class="card-body">
            <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg); align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Product</label>
                    <select class="form-select" name="product_id" required>
                        <option value="">Select product</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['product_id']; ?>"><?php echo sanitize($p['product_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Supplier (for IN only)</label>
                    <select class="form-select" name="supplier_id">
                        <option value="">None</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?php echo $s['supplier_id']; ?>"><?php echo sanitize($s['supplier_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Quantity</label>
                    <input class="form-control" type="number" name="quantity" min="1" required placeholder="Enter quantity">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Transaction Type</label>
                    <select class="form-select" name="transaction_type">
                        <option value="in">Stock In</option>
                        <option value="out">Stock Out</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end; margin-bottom: 0;">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-check-circle"></i> Record Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> Recent Transactions (Last 50)
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Supplier</th>
                            <th>Quantity</th>
                            <th>Type</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No transactions found. Record your first transaction above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><?php echo $t['transaction_id']; ?></td>
                                    <td><strong><?php echo sanitize($t['product_name']); ?></strong></td>
                                    <td><?php echo sanitize($t['supplier_name'] ?: '—'); ?></td>
                                    <td><strong><?php echo $t['quantity']; ?></strong></td>
                                    <td>
                                        <span style="padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 600; font-size: var(--font-size-xs); text-transform: uppercase; background: <?php echo $t['transaction_type'] === 'in' ? 'var(--success-light)' : 'var(--danger-light)'; ?>; color: <?php echo $t['transaction_type'] === 'in' ? '#065f46' : '#991b1b'; ?>;">
                                            <?php echo strtoupper($t['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($t['transaction_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
