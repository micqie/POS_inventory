<?php
// Sales flow with session cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle AJAX request for sale details
if (isset($_GET['ajax']) && $_GET['ajax'] === 'sale_details' && isset($_GET['sale_id'])) {
    $saleId = (int)$_GET['sale_id'];

    // Get sale info
    $stmt = $conn->prepare('
        SELECT s.sale_id, s.total_amount, s.sale_date,
               c.customer_name, u.username
        FROM sales s
        LEFT JOIN customers c ON c.customer_id = s.customer_id
        JOIN users u ON u.user_id = s.user_id
        WHERE s.sale_id = ?
    ');
    $stmt->bind_param('i', $saleId);
    $stmt->execute();
    $saleInfo = $stmt->get_result()->fetch_assoc();

    // Get sale details with product_name
    $detailStmt = $conn->prepare('
        SELECT sd.quantity, sd.price, sd.product_name
        FROM sale_details sd
        WHERE sd.sale_id = ?
        ORDER BY sd.product_name
    ');
    $detailStmt->bind_param('i', $saleId);
    $detailStmt->execute();
    $details = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'saleInfo' => $saleInfo,
        'details' => $details
    ]);
    exit;
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

    $customerName = trim($_POST['customer_name'] ?? '');
    $customerId = null;

    // If customer name is provided but not in dropdown, create new customer
    if ($customerName !== '') {
        // Check if customer exists
        $checkStmt = $conn->prepare('SELECT customer_id FROM customers WHERE customer_name = ?');
        $checkStmt->bind_param('s', $customerName);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            // Customer exists, get ID
            $customerRow = $result->fetch_assoc();
            $customerId = $customerRow['customer_id'];
        } else {
            // Create new customer
            $insertStmt = $conn->prepare('INSERT INTO customers (customer_name) VALUES (?)');
            $insertStmt->bind_param('s', $customerName);
            if ($insertStmt->execute()) {
                $customerId = $insertStmt->insert_id;
                flash('info', 'New customer created: ' . $customerName);
            }
        }
    } else {
        // Use dropdown selection
        $customerId = $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : null;
    }

    $currentUser = current_user();
    $userId = $currentUser ? (int)($currentUser['user_id'] ?? $currentUser['id'] ?? 0) : 0;

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
    $detailStmt = $conn->prepare('INSERT INTO sale_details (sale_id, product_name, quantity, price, product_id) VALUES (?,?,?,?,?)');
    foreach ($_SESSION['cart'] as $item) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['quantity'];
        $price = (float)$item['price'];
        $productName = $item['product_name'];

        // Insert into sale_details with product_name
        $detailStmt->bind_param('isidd', $saleId, $productName, $qty, $price, $pid);
        $detailStmt->execute();

        // Update stock
        $conn->query("UPDATE products SET stock = stock - {$qty} WHERE product_id = {$pid}");

        // Record inventory transaction
        $conn->query("INSERT INTO inventory_transactions (product_id, quantity, transaction_type) VALUES ({$pid}, {$qty}, 'out')");
    }

    $_SESSION['cart'] = [];
    flash('success', 'Sale completed.');
    header('Location: index.php?page=sales');
    exit;
}

$recentSales = $conn->query('
    SELECT s.sale_id, s.total_amount, s.sale_date,
           c.customer_name, u.username
    FROM sales s
    LEFT JOIN customers c ON c.customer_id = s.customer_id
    JOIN users u ON u.user_id = s.user_id
    ORDER BY s.sale_id DESC
    LIMIT 10
')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <div class="page-header">
        <h1 class="page-title">Sales</h1>
        <p class="page-subtitle">Process sales and manage transactions</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cart-plus"></i> Add to Cart
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="add_to_cart" value="1">
                    <div class="form-group">
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
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input class="form-control" type="number" name="quantity" min="1" required placeholder="Enter quantity">
                    </div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-plus-circle"></i> Add to Cart
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-cart-check"></i> Shopping Cart
            </div>
            <div class="card-body">
                <?php
                $cartTotal = 0;
                foreach ($_SESSION['cart'] as $item):
                    $line = $item['price'] * $item['quantity'];
                    $cartTotal += $line;
                endforeach;
                ?>

                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="text-center text-muted" style="padding: var(--spacing-xl);">
                        <i class="bi bi-cart-x" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                        <p>Your cart is empty. Add products to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container" style="margin-bottom: var(--spacing-lg);">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $item): $line = $item['price'] * $item['quantity']; ?>
                                    <tr>
                                        <td><strong><?php echo sanitize($item['product_name']); ?></strong></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><strong>$<?php echo number_format($line, 2); ?></strong></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-danger" href="index.php?page=sales&remove=<?php echo $item['product_id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="padding: var(--spacing-lg); background: var(--bg-secondary); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: var(--font-size-lg); font-weight: 600;">Total Amount:</span>
                            <span style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--primary-600);">$<?php echo number_format($cartTotal, 2); ?></span>
                        </div>
                    </div>

                    <form method="post">
                        <input type="hidden" name="checkout" value="1">

                        <!-- Customer Input Section -->
                        <div class="form-group">
                            <label class="form-label">Customer Name (Optional)</label>
                            <input class="form-control" type="text" name="customer_name" placeholder="Enter new customer name" value="">
                            <small class="text-muted">Enter new customer name or select from dropdown below</small>
                        </div>

                        <div class="form-group" style="margin-top: var(--spacing-md);">
                            <label class="form-label">Or Select Existing Customer</label>
                            <select class="form-select" name="customer_id">
                                <option value="">Select existing customer (optional)</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c['customer_id']; ?>"><?php echo sanitize($c['customer_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Leave both empty for walk-in customer</small>
                        </div>

                        <button class="btn btn-success w-100" type="submit" style="margin-top: var(--spacing-md);">
                            <i class="bi bi-check-circle"></i> Complete Checkout
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-receipt"></i> Recent Sales
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Total Amount</th>
                            <th>Customer</th>
                            <th>User</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSales)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No sales recorded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $s): ?>
                                <tr class="clickable-row" data-sale-id="<?php echo $s['sale_id']; ?>" style="cursor: pointer;">
                                    <td><?php echo $s['sale_id']; ?></td>
                                    <td><strong style="color: var(--success);">$<?php echo number_format($s['total_amount'], 2); ?></strong></td>
                                    <td><?php echo sanitize($s['customer_name'] ?? 'Walk-in'); ?></td>
                                    <td><?php echo sanitize($s['username']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($s['sale_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Sale Details Modal -->
<!-- Sale Details Modal -->
<div id="saleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: white; border-radius: 12px; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <!-- Modal Header -->
        <div style="background: linear-gradient(135deg, #4e54c8, #8f94fb); color: white; padding: 20px; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;">
                    <i class="bi bi-receipt"></i> Sale Details
                </h3>
            </div>
            <button onclick="closeModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 5px;">&times;</button>
        </div>

        <!-- Modal Body -->
        <div style="padding: 25px; max-height: calc(90vh - 150px); overflow-y: auto;">
            <!-- Loading State -->
            <div id="modalLoading" style="text-align: center; padding: 40px 0;">
                <div style="border: 4px solid #f3f3f3; border-top: 4px solid #4e54c8; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <h4 style="color: #666;">Loading sale details...</h4>
            </div>

            <!-- Content State -->
            <div id="modalContent" style="display: none;">
                <!-- Customer Info -->
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; border: 1px solid #e9ecef;">
                    <div style="display: flex; align-items: center; margin-bottom: 5px;">


                    </div>  
                    <div>
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 4px;">Customer Name</div>
                        <div style="display: flex; align-items: center; font-size: 1rem;">
                            <i class="bi bi-person-fill" style="color: #4e54c8; margin-right: 8px;"></i>
                            <span id="modalCustomer" style="font-weight: 500; color: #333;">Loading...</span>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div style="margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 style="margin: 0; font-size: 1.2rem; color: #333;">
                            <i class="bi bi-cart-check" style="margin-right: 8px;"></i>
                            Purchased Products
                        </h4>
                        <span id="productCount" style="background: #4e54c8; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem;">0 items</span>
                    </div>

                    <div style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f5f5f5;">
                                <tr>
                                    <th style="padding: 16px; text-align: left; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Product Name</th>
                                    <th style="padding: 16px; text-align: center; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Quantity</th>
                                    <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Price</th>
                                    <th style="padding: 16px; text-align: right; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="modalProductsBody">
                                <!-- Products will be inserted here -->
                            </tbody>
                            <tfoot style="background: #f5f5f5; border-top: 2px solid #e0e0e0;">
                                <tr>
                                    <td colspan="3" style="padding: 16px; text-align: right; font-weight: 600; color: #333;">Total Amount:</td>
                                    <td style="padding: 16px; text-align: right;">
                                        <span id="modalProductsTotal" style="font-weight: 600; color: #28a745; font-size: 1.2rem;">$0.00</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.clickable-row {
    transition: background-color 0.2s;
}

.clickable-row:hover {
    background-color: rgba(78, 84, 200, 0.1);
}

#modalProductsBody tr {
    transition: background-color 0.2s;
}

#modalProductsBody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Custom scrollbar */
#saleModal > div {
    scrollbar-width: thin;
    scrollbar-color: #4e54c8 #f1f1f1;
}

#saleModal > div::-webkit-scrollbar {
    width: 8px;
}

#saleModal > div::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#saleModal > div::-webkit-scrollbar-thumb {
    background: #4e54c8;
    border-radius: 4px;
}

#saleModal > div::-webkit-scrollbar-thumb:hover {
    background: #3a3f9c;
}
</style>

<script>
// Simple modal functions
function showModal() {
    document.getElementById('saleModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('saleModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    resetModal();
}

function resetModal() {
    document.getElementById('modalContent').style.display = 'none';
    document.getElementById('modalLoading').style.display = 'block';

}

document.addEventListener('DOMContentLoaded', function() {
    // Add click event to table rows
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function() {
            const saleId = this.dataset.saleId;
            loadSaleDetails(saleId);
        });

        // Add hover effect
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(78, 84, 200, 0.1)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Close modal when clicking outside
    document.getElementById('saleModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
});

function loadSaleDetails(saleId) {
    // Show modal with loading state
    resetModal();
    showModal();

    // Fetch sale details via AJAX
    fetch(`index.php?page=sales&ajax=sale_details&sale_id=${saleId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Check for error response
            if (data.error) {
                throw new Error(data.message || 'Failed to load sale details');
            }

            // Validate data structure
            if (!data.saleInfo) {
                throw new Error('Invalid response data: missing saleInfo');
            }

            // Ensure details is an array (can be empty)
            if (!Array.isArray(data.details)) {
                data.details = [];
            }

            // Hide loading, show content
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';

            // Update customer name ONLY in customer info section (removed header line)
            const customerName = data.saleInfo.customer_name || 'Walk-in Customer';
            document.getElementById('modalCustomer').textContent = customerName;

            // Update products table
            let productsHTML = '';
            let total = 0;
            let itemCount = 0;

            if (data.details && data.details.length > 0) {
                data.details.forEach(item => {
                    const subtotal = parseFloat(item.quantity) * parseFloat(item.price);
                    total += subtotal;
                    itemCount += parseInt(item.quantity);

                    productsHTML += `
                        <tr>
                            <td style="padding: 16px; border-bottom: 1px solid #e0e0e0;">
                                <div style="font-weight: 500; color: #333;">
                                    ${escapeHtml(item.product_name || 'Unknown Product')}
                                </div>
                            </td>
                            <td style="padding: 16px; text-align: center; border-bottom: 1px solid #e0e0e0;">
                                <span style="background: #4e54c8; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.9rem;">
                                    ${item.quantity}
                                </span>
                            </td>
                            <td style="padding: 16px; text-align: right; border-bottom: 1px solid #e0e0e0; color: #333;">
                                $${parseFloat(item.price || 0).toFixed(2)}
                            </td>
                            <td style="padding: 16px; text-align: right; border-bottom: 1px solid #e0e0e0; font-weight: 500; color: #333;">
                                $${subtotal.toFixed(2)}
                            </td>
                        </tr>
                    `;
                });
            } else {
                productsHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #666;">
                            <i class="bi bi-exclamation-circle"></i>
                            No products found for this sale
                        </td>
                    </tr>
                `;
            }

            document.getElementById('modalProductsBody').innerHTML = productsHTML;
            document.getElementById('modalProductsTotal').textContent = '$' + total.toFixed(2);
            document.getElementById('productCount').textContent = itemCount + ' item' + (itemCount !== 1 ? 's' : '');

        })
        .catch(error => {
            console.error('Error loading sale details:', error);

            // Show error message
            document.getElementById('modalLoading').style.display = 'block';
            document.getElementById('modalLoading').innerHTML = `
                <div style="text-align: center; padding: 40px 0;">
                    <div style="background: #dc3545; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h4 style="color: #dc3545; margin-bottom: 10px;">Failed to load sale details</h4>
                    <p style="color: #666; margin-bottom: 10px;">${error.message || 'Please try again or check your connection'}</p>
                    <p style="color: #999; font-size: 0.85rem; margin-bottom: 20px;">Sale ID: ${saleId}</p>
                    <button onclick="loadSaleDetails(${saleId})" style="background: #4e54c8; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        <i class="bi bi-arrow-clockwise"></i> Retry
                    </button>
                </div>
            `;
        });
}
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
