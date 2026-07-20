<?php
/**
 * generate_accounts.php
 *
 * HOW TO USE (as the hospital owner):
 * 1. Edit the $accounts list below — one line per employee/staff member.
 *    - username: whatever login name you want to give them (must be unique)
 *    - password: a temporary password you assign them (they can be told to
 *      change it later — this demo system doesn't yet have a "change password"
 *      feature, so just tell them this is their login password)
 *    - full_name: their real name, shown on their dashboard
 *    - role: either 'employee' or 'staff'
 * 2. Save the file, then visit http://localhost/healthcare/generate_accounts.php
 * 3. It will print ready-to-run SQL INSERT statements below.
 * 4. Copy ALL of the printed SQL, paste it into phpMyAdmin's SQL tab
 *    (healthcare_system database), click Go.
 * 5. Delete this file afterward — it's just a one-time setup helper and
 *    should not stay on a real/public server since it can print SQL that
 *    reveals your account list.
 */

$accounts = [
    // username      password         full_name              role
    ['Ashit',        'Emp@1234',      'John Doe',            'employee'],
    ['Aryan',        'Emp@2345',      'Sarah Smith',         'employee'],
    ['Rajdeep',       'Emp@3456',      'Robert Jones',        'employee'],
    ['Sanket',        'Emp@4567',      'Ayesha Khan',         'employee'],
    ['Onkar',      'Emp@5678',      'Maria Garcia',        'employee'],

    ['Vijay',       'Staff@1234',    'Nurse Patel',         'staff'],
    ['Jayesh',      'Staff@2345',    'Nurse Wilson',        'staff'],
    ['Sahil',       'Staff@3456',    'Nurse Kumar',         'staff'],
    ['Tanuja',        'Staff@4567',    'Nurse Chen',          'staff'],
    ['Sakshi',       'Staff@5678',    'Nurse Moore',         'staff'],
];

$sqlStatements = [];
foreach ($accounts as [$username, $password, $fullName, $role]) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $usernameEsc = addslashes($username);
    $fullNameEsc = addslashes($fullName);
    $roleEsc     = addslashes($role);
    $sqlStatements[] =
        "INSERT INTO users (username, password_hash, full_name, role) VALUES " .
        "('{$usernameEsc}', '{$hash}', '{$fullNameEsc}', '{$roleEsc}');";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bulk Account Generator</title>
<style>
  body{ font-family: monospace; background:#0b2e4a; color:#e8f2fa; padding:30px; }
  h2{ color:#fff; }
  .credentials-table{ border-collapse:collapse; width:100%; margin-bottom:30px; background:#12395b; }
  .credentials-table th, .credentials-table td{ border:1px solid #1e5279; padding:8px 12px; text-align:left; }
  .credentials-table th{ background:#0d6ea8; color:#fff; }
  textarea{ width:100%; height:320px; background:#03192b; color:#7fffa0; padding:16px; border:none; border-radius:8px; font-size:13px; }
  .warning{ background:#5a2d0c; border:1px solid #c96a1f; padding:14px 18px; border-radius:8px; margin-bottom:20px; color:#ffd9a8; }
</style>
</head>
<body>

<h2>Generated Staff / Employee Accounts</h2>

<div class="warning">
  ⚠️ Delete this file (<code>generate_accounts.php</code>) right after copying the SQL below.
  It prints real login credentials in plain text and should never stay on a live server.
</div>

<h3>1. Credentials to hand out to your staff</h3>
<table class="credentials-table">
  <tr><th>Username</th><th>Password</th><th>Full Name</th><th>Role</th></tr>
  <?php foreach ($accounts as [$username, $password, $fullName, $role]): ?>
    <tr>
      <td><?= htmlspecialchars($username) ?></td>
      <td><?= htmlspecialchars($password) ?></td>
      <td><?= htmlspecialchars($fullName) ?></td>
      <td><?= htmlspecialchars($role) ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<h3>2. Copy ALL of this SQL and run it in phpMyAdmin (healthcare_system → SQL tab → Go)</h3>
<textarea readonly onclick="this.select()"><?= htmlspecialchars(implode("\n", $sqlStatements)) ?></textarea>

</body>
</html>
