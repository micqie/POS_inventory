<?php
// Sales flow with session cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Data for selects
$products = $conn->query('SELECT product_id, product_name, price, stock FROM products ORDER BY product_name')->fetch_all(MYSQLI_ASSOC);
$customers = $conn->query('SELECT customer_id, customer_name FROM customers ORDER BY customer_name')->fetch_all(MYSQLI_ASSOC);

// Add to cart
if (is_post() && isset($_POST['add_to_cart'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    if ($productId === 0 || $qty <= 0) {
        flash('error', 'Select a product and quantity.');
    } else {
        // Find product info
        foreach ($products as $p) {
            if ((int)$p['product_id'] === $productId) {
                if ($qty > $p['stock']) {
                    flash('error', 'Not enough stock for ' . $p['product_name']);
                    header('Location: index.php?page=sales');
                    exit;
                }
                $_SESSION['cart'][$productId] = [
                    'product_id' => $productId,
                    'product_name' => $p['product_name'],
                    'price' => (float)$p['price'],
                    'quantity' => $qty,
                ];
                flash('success', 'Added to cart.');
                break;
            }
        }
    }
    header('Location: index.php?page=sales');
    exit;
}

// Remove
if (isset($_GET['remove'])) {
    $pid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$pid]);
    flash('success', 'Item removed.');
    header('Location: index.php?page=sales');
    exit;
}

// Checkout
if (is_post() && isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        flash('error', 'Cart is empty.');
        header('Location: index.php?page=sales');
        exit;
    }
    $customerId = $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : null;
    $userId = current_user()['user_id'];

    // Re-validate stock
    foreach ($_SESSION['cart'] as $item) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['quantity'];
        $res = $conn->query("SELECT stock FROM products WHERE product_id={$pid}")->fetch_assoc();
        if (!$res || $res['stock'] < $qty) {
            flash('error', 'Insufficient stock for ' . $item['product_name']);
            header('Location: index.php?page=sales');
            exit;
        }
    }

    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Create sale
    $stmt = $conn->prepare('INSERT INTO sales (user_id, customer_id, total_amount) VALUES (?,?,?)');
    $stmt->bind_param('iid', $userId, $customerId, $total);
    $stmt->execute();
    $saleId = $stmt->insert_id;

    // Details + stock update + inventory transaction out
    $detailStmt = $conn->prepare('INSERT INTO sale_details (sale_id, product_id, quantity, price) VALUES (?,?,?,?)');
    foreach ($_SESSION['cart'] as $item) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['quantity'];
        $price = (float)$item['price'];
        $detailStmt->bind_param('iiid', $saleId, $pid, $qty, $price);
        $detailStmt->execute();
        $conn->query("UPDATE products SET stock = stock - {$qty} WHERE product_id = {$pid}");
        $conn->query("INSERT INTO inventory_transactions (product_id, quantity, transaction_type) VALUES ({$pid}, {$qty}, 'out')");
    }

    $_SESSION['cart'] = [];
    flash('success', 'Sale completed.');
    header('Location: index.php?page=sales');
    exit;
}

$recentSales = $conn->query('
    SELECT s.sale_id, s.total_amount, s.sale_date, c.customer_name, u.username
    FROM sales s
    LEFT JOIN customers c ON c.customer_id = s.customer_id
    JOIN users u ON u.user_id = s.user_id
    ORDER BY s.sale_id DESC
    LIMIT 10
')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <h3>Sales</h3>
    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Add to Cart</h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="add_to_cart" value="1">
                        <div class="col-12">
                            <label class="form-label">Product</label>
                            <select class="form-select" name="product_id" required>
                                <option value="">Select product</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?php echo $p['product_id']; ?>">
                                        <?php echo sanitize($p['product_name']); ?> (Stock: <?php echo $p['stock']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Quantity</label>
                            <input class="form-control" type="number" name="quantity" min="1" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Cart</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th class="text-end"></th></tr></thead>
                            <tbody>
                            <?php $cartTotal = 0; foreach ($_SESSION['cart'] as $item): $line = $item['price'] * $item['quantity']; $cartTotal += $line; ?>
                                <tr>
                                    <td><?php echo sanitize($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo number_format($line, 2); ?></td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-danger" href="index.php?page=sales&remove=<?php echo $item['product_id']; ?>">Remove</a></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($_SESSION['cart'])): ?>
                                <tr><td colspan="5" class="text-center text-muted">No items.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="fw-bold">Total: <?php echo number_format($cartTotal, 2); ?></p>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="checkout" value="1">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Customer (optional)</label>
                            <select class="form-select" name="customer_id">
                                <option value="">Walk-in</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c['customer_id']; ?>"><?php echo sanitize($c['customer_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-success" type="submit">Checkout</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Recent Sales</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Total</th><th>Customer</th><th>Cashier</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentSales as $s): ?>
                            <tr>
                                <td><?php echo $s['sale_id']; ?></td>
                                <td><?php echo number_format($s['total_amount'], 2); ?></td>
                                <td><?php echo sanitize($s['customer_name'] ?? 'Walk-in'); ?></td>
                                <td><?php echo sanitize($s['username']); ?></td>
                                <td><?php echo $s['sale_date']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

