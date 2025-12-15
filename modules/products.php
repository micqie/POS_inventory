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
        if (isset($_POST['product_id'])) {
            $id = (int)$_POST['product_id'];
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
    <h3>Products</h3>
    <form method="post">
        <input type="hidden" name="product_id" value="<?php echo $editItem['product_id'] ?? ''; ?>">
        <label>Name</label>
        <input type="text" name="product_name" required value="<?php echo sanitize($editItem['product_name'] ?? ''); ?>">
        <label>Category</label>
        <select name="category_id" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo isset($editItem['category_id']) && (int)$editItem['category_id'] === (int)$cat['category_id'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($cat['category_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label>Price</label>
        <input type="number" step="0.01" name="price" required value="<?php echo $editItem['price'] ?? '0'; ?>">
        <label>Stock</label>
        <input type="number" name="stock" required value="<?php echo $editItem['stock'] ?? '0'; ?>">
        <button class="btn" type="submit"><?php echo $editItem ? 'Update' : 'Add'; ?></button>
        <?php if ($editItem): ?>
            <a class="btn secondary" href="index.php?page=products">Cancel</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?php echo $p['product_id']; ?></td>
                    <td><?php echo sanitize($p['product_name']); ?></td>
                    <td><?php echo sanitize($p['category_name']); ?></td>
                    <td><?php echo number_format($p['price'], 2); ?></td>
                    <td><?php echo $p['stock']; ?></td>
                    <td><?php echo $p['created_at']; ?></td>
                    <td>
                        <a class="btn secondary" href="index.php?page=products&edit=<?php echo $p['product_id']; ?>">Edit</a>
                        <a class="btn danger" href="index.php?page=products&delete=<?php echo $p['product_id']; ?>" onclick="return confirm('Delete product?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

