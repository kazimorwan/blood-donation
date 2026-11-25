<?php
require_once 'includes/functions.php';

// Already logged in? Go to the dashboard.
if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Read the form values
    $name          = trim($_POST['name'] ?? '');
    $mobile        = clean_mobile($_POST['mobile_no'] ?? '');
    $group         = $_POST['blood_group'] ?? '';
    $area_id       = (int) ($_POST['area_id'] ?? 0);
    $dob           = $_POST['dob'] ?? '';
    $nid           = trim($_POST['nid'] ?? '');
    $last_donation = $_POST['last_donation_date'] ?? '';
    $password      = $_POST['password'] ?? '';
    $confirm       = $_POST['confirm_password'] ?? '';
    $agree         = isset($_POST['agree']);

    // 2. Validate
    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if (!is_valid_mobile($mobile)) {
        $errors[] = 'Mobile number must be 11 digits and start with 01.';
    }
    if (!in_array($group, $blood_groups)) {
        $errors[] = 'Please choose a blood group.';
    }
    if (!isset($areas[$area_id])) {
        $errors[] = 'Please choose an area.';
    }
    if ($dob === '' || strtotime($dob) === false || strtotime($dob) > strtotime('-18 years')) {
        $errors[] = 'You must be at least 18 years old to donate.';
    }
    if ($nid !== '' && !preg_match('/^[0-9]{10,17}$/', $nid)) {
        $errors[] = 'NID must be 10 to 17 digits (or leave it empty).';
    }
    if ($last_donation !== '' && (strtotime($last_donation) === false || $last_donation > date('Y-m-d'))) {
        $errors[] = 'Last donation date cannot be in the future.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$agree) {
        $errors[] = 'Please tick the agreement box.';
    }

    // 3. Mobile number must be unique
    if (count($errors) === 0) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE mobile_no = ?');
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) {
            $errors[] = 'This mobile number is already registered. Please login.';
        }
    }

    // 4. Save the new user (password is hashed, never stored as plain text)
    if (count($errors) === 0) {
        $stmt = $pdo->prepare('INSERT INTO users (name, mobile_no, password, blood_group, area_id, dob, nid, last_donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $name,
            $mobile,
            password_hash($password, PASSWORD_DEFAULT),
            $group,
            $area_id,
            $dob,
            $nid === '' ? null : $nid,
            $last_donation === '' ? null : $last_donation,
        ]);

        set_flash('success', 'Registration successful! Please login.');
        redirect('login.php');
    }
}

$page_title = 'Become a Donor';
$active = 'register';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-2xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Become a Donor</h1>
    <p class="mt-1 text-gray-500">Create your profile. Already registered? <a href="login.php" class="font-semibold text-brand-600 hover:underline">Login</a></p>

    <form method="post" action="register.php" class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
      <?php show_errors($errors); ?>

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Full name *</label>
          <input id="name" name="name" type="text" value="<?= old('name') ?>" required class="<?= INPUT_CLASS ?>">
        </div>

        <div>
          <label for="mobile-no" class="mb-1 block text-sm font-medium text-gray-700">Mobile number * (used to login)</label>
          <input id="mobile-no" name="mobile_no" type="tel" value="<?= old('mobile_no') ?>" placeholder="01XXXXXXXXX" required class="<?= INPUT_CLASS ?>">
        </div>

        <div>
          <label for="nid" class="mb-1 block text-sm font-medium text-gray-700">NID number (optional)</label>
          <input id="nid" name="nid" type="text" inputmode="numeric" value="<?= old('nid') ?>" class="<?= INPUT_CLASS ?>">
        </div>

        <div>
          <label for="blood-group" class="mb-1 block text-sm font-medium text-gray-700">Blood group *</label>
          <select id="blood-group" name="blood_group" required class="<?= INPUT_CLASS ?>"><?= select_options($blood_groups, $_POST['blood_group'] ?? '', 'Select') ?></select>
        </div>

        <div>
          <label for="area-id" class="mb-1 block text-sm font-medium text-gray-700">Area *</label>
          <select id="area-id" name="area_id" required class="<?= INPUT_CLASS ?>"><?= area_options($areas, $_POST['area_id'] ?? '', 'Select') ?></select>
        </div>

        <div>
          <label for="dob" class="mb-1 block text-sm font-medium text-gray-700">Date of birth *</label>
          <input id="dob" name="dob" type="date" value="<?= old('dob') ?>" required class="<?= INPUT_CLASS ?>">
        </div>

        <div>
          <label for="last-donation-date" class="mb-1 block text-sm font-medium text-gray-700">Last donation date</label>
          <input id="last-donation-date" name="last_donation_date" type="date" max="<?= date('Y-m-d') ?>" value="<?= old('last_donation_date') ?>" class="<?= INPUT_CLASS ?>">
        </div>

        <div>
          <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password *</label>
          <input id="password" name="password" type="password" required class="<?= INPUT_CLASS ?>">
        </div>

        <div>
          <label for="confirm-password" class="mb-1 block text-sm font-medium text-gray-700">Confirm password *</label>
          <input id="confirm-password" name="confirm_password" type="password" required class="<?= INPUT_CLASS ?>">
        </div>

        <label class="flex items-start gap-2 text-sm text-gray-600 sm:col-span-2">
          <input type="checkbox" name="agree" required class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-600">
          I am 18 or older, healthy, and agree to be contacted by people who need blood. *
        </label>
      </div>

      <button type="submit" class="mt-6 w-full rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700">Create Account</button>
    </form>
  </main>

<?php require 'includes/footer.php'; ?>
