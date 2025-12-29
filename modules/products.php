<?php
// CRUD for products
$categories = $conn->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetch_all(MYSQLI_ASSOC);

if (is_post()) {
    $name = trim($_POST['product_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if ($name === '' || $categoryId === 0) {
        flash('error', 'Product name and category are required.');
    } else {
        $id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE products SET category_id=?, product_name=?, price=?, stock=? WHERE product_id=?');
            $stmt->bind_param('isdii', $categoryId, $name, $price, $stock, $id);
            $stmt->execute();
            flash('success', 'Product updated.');
        } else {
            $stmt = $conn->prepare('INSERT INTO products (category_id, product_name, price, stock) VALUES (?,?,?,?)');
            $stmt->bind_param('isdi', $categoryId, $name, $price, $stock);
            $stmt->execute();
            flash('success', 'Product added.');
        }
    }
    header('Location: index.php?page=products');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM products WHERE product_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash('success', 'Product deleted.');
    header('Location: index.php?page=products');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM products WHERE product_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();
}

$products = $conn->query('
    SELECT p.*, c.category_name
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    ORDER BY p.product_id DESC
')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <div class="page-header">
        <h1 class="page-title">Products</h1>
        <p class="page-subtitle">Manage your product catalog</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle"></i> <?php echo $editItem ? 'Edit Product' : 'Add New Product'; ?>
        </div>
        <div class="card-body">
            <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg); align-items: flex-end;">
                <input type="hidden" name="product_id" value="<?php echo $editItem['product_id'] ?? ''; ?>">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Product Name</label>
                    <input class="form-control" type="text" name="product_name" required value="<?php echo sanitize($editItem['product_name'] ?? ''); ?>" placeholder="Enter product name">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo isset($editItem['category_id']) && (int)$editItem['category_id'] === (int)$cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Price</label>
                    <input class="form-control" type="number" step="0.01" name="price" required value="<?php echo $editItem['price'] ?? '0'; ?>" placeholder="0.00">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Stock Quantity</label>
                    <input class="form-control" type="number" name="stock" required value="<?php echo $editItem['stock'] ?? '0'; ?>" placeholder="0">
                </div>
                <div class="form-group" style="display: flex; gap: var(--spacing-md); align-items: flex-end; margin-bottom: 0;">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-<?php echo $editItem ? 'check-circle' : 'plus-circle'; ?>"></i>
                        <?php echo $editItem ? 'Update Product' : 'Add Product'; ?>
                    </button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=products">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> All Products
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No products found. Add your first product above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?php echo $p['product_id']; ?></td>
                                    <td><strong><?php echo sanitize($p['product_name']); ?></strong></td>
                                    <td><?php echo sanitize($p['category_name']); ?></td>
                                    <td><strong>$<?php echo number_format($p['price'], 2); ?></strong></td>
                                    <td>
                                        <span style="padding: 4px 8px; border-radius: var(--radius-sm); background: <?php echo $p['stock'] > 10 ? 'var(--success-light)' : 'var(--warning-light)'; ?>; color: <?php echo $p['stock'] > 10 ? '#065f46' : '#92400e'; ?>; font-weight: 600;">
                                            <?php echo $p['stock']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=products&edit=<?php echo $p['product_id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a class="btn btn-sm btn-outline-danger" href="index.php?page=products&delete=<?php echo $p['product_id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
