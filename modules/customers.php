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
    <h3>Customers</h3>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="customer_id" value="<?php echo $editItem['customer_id'] ?? ''; ?>">
                <div class="col-12 col-md-6">
                    <label class="form-label">Name</label>
                    <input class="form-control" type="text" name="customer_name" required value="<?php echo sanitize($editItem['customer_name'] ?? ''); ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Contact</label>
                    <input class="form-control" type="text" name="contact_number" value="<?php echo sanitize($editItem['contact_number'] ?? ''); ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?php echo $editItem ? 'Update' : 'Add'; ?></button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=customers">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Name</th><th>Contact</th><th>Created</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><?php echo $c['customer_id']; ?></td>
                            <td><?php echo sanitize($c['customer_name']); ?></td>
                            <td><?php echo sanitize($c['contact_number']); ?></td>
                            <td><?php echo $c['created_at']; ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="index.php?page=customers&edit=<?php echo $c['customer_id']; ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="index.php?page=customers&delete=<?php echo $c['customer_id']; ?>" onclick="return confirm('Delete customer?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

