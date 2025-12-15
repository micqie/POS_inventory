<?php
require_role('admin');

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] === 'cashier' ? 'cashier' : 'admin';
    $password = $_POST['password'] ?? '';

    if ($username === '' || ($password === '' && empty($_POST['user_id']))) {
        flash('error', 'Username and password are required.');
    } else {
        if (isset($_POST['user_id'])) {
            $id = (int)$_POST['user_id'];
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
    <form method="post">
        <input type="hidden" name="user_id" value="<?php echo $editItem['user_id'] ?? ''; ?>">
        <label>Username</label>
        <input type="text" name="username" required value="<?php echo sanitize($editItem['username'] ?? ''); ?>">
        <label>Role</label>
        <select name="role">
            <option value="admin" <?php echo (($editItem['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>admin</option>
            <option value="cashier" <?php echo (($editItem['role'] ?? '') === 'cashier') ? 'selected' : ''; ?>>cashier</option>
        </select>
        <label>Password <?php echo $editItem ? '(leave blank to keep current)' : ''; ?></label>
        <input type="password" name="password" <?php echo $editItem ? '' : 'required'; ?>>
        <button class="btn" type="submit"><?php echo $editItem ? 'Update' : 'Add'; ?></button>
        <?php if ($editItem): ?>
            <a class="btn secondary" href="index.php?page=users">Cancel</a>
        <?php endif; ?>
    </form>

    <table>
        <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['user_id']; ?></td>
                    <td><?php echo sanitize($u['username']); ?></td>
                    <td><?php echo $u['role']; ?></td>
                    <td><?php echo $u['created_at']; ?></td>
                    <td>
                        <a class="btn secondary" href="index.php?page=users&edit=<?php echo $u['user_id']; ?>">Edit</a>
                        <a class="btn danger" href="index.php?page=users&delete=<?php echo $u['user_id']; ?>" onclick="return confirm('Delete user?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

