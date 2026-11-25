<?php
require_once 'includes/functions.php';
require_login();

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $group         = $_POST['blood_group'] ?? '';
    $area_id       = (int) ($_POST['area_id'] ?? 0);
    $nid           = trim($_POST['nid'] ?? '');
    $last_donation = $_POST['last_donation_date'] ?? '';
    $new_password  = $_POST['new_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if (!in_array($group, $blood_groups)) {
        $errors[] = 'Please choose a blood group.';
    }
    if (!isset($areas[$area_id])) {
        $errors[] = 'Please choose an area.';
    }
    if ($nid !== '' && !preg_match('/^[0-9]{10,17}$/', $nid)) {
        $errors[] = 'NID must be 10 to 17 digits (or leave it empty).';
    }
    if ($last_donation !== '' && (strtotime($last_donation) === false || $last_donation > date('Y-m-d'))) {
        $errors[] = 'Last donation date cannot be in the future.';
    }
    // Password is optional here: leave it empty to keep the old one
    if ($new_password !== '' && strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }

    if (count($errors) === 0) {
        $stmt = $pdo->prepare('UPDATE users SET name = ?, blood_group = ?, area_id = ?, nid = ?, last_donation_date = ? WHERE id = ?');
        $stmt->execute([
            $name,
            $group,
            $area_id,
            $nid === '' ? null : $nid,
            $last_donation === '' ? null : $last_donation,
            $user['id'],
        ]);

        if ($new_password !== '') {
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user['id']]);
        }

        set_flash('success', 'Profile updated.');
        redirect('dashboard.php');
    }

    // Show what the user typed if there was an error
    $user['name'] = $name;
    $user['blood_group'] = $group;
    $user['area_id'] = $area_id;
    $user['nid'] = $nid;
    $user['last_donation_date'] = $last_donation;
}

$page_title = 'Edit Profile';
$active = 'dashboard';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-2xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
    <p class="mt-1 text-gray-500">Mobile: <?= e($user['mobile_no']) ?> (cannot be changed)</p>

    <form method="post" action="profile.php" class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
      <?php show_errors($errors); ?>

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Full name *</label>
          <input id="name" name="name" type="text" value="<?= e($user['name']) ?>" required class="<?= INPUT_CLASS ?>">
        </div>
        <div>
          <label for="blood-group" class="mb-1 block text-sm font-medium text-gray-700">Blood group *</label>
          <select id="blood-group" name="blood_group" required class="<?= INPUT_CLASS ?>"><?= select_options($blood_groups, $user['blood_group'], 'Select') ?></select>
        </div>
        <div>
          <label for="area-id" class="mb-1 block text-sm font-medium text-gray-700">Area *</label>
          <select id="area-id" name="area_id" required class="<?= INPUT_CLASS ?>"><?= area_options($areas, $user['area_id'], 'Select') ?></select>
        </div>
        <div>
          <label for="nid" class="mb-1 block text-sm font-medium text-gray-700">NID number</label>
          <input id="nid" name="nid" type="text" inputmode="numeric" value="<?= e($user['nid']) ?>" class="<?= INPUT_CLASS ?>">
        </div>
        <div>
          <label for="last-donation-date" class="mb-1 block text-sm font-medium text-gray-700">Last donation date</label>
          <input id="last-donation-date" name="last_donation_date" type="date" max="<?= date('Y-m-d') ?>" value="<?= e($user['last_donation_date']) ?>" class="<?= INPUT_CLASS ?>">
        </div>
        <div class="sm:col-span-2">
          <label for="new-password" class="mb-1 block text-sm font-medium text-gray-700">New password</label>
          <input id="new-password" name="new_password" type="password" placeholder="Leave empty to keep current" class="<?= INPUT_CLASS ?>">
        </div>
      </div>

      <div class="mt-6 flex gap-3">
        <button type="submit" class="flex-1 rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700">Save changes</button>
        <a href="dashboard.php" class="rounded-lg border border-gray-300 px-6 py-3 font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
      </div>
    </form>
  </main>

<?php require 'includes/footer.php'; ?>
