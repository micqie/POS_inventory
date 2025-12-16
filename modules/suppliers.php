<?php
// CRUD suppliers
if (is_post()) {
    $name = trim($_POST['supplier_name'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');

    if ($name === '') {
        flash('error', 'Supplier name is required.');
    } else {
        $id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE suppliers SET supplier_name=?, contact_info=? WHERE supplier_id=?');
            $stmt->bind_param('ssi', $name, $contact, $id);
            $stmt->execute();
            flash('success', 'Supplier updated.');
        } else {
            $stmt = $conn->prepare('INSERT INTO suppliers (supplier_name, contact_info) VALUES (?,?)');
            $stmt->bind_param('ss', $name, $contact);
            $stmt->execute();
            flash('success', 'Supplier added.');
        }
    }
    header('Location: index.php?page=suppliers');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM suppliers WHERE supplier_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash('success', 'Supplier deleted.');
    header('Location: index.php?page=suppliers');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM suppliers WHERE supplier_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();
}

$suppliers = $conn->query('SELECT * FROM suppliers ORDER BY supplier_id DESC')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <div class="page-header">
        <h1 class="page-title">Suppliers</h1>
        <p class="page-subtitle">Manage supplier information</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle"></i> <?php echo $editItem ? 'Edit Supplier' : 'Add New Supplier'; ?>
        </div>
        <div class="card-body">
            <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg);">
                <input type="hidden" name="supplier_id" value="<?php echo $editItem['supplier_id'] ?? ''; ?>">
                <div class="form-group">
                    <label class="form-label">Supplier Name</label>
                    <input class="form-control" type="text" name="supplier_name" required value="<?php echo sanitize($editItem['supplier_name'] ?? ''); ?>" placeholder="Enter supplier name">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Information</label>
                    <input class="form-control" type="text" name="contact_info" value="<?php echo sanitize($editItem['contact_info'] ?? ''); ?>" placeholder="Phone, email, or address">
                </div>
                <div class="form-group" style="display: flex; gap: var(--spacing-md); align-items: flex-end;">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-<?php echo $editItem ? 'check-circle' : 'plus-circle'; ?>"></i>
                        <?php echo $editItem ? 'Update Supplier' : 'Add Supplier'; ?>
                    </button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=suppliers">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> All Suppliers
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No suppliers found. Add your first supplier above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $s): ?>
                                <tr>
                                    <td><?php echo $s['supplier_id']; ?></td>
                                    <td><strong><?php echo sanitize($s['supplier_name']); ?></strong></td>
                                    <td><?php echo sanitize($s['contact_info'] ?: '—'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=suppliers&edit=<?php echo $s['supplier_id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a class="btn btn-sm btn-outline-danger" href="index.php?page=suppliers&delete=<?php echo $s['supplier_id']; ?>" onclick="return confirm('Are you sure you want to delete this supplier?');">
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
