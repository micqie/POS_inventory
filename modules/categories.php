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
    <h3>Categories</h3>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="post" class="row g-3">
        <input type="hidden" name="category_id" value="<?php echo $editItem['category_id'] ?? ''; ?>">
                <div class="col-12 col-md-8 col-lg-6">
                    <label class="form-label">Name</label>
                    <input class="form-control" type="text" name="category_name" required value="<?php echo sanitize($editItem['category_name'] ?? ''); ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?php echo $editItem ? 'Update' : 'Add'; ?></button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=categories">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Name</th><th>Created</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['category_id']; ?></td>
                            <td><?php echo sanitize($cat['category_name']); ?></td>
                            <td><?php echo $cat['created_at']; ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="index.php?page=categories&edit=<?php echo $cat['category_id']; ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="index.php?page=categories&delete=<?php echo $cat['category_id']; ?>" onclick="return confirm('Delete category?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

