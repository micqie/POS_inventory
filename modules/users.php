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
    <div class="page-header">
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage system users and permissions</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle"></i> <?php echo $editItem ? 'Edit User' : 'Add New User'; ?>
        </div>
        <div class="card-body">
            <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg);">
                <input type="hidden" name="user_id" value="<?php echo $editItem['user_id'] ?? ''; ?>">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input class="form-control" type="text" name="username" required value="<?php echo sanitize($editItem['username'] ?? ''); ?>" placeholder="Enter username">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="admin" <?php echo (($editItem['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        <option value="cashier" <?php echo (($editItem['role'] ?? '') === 'cashier') ? 'selected' : ''; ?>>Cashier</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Password <?php echo $editItem ? '<span style="font-weight: 400; color: var(--text-muted);">(leave blank to keep current)</span>' : ''; ?></label>
                    <input class="form-control" type="password" name="password" <?php echo $editItem ? '' : 'required'; ?> placeholder="Enter password">
                </div>
                <div class="form-group" style="display: flex; gap: var(--spacing-md); align-items: flex-end;">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-<?php echo $editItem ? 'check-circle' : 'plus-circle'; ?>"></i>
                        <?php echo $editItem ? 'Update User' : 'Add User'; ?>
                    </button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-secondary" href="index.php?page=users">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> All Users
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: var(--spacing-2xl);">
                                    <i class="bi bi-inbox" style="font-size: var(--font-size-3xl); display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                                    No users found. Add your first user above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['user_id']; ?></td>
                                    <td><strong><?php echo sanitize($u['username']); ?></strong></td>
                                    <td>
                                        <span style="padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 600; font-size: var(--font-size-xs); text-transform: uppercase; background: <?php echo $u['role'] === 'admin' ? 'var(--danger-light)' : 'var(--info-light)'; ?>; color: <?php echo $u['role'] === 'admin' ? '#991b1b' : '#1e40af'; ?>;">
                                            <?php echo $u['role']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=users&edit=<?php echo $u['user_id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($u['user_id'] !== (int)current_user()['user_id']): ?>
                                            <a class="btn btn-sm btn-outline-danger" href="index.php?page=users&delete=<?php echo $u['user_id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: var(--font-size-xs);">Current user</span>
                                        <?php endif; ?>
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
