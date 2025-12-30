<?php
// CRUD for categories
if (is_post()) {
    $name = trim($_POST['category_name'] ?? '');
    if ($name === '') {
        flash('error', 'Category name is required.');
    } else {
        $id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE categories SET category_name=? WHERE category_id=?');
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            flash('success', 'Category updated.');
        } else {
            $stmt = $conn->prepare('INSERT INTO categories (category_name) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            flash('success', 'Category added.');
        }
    }
    header('Location: index.php?page=categories');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM categories WHERE category_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash('success', 'Category deleted.');
    header('Location: index.php?page=categories');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM categories WHERE category_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();
}

$categories = $conn->query('SELECT * FROM categories ORDER BY category_id DESC')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <div class="page-header">
        <h1 class="page-title">Categories</h1>
        <p class="page-subtitle">Organize products into categories</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle"></i> <?php echo $editItem ? 'Edit Category' : 'Add New Category'; ?>
        </div>
        <div class="card-body">
            <form method="post" style="display: flex; gap: var(--spacing-md); align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
                    <label class="form-label">Category Name</label>
                    <input class="form-control" type="text" name="category_name" required value="<?php echo sanitize($editItem['category_name'] ?? ''); ?>" placeholder="Enter category name">
                </div>
                <div style="display: flex; gap: var(--spacing-md);">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-<?php echo $editItem ? 'check-circle' : 'plus-circle'; ?>"></i>
                        <?php echo $editItem ? 'Update' : 'Add Category'; ?>
                    </button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=categories">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> All Categories
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            
                            <th>Name</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No categories found. Add your first category above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                 
                                    <td><strong><?php echo sanitize($cat['category_name']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=categories&edit=<?php echo $cat['category_id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a class="btn btn-sm btn-outline-danger" href="index.php?page=categories&delete=<?php echo $cat['category_id']; ?>" onclick="return confirm('Are you sure you want to delete this category?');">
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
