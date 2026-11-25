<?php
require_once 'includes/functions.php';
require_admin();

// ---------- Handle button clicks (all buttons are small POST forms) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    // Verify / un-verify a user (admins are not touched)
    if ($action === 'toggle_verify') {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 - is_verified WHERE id = ? AND role = 'user'");
        $stmt->execute([$id]);
        set_flash('success', 'Verification updated.');
    }

    // Activate (1) or deactivate (3) a user
    if ($action === 'set_user_status') {
        $status = (int) ($_POST['status'] ?? 0);
        if ($status === USER_ACTIVE || $status === USER_INACTIVE) {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'user'");
            $stmt->execute([$status, $id]);
            set_flash('success', 'User status updated.');
        }
    }

    // Delete a user (their requests and acceptances are deleted too)
    if ($action === 'delete_user') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
        $stmt->execute([$id]);
        set_flash('success', 'User deleted.');
    }

    // Cancel any request
    if ($action === 'cancel_request') {
        cancel_request($id);
        set_flash('success', 'Request cancelled.');
    }

    // Add a new area
    if ($action === 'add_area') {
        $area_name = trim($_POST['area_name'] ?? '');
        if ($area_name === '' || strlen($area_name) > 50) {
            set_flash('error', 'Area name is required (max 50 characters).');
        } else {
            $stmt = $pdo->prepare('INSERT INTO areas (name) VALUES (?)');
            $stmt->execute([$area_name]);
            set_flash('success', 'Area added.');
        }
    }

    redirect('admin.php');
}

// ---------- Data for the page ----------
$total_users    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$unverified     = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND is_verified = 0")->fetchColumn();
$open_requests  = $pdo->query('SELECT COUNT(*) FROM donation_requests WHERE status IN (' . REQ_PENDING . ', ' . REQ_ONGOING . ', ' . REQ_ACCEPTED . ')')->fetchColumn();
$completed      = $pdo->query('SELECT COUNT(*) FROM donation_requests WHERE status = ' . REQ_COMPLETED)->fetchColumn();

$users = $pdo->query("SELECT u.*, a.name AS area_name FROM users u LEFT JOIN areas a ON a.id = u.area_id WHERE u.role = 'user' ORDER BY u.id DESC LIMIT 10")->fetchAll();

$requests = $pdo->query('SELECT r.*, a.name AS area_name, u.name AS seeker_name, u.mobile_no AS seeker_mobile
                         FROM donation_requests r
                         JOIN areas a ON a.id = r.area_id
                         JOIN users u ON u.id = r.blood_seeker_id
                         ORDER BY r.id DESC LIMIT 10')->fetchAll();

$page_title = 'Admin';
$active = 'admin';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
    <p class="mt-1 text-gray-500">Manage users, blood requests and areas.</p>

    <!-- Stats -->
    <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
      <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Total users</p><p class="mt-1 text-3xl font-extrabold text-brand-600"><?= e($total_users) ?></p></div>
      <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Not verified</p><p class="mt-1 text-3xl font-extrabold text-orange-500"><?= e($unverified) ?></p></div>
      <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Active requests</p><p class="mt-1 text-3xl font-extrabold text-blue-600"><?= e($open_requests) ?></p></div>
      <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Completed</p><p class="mt-1 text-3xl font-extrabold text-green-600"><?= e($completed) ?></p></div>
    </div>

    <!-- Requests -->
    <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
      <h2 class="font-semibold text-gray-900">Blood requests (latest 10)</h2>
      <div class="mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-gray-100 text-gray-500">
            <tr>
              <th class="py-2 pr-4 font-medium">Seeker</th>
              <th class="py-2 pr-4 font-medium">Group</th>
              <th class="py-2 pr-4 font-medium">Area</th>
              <th class="py-2 pr-4 font-medium">Date</th>
              <th class="py-2 pr-4 font-medium">Status</th>
              <th class="py-2 font-medium">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($requests as $r) { ?>
              <tr>
                <td class="py-3 pr-4"><?= e($r['seeker_name']) ?><br><span class="text-gray-400"><?= e($r['seeker_mobile']) ?></span></td>
                <td class="py-3 pr-4 font-semibold text-brand-600"><?= e($r['blood_group']) ?> &times; <?= e($r['quantity']) ?></td>
                <td class="py-3 pr-4"><?= e($r['area_name']) ?></td>
                <td class="py-3 pr-4 whitespace-nowrap"><?= e($r['donation_date']) ?></td>
                <td class="py-3 pr-4"><?= request_status_badge($r['status']) ?></td>
                <td class="py-3">
                  <?php if ($r['status'] != REQ_COMPLETED && $r['status'] != REQ_CANCELLED) { ?>
                    <form method="post" action="admin.php" onsubmit="return confirm('Cancel this request?');">
                      <input type="hidden" name="action" value="cancel_request">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <button type="submit" class="rounded-lg border border-red-300 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Cancel</button>
                    </form>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <?php if (count($requests) === 0) { ?>
        <p class="mt-2 text-sm text-gray-500">No requests yet.</p>
      <?php } ?>
    </section>

    <!-- Users -->
    <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
      <h2 class="font-semibold text-gray-900">Users (latest 10)</h2>
      <div class="mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-gray-100 text-gray-500">
            <tr>
              <th class="py-2 pr-4 font-medium">Name</th>
              <th class="py-2 pr-4 font-medium">Mobile</th>
              <th class="py-2 pr-4 font-medium">Group</th>
              <th class="py-2 pr-4 font-medium">Area</th>
              <th class="py-2 pr-4 font-medium">Verified</th>
              <th class="py-2 pr-4 font-medium">Status</th>
              <th class="py-2 font-medium">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($users as $u) { ?>
              <tr>
                <td class="py-3 pr-4"><?= e($u['name']) ?></td>
                <td class="py-3 pr-4"><?= e($u['mobile_no']) ?></td>
                <td class="py-3 pr-4 font-semibold text-brand-600"><?= e($u['blood_group']) ?></td>
                <td class="py-3 pr-4"><?= e($u['area_name'] ?: '-') ?></td>
                <td class="py-3 pr-4"><?= $u['is_verified'] ? 'Yes' : 'No' ?></td>
                <td class="py-3 pr-4"><?= $u['status'] == USER_ACTIVE ? 'Active' : ($u['status'] == USER_UNAVAILABLE ? 'Unavailable' : 'Inactive') ?></td>
                <td class="py-3 whitespace-nowrap">
                  <form method="post" action="admin.php" class="inline">
                    <input type="hidden" name="action" value="toggle_verify">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="rounded-lg bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700"><?= $u['is_verified'] ? 'Unverify' : 'Verify' ?></button>
                  </form>
                  <form method="post" action="admin.php" class="inline">
                    <input type="hidden" name="action" value="set_user_status">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <input type="hidden" name="status" value="<?= $u['status'] == USER_INACTIVE ? USER_ACTIVE : USER_INACTIVE ?>">
                    <button type="submit" class="rounded-lg bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-300"><?= $u['status'] == USER_INACTIVE ? 'Activate' : 'Deactivate' ?></button>
                  </form>
                  <form method="post" action="admin.php" class="inline" onsubmit="return confirm('Delete this user and all their data?');">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="rounded-lg border border-red-300 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                  </form>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Areas -->
    <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
      <h2 class="font-semibold text-gray-900">Areas</h2>
      <p class="mt-3 text-sm text-gray-600"><?= e(implode(', ', $areas)) ?></p>
      <form method="post" action="admin.php" class="mt-4 flex max-w-md gap-2">
        <input type="hidden" name="action" value="add_area">
        <input name="area_name" type="text" maxlength="50" placeholder="New area name" required class="<?= INPUT_CLASS ?>" aria-label="New area name">
        <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700">Add</button>
      </form>
    </section>
  </main>

<?php require 'includes/footer.php'; ?>
