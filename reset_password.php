<?php
/**
 * reset_password.php
 *
 * A simple owner-only tool to set/reset a staff or doctor's password
 * directly, without needing phpMyAdmin or manual hashing.
 *
 * SECURITY NOTE: Delete this file when you're done setting up accounts.
 * Anyone who can reach this URL can change any staff member's password.
 */
require_once __DIR__ . '/db_config.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $message = 'Please enter both a username and a password.';
        $messageType = 'danger';
    } else {
        try {
            $pdo = getDbConnection();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE username = :u');
            $stmt->execute([':hash' => $hash, ':u' => $username]);

            if ($stmt->rowCount() > 0) {
                $message = "Password updated successfully for '{$username}'.";
                $messageType = 'success';
            } else {
                $message = "No user found with username '{$username}'. Check spelling, or use this tool below to create a new account instead.";
                $messageType = 'warning';
            }
        } catch (Throwable $e) {
            $message = 'Something went wrong: ' . htmlspecialchars($e->getMessage());
            $messageType = 'danger';
        }
    }
}

// Handle creating a brand new account too, so this one tool covers both cases.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_new'])) {
    $username = trim($_POST['new_username'] ?? '');
    $password = $_POST['new_password'] ?? '';
    $fullName = trim($_POST['new_full_name'] ?? '');
    $role     = $_POST['new_role'] ?? 'employee';

    if ($username === '' || $password === '' || $fullName === '') {
        $message = 'Please fill in all fields to create a new account.';
        $messageType = 'danger';
    } else {
        try {
            $pdo  = getDbConnection();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role) VALUES (:u, :h, :n, :r)');
            $stmt->execute([':u' => $username, ':h' => $hash, ':n' => $fullName, ':r' => $role]);
            $message = "New account created for '{$fullName}' (username: {$username}).";
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Could not create account (username may already exist).';
            $messageType = 'danger';
        }
    }
}

// Pull the current list of accounts to show on screen for reference.
$existingUsers = [];
try {
    $pdo = getDbConnection();
    $existingUsers = $pdo->query('SELECT username, full_name, role FROM users ORDER BY role, full_name')->fetchAll();
} catch (Throwable $e) {
    // ignore for this simple tool
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Password Tool</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{ background:#f4f9fc; font-family: sans-serif; padding:40px 0; }
  .card{ border-radius:14px; border:none; box-shadow:0 8px 24px rgba(0,0,0,.08); }
</style>
</head>
<body>
<div class="container" style="max-width:700px;">

  <div class="alert alert-warning">
    ⚠️ Delete this file (<code>reset_password.php</code>) once you're done setting up
    accounts — anyone with this URL can change passwords.
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="card p-4 mb-4">
    <h5 class="mb-3">Reset an Existing Account's Password</h5>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="e.g. dr.yuvraj" required>
      </div>
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="text" name="password" class="form-control" placeholder="e.g. Yuvraj@123" required>
      </div>
      <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
  </div>

  <div class="card p-4 mb-4">
    <h5 class="mb-3">Or Create a Brand New Account</h5>
    <form method="POST">
      <input type="hidden" name="create_new" value="1">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="new_username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="text" name="new_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="new_full_name" class="form-control" placeholder="e.g. Dr. Jane Doe" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="new_role" class="form-select">
          <option value="employee">Doctor</option>
          <option value="staff">Staff / Nurse</option>
        </select>
      </div>
      <button type="submit" class="btn btn-success">Create Account</button>
    </form>
  </div>

  <div class="card p-4">
    <h5 class="mb-3">Current Accounts</h5>
    <?php if (!$existingUsers): ?>
      <p class="text-muted small">No accounts found, or database not connected.</p>
    <?php else: ?>
      <table class="table table-sm">
        <thead><tr><th>Username</th><th>Name</th><th>Role</th></tr></thead>
        <tbody>
        <?php foreach ($existingUsers as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td><?= htmlspecialchars($u['role'] === 'employee' ? 'Doctor' : ucfirst($u['role'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
