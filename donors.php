<?php
require_once 'includes/functions.php';

$viewer = current_user();

// Read the filters from the URL (GET form)
$group     = $_GET['group'] ?? '';
$area_id   = (int) ($_GET['area'] ?? 0);
$only_free = isset($_GET['available']);

// Build the query step by step. Values always go in as "?" parameters (safe from SQL injection).
// Inactive users (status 3) are hidden.
$sql = "SELECT u.*, a.name AS area_name
        FROM users u
        LEFT JOIN areas a ON a.id = u.area_id
        WHERE u.role = 'user' AND u.status IN (" . USER_ACTIVE . ', ' . USER_UNAVAILABLE . ')';
$params = [];

if (in_array($group, $blood_groups)) {
    $sql .= ' AND u.blood_group = ?';
    $params[] = $group;
}
if ($area_id > 0) {
    $sql .= ' AND u.area_id = ?';
    $params[] = $area_id;
}
if ($only_free) {
    $sql .= ' AND u.status = ' . USER_ACTIVE . ' AND (u.last_donation_date IS NULL OR u.last_donation_date <= DATE_SUB(CURDATE(), INTERVAL ' . (int) DONATION_GAP_DAYS . ' DAY))';
}
$sql .= ' ORDER BY u.name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donors = $stmt->fetchAll();

$page_title = 'Find Donors';
$active = 'donors';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Find Blood Donors</h1>
    <p class="mt-1 text-gray-500">Filter by blood group and area to find a donor near you.</p>

    <!-- Filters -->
    <form method="get" action="donors.php" class="mt-6 grid gap-4 rounded-2xl bg-white p-5 shadow-sm sm:grid-cols-4">
      <div>
        <label for="group" class="mb-1 block text-sm font-medium text-gray-700">Blood group</label>
        <select id="group" name="group" class="<?= INPUT_CLASS ?>"><?= select_options($blood_groups, $group, 'All groups') ?></select>
      </div>
      <div>
        <label for="area" class="mb-1 block text-sm font-medium text-gray-700">Area</label>
        <select id="area" name="area" class="<?= INPUT_CLASS ?>"><?= area_options($areas, $area_id, 'All areas') ?></select>
      </div>
      <label class="flex items-end gap-2 pb-2 text-sm font-medium text-gray-700">
        <input type="checkbox" name="available" value="1" <?= $only_free ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-600">
        Only available donors
      </label>
      <div class="flex items-end">
        <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">Search</button>
      </div>
    </form>

    <p class="mt-6 text-sm text-gray-500"><?= count($donors) ?> donor(s) found</p>

    <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($donors as $d) { donor_card($d, $viewer); } ?>
    </div>

    <?php if (count($donors) === 0) { ?>
      <p class="mt-10 rounded-2xl bg-white p-8 text-center text-gray-500">No donors found. Try changing the filters.</p>
    <?php } ?>
  </main>

<?php require 'includes/footer.php'; ?>
