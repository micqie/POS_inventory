<?php
// CRUD customers
if (is_post()) {
    $name = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    if ($name === '') {
        flash('error', 'Customer name is required.');
    } else {
        if (isset($_POST['customer_id'])) {
            $id = (int)$_POST['customer_id'];
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
    <form method="post">
        <input type="hidden" name="customer_id" value="<?php echo $editItem['customer_id'] ?? ''; ?>">
        <label>Name</label>
        <input type="text" name="customer_name" required value="<?php echo sanitize($editItem['customer_name'] ?? ''); ?>">
        <label>Contact</label>
        <input type="text" name="contact_number" value="<?php echo sanitize($editItem['contact_number'] ?? ''); ?>">
        <button class="btn" type="submit"><?php echo $editItem ? 'Update' : 'Add'; ?></button>
        <?php if ($editItem): ?>
            <a class="btn secondary" href="index.php?page=customers">Cancel</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Contact</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?php echo $c['customer_id']; ?></td>
                    <td><?php echo sanitize($c['customer_name']); ?></td>
                    <td><?php echo sanitize($c['contact_number']); ?></td>
                    <td><?php echo $c['created_at']; ?></td>
                    <td>
                        <a class="btn secondary" href="index.php?page=customers&edit=<?php echo $c['customer_id']; ?>">Edit</a>
                        <a class="btn danger" href="index.php?page=customers&delete=<?php echo $c['customer_id']; ?>" onclick="return confirm('Delete customer?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

