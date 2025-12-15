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
    <h3>Suppliers</h3>
    <form method="post">
        <input type="hidden" name="supplier_id" value="<?php echo $editItem['supplier_id'] ?? ''; ?>">
        <label>Name</label>
        <input type="text" name="supplier_name" required value="<?php echo sanitize($editItem['supplier_name'] ?? ''); ?>">
        <label>Contact Info</label>
        <input type="text" name="contact_info" value="<?php echo sanitize($editItem['contact_info'] ?? ''); ?>">
        <button class="btn" type="submit"><?php echo $editItem ? 'Update' : 'Add'; ?></button>
        <?php if ($editItem): ?>
            <a class="btn secondary" href="index.php?page=suppliers">Cancel</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Contact</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><?php echo $s['supplier_id']; ?></td>
                    <td><?php echo sanitize($s['supplier_name']); ?></td>
                    <td><?php echo sanitize($s['contact_info']); ?></td>
                    <td><?php echo $s['created_at']; ?></td>
                    <td>
                        <a class="btn secondary" href="index.php?page=suppliers&edit=<?php echo $s['supplier_id']; ?>">Edit</a>
                        <a class="btn danger" href="index.php?page=suppliers&delete=<?php echo $s['supplier_id']; ?>" onclick="return confirm('Delete supplier?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

