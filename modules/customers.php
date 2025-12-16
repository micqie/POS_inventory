<?php
// CRUD customers
if (is_post()) {
    $name = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    if ($name === '') {
        flash('error', 'Customer name is required.');
    } else {
        $id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE customers SET customer_name=?, contact_number=? WHERE customer_id=?');
            $stmt->bind_param('ssi', $name, $contact, $id);
            $stmt->execute();
            flash('success', 'Customer updated.');
        } else {
            $stmt = $conn->prepare('INSERT INTO customers (customer_name, contact_number) VALUES (?, ?)');
            $stmt->bind_param('ss', $name, $contact);
            $stmt->execute();
            flash('success', 'Customer added.');
        }
    }
    header('Location: index.php?page=customers');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM customers WHERE customer_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash('success', 'Customer deleted.');
    header('Location: index.php?page=customers');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM customers WHERE customer_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();
}

$customers = $conn->query('SELECT * FROM customers ORDER BY customer_id DESC')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <div class="page-header">
        <h1 class="page-title">Customers</h1>
        <p class="page-subtitle">Manage customer information</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle"></i> <?php echo $editItem ? 'Edit Customer' : 'Add New Customer'; ?>
        </div>
        <div class="card-body">
            <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg);">
                <input type="hidden" name="customer_id" value="<?php echo $editItem['customer_id'] ?? ''; ?>">
                <div class="form-group">
                    <label class="form-label">Customer Name</label>
                    <input class="form-control" type="text" name="customer_name" required value="<?php echo sanitize($editItem['customer_name'] ?? ''); ?>" placeholder="Enter customer name">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input class="form-control" type="text" name="contact_number" value="<?php echo sanitize($editItem['contact_number'] ?? ''); ?>" placeholder="Phone or email">
                </div>
                <div class="form-group" style="display: flex; gap: var(--spacing-md); align-items: flex-end;">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-<?php echo $editItem ? 'check-circle' : 'plus-circle'; ?>"></i>
                        <?php echo $editItem ? 'Update Customer' : 'Add Customer'; ?>
                    </button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=customers">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> All Customers
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
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No customers found. Add your first customer above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td><?php echo $c['customer_id']; ?></td>
                                    <td><strong><?php echo sanitize($c['customer_name']); ?></strong></td>
                                    <td><?php echo sanitize($c['contact_number'] ?: '—'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=customers&edit=<?php echo $c['customer_id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a class="btn btn-sm btn-outline-danger" href="index.php?page=customers&delete=<?php echo $c['customer_id']; ?>" onclick="return confirm('Are you sure you want to delete this customer?');">
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
