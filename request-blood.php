<?php
require_once 'includes/functions.php';
require_login();

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Read the form values
    $problem  = trim($_POST['patient_problem'] ?? '');
    $group    = $_POST['blood_group'] ?? '';
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $area_id  = (int) ($_POST['area_id'] ?? 0);
    $date     = $_POST['donation_date'] ?? '';
    $time     = $_POST['time'] ?? '';

    // 2. Validate
    if ($problem === '' || strlen($problem) > 255) {
        $errors[] = 'Please describe the patient problem and hospital (max 255 characters).';
    }
    if (!in_array($group, $blood_groups)) {
        $errors[] = 'Please choose a blood group.';
    }
    if (!isset($areas[$area_id])) {
        $errors[] = 'Please choose an area.';
    }
    if ($quantity < 1 || $quantity > 10) {
        $errors[] = 'Bags needed must be between 1 and 10.';
    }
    if ($date === '' || strtotime($date) === false || $date < date('Y-m-d')) {
        $errors[] = 'Please choose a valid date (today or later).';
    }
    if ($time !== '' && strtotime($time) === false) {
        $errors[] = 'Please enter a valid time.';
    }

    // 3. Save (status starts as "pending" until a donor accepts)
    if (count($errors) === 0) {
        $stmt = $pdo->prepare('INSERT INTO donation_requests (patient_problem, blood_seeker_id, blood_group, donation_date, `time`, quantity, area_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $problem,
            $user['id'],
            $group,
            $date,
            $time === '' ? null : $time,
            $quantity,
            $area_id,
            REQ_PENDING,
        ]);

        // 4. Notify available donors with the same blood group
        notify_matching_donors($pdo->lastInsertId(), $user['id'], $group, $date, $areas[$area_id]);

        set_flash('success', 'Your blood request is now public. Donors can accept it.');
        redirect('dashboard.php');
    }
}

$page_title = 'Request Blood';
$active = 'requests';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-2xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Request Blood</h1>
    <p class="mt-1 text-gray-500">Fill in the details. Matching donors will see your request. Your mobile number (<?= e($user['mobile_no']) ?>) is shared with logged-in donors.</p>

    <form method="post" action="request-blood.php" class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
      <?php show_errors($errors); ?>

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label for="patient-problem" class="mb-1 block text-sm font-medium text-gray-700">Patient problem and hospital name *</label>
          <textarea id="patient-problem" name="patient_problem" rows="3" maxlength="255" required placeholder="Example: Heart surgery. Dhaka Medical College Hospital, Ward 5." class="<?= INPUT_CLASS ?>"><?= old('patient_problem') ?></textarea>
        </div>

        <div>
          <label for="blood-group" class="mb-1 block text-sm font-medium text-gray-700">Blood group *</label>
          <select id="blood-group" name="blood_group" required class="<?= INPUT_CLASS ?>"><?= select_options($blood_groups, $_POST['blood_group'] ?? '', 'Select') ?></select>
        </div>

        <div>
          <label for="quantity" class="mb-1 block text-sm font-medium text-gray-700">Bags needed *</label>
          <input id="quantity" name="quantity" type="number" min="1" max="10" value="<?= old('quantity') ?>" required class="<?= INPUT_CLASS ?>">
        </div>

        <div class="sm:col-span-2">
          <label for="area-id" class="mb-1 block text-sm font-medium text-gray-700">Area *</label>
          <select id="area-id" name="area_id" required class="<?= INPUT_CLASS ?>"><?= area_options($areas, $_POST['area_id'] ?? '', 'Select') ?></select>
        </div>

        <div>
          <label for="donation-date" class="mb-1 block text-sm font-medium text-gray-700">Needed on *</label>
          <input id="donation-date" name="donation_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= old('donation_date') ?>" required class="<?= INPUT_CLASS ?>">
        </div>

        <div>
          <label for="time" class="mb-1 block text-sm font-medium text-gray-700">Time</label>
          <input id="time" name="time" type="time" value="<?= old('time') ?>" class="<?= INPUT_CLASS ?>">
        </div>
      </div>

      <button type="submit" class="mt-6 w-full rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700">Submit Request</button>
    </form>
  </main>

<?php require 'includes/footer.php'; ?>
