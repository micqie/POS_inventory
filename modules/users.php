<?php
require_role('admin');

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] === 'cashier' ? 'cashier' : 'admin';
    $password = $_POST['password'] ?? '';

    if ($username === '' || ($password === '' && empty($_POST['user_id']))) {
        flash('error', 'Username and password are required.');
    } else {
        $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($id > 0) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET username=?, role=?, password=? WHERE user_id=?');
                $stmt->bind_param('sssi', $username, $role, $hash, $id);
            } else {
                $stmt = $conn->prepare('UPDATE users SET username=?, role=? WHERE user_id=?');
                $stmt->bind_param('ssi', $username, $role, $id);
            }
            $stmt->execute();
            flash('success', 'User updated.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?,?,?)');
            $stmt->bind_param('sss', $username, $hash, $role);
            $stmt->execute();
            flash('success', 'User added.');
        }
    }
    header('Location: index.php?page=users');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === (int)current_user()['user_id']) {
        flash('error', 'Cannot delete your own account.');
    } else {
        $stmt = $conn->prepare('DELETE FROM users WHERE user_id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('success', 'User deleted.');
    }
    header('Location: index.php?page=users');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM users WHERE user_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();
}

$users = $conn->query('SELECT * FROM users ORDER BY user_id DESC')->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <h3>Users</h3>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="user_id" value="<?php echo $editItem['user_id'] ?? ''; ?>">
                <div class="col-12 col-md-4">
                    <label class="form-label">Username</label>
                    <input class="form-control" type="text" name="username" required value="<?php echo sanitize($editItem['username'] ?? ''); ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="admin" <?php echo (($editItem['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>admin</option>
                        <option value="cashier" <?php echo (($editItem['role'] ?? '') === 'cashier') ? 'selected' : ''; ?>>cashier</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Password <?php echo $editItem ? '(leave blank to keep current)' : ''; ?></label>
                    <input class="form-control" type="password" name="password" <?php echo $editItem ? '' : 'required'; ?>>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?php echo $editItem ? 'Update' : 'Add'; ?></button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=users">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['user_id']; ?></td>
                            <td><?php echo sanitize($u['username']); ?></td>
                            <td><?php echo $u['role']; ?></td>
                            <td><?php echo $u['created_at']; ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="index.php?page=users&edit=<?php echo $u['user_id']; ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="index.php?page=users&delete=<?php echo $u['user_id']; ?>" onclick="return confirm('Delete user?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

