<?php
require_once 'includes/functions.php';
require_admin();

// ---------- Read filters from the URL (GET form) ----------
$search      = trim($_GET['q'] ?? '');
$group       = $_GET['group'] ?? '';
$area_id     = (int) ($_GET['area'] ?? 0);
$status      = (int) ($_GET['status'] ?? 0);      // 0 = all
$verified    = $_GET['verified'] ?? '';           // '' = all, '1' = yes, '0' = no
$availability = $_GET['availability'] ?? '';      // '' = all, 'yes', 'no'
$per_page    = (int) ($_GET['per_page'] ?? 10);
$page        = max(1, (int) ($_GET['page'] ?? 1));

if (!in_array($per_page, [10, 25, 50, 100])) {
    $per_page = 10;
}

// ---------- Build the WHERE part (values always go in as "?" parameters) ----------
$where  = "u.role = 'user'";
$params = [];

if ($search !== '') {
    $where .= ' AND (u.name LIKE ? OR u.mobile_no LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if (in_array($group, $blood_groups)) {
    $where .= ' AND u.blood_group = ?';
    $params[] = $group;
}
if ($area_id > 0) {
    $where .= ' AND u.area_id = ?';
    $params[] = $area_id;
}
if (in_array($status, [USER_ACTIVE, USER_UNAVAILABLE, USER_INACTIVE])) {
    $where .= ' AND u.status = ?';
    $params[] = $status;
}
if ($verified === '1' || $verified === '0') {
    $where .= ' AND u.is_verified = ?';
    $params[] = (int) $verified;
}

// "Available" = active and waited long enough since the last donation
$available_sql = 'u.status = ' . USER_ACTIVE . ' AND (u.last_donation_date IS NULL OR u.last_donation_date <= DATE_SUB(CURDATE(), INTERVAL ' . (int) DONATION_GAP_DAYS . ' DAY))';
if ($availability === 'yes') {
    $where .= ' AND ' . $available_sql;
} elseif ($availability === 'no') {
    $where .= ' AND NOT (' . $available_sql . ')';
}

// ---------- Count rows, then fetch only the current page ----------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$total_pages = max(1, (int) ceil($total / $per_page));
$page   = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// LIMIT/OFFSET are plain integers (cast above), so they are safe to put in the SQL
$stmt = $pdo->prepare("SELECT u.*, a.name AS area_name
                       FROM users u
                       LEFT JOIN areas a ON a.id = u.area_id
                       WHERE $where
                       ORDER BY u.name
                       LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$donors = $stmt->fetchAll();

// Link to another page number, keeping all current filters
function page_url($page_number) {
    $query = $_GET;
    $query['page'] = $page_number;
    return 'admin-donors.php?' . http_build_query($query);
}

$from = $total === 0 ? 0 : $offset + 1;
$to   = min($offset + $per_page, $total);

$page_title = 'Doners';
$active = 'doners';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Doners</h1>
    <p class="mt-1 text-gray-500">Search and filter all registered donors.</p>

    <!-- Search + filters -->
    <form method="get" action="admin-donors.php" class="mt-6 grid gap-4 rounded-2xl bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
      <div class="sm:col-span-2 lg:col-span-4">
        <label for="q" class="mb-1 block text-sm font-medium text-gray-700">Search</label>
        <input id="q" name="q" type="search" value="<?= e($search) ?>" placeholder="Name or mobile number" class="<?= INPUT_CLASS ?>">
      </div>
      <div>
        <label for="group" class="mb-1 block text-sm font-medium text-gray-700">Blood group</label>
        <select id="group" name="group" class="<?= INPUT_CLASS ?>"><?= select_options($blood_groups, $group, 'All groups') ?></select>
      </div>
      <div>
        <label for="area" class="mb-1 block text-sm font-medium text-gray-700">Area</label>
        <select id="area" name="area" class="<?= INPUT_CLASS ?>"><?= area_options($areas, $area_id, 'All areas') ?></select>
      </div>
      <div>
        <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
        <select id="status" name="status" class="<?= INPUT_CLASS ?>">
          <option value="0">All</option>
          <option value="<?= USER_ACTIVE ?>" <?= $status === USER_ACTIVE ? 'selected' : '' ?>>Active</option>
          <option value="<?= USER_UNAVAILABLE ?>" <?= $status === USER_UNAVAILABLE ? 'selected' : '' ?>>Unavailable</option>
          <option value="<?= USER_INACTIVE ?>" <?= $status === USER_INACTIVE ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div>
        <label for="verified" class="mb-1 block text-sm font-medium text-gray-700">Verified</label>
        <select id="verified" name="verified" class="<?= INPUT_CLASS ?>">
          <option value="">All</option>
          <option value="1" <?= $verified === '1' ? 'selected' : '' ?>>Verified</option>
          <option value="0" <?= $verified === '0' ? 'selected' : '' ?>>Not verified</option>
        </select>
      </div>
      <div>
        <label for="availability" class="mb-1 block text-sm font-medium text-gray-700">Availability</label>
        <select id="availability" name="availability" class="<?= INPUT_CLASS ?>">
          <option value="">All</option>
          <option value="yes" <?= $availability === 'yes' ? 'selected' : '' ?>>Available</option>
          <option value="no" <?= $availability === 'no' ? 'selected' : '' ?>>Not available</option>
        </select>
      </div>
      <div>
        <label for="per_page" class="mb-1 block text-sm font-medium text-gray-700">Rows per page</label>
        <select id="per_page" name="per_page" class="<?= INPUT_CLASS ?>">
          <?php foreach ([10, 25, 50, 100] as $n) { ?>
            <option value="<?= $n ?>" <?= $per_page === $n ? 'selected' : '' ?>><?= $n ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="flex items-end gap-2 sm:col-span-2">
        <button type="submit" class="flex-1 rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">Apply</button>
        <a href="admin-donors.php" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-center font-medium text-gray-700 hover:bg-gray-50">Reset</a>
      </div>
    </form>

    <!-- Table -->
    <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-gray-100 text-gray-500">
            <tr>
              <th class="py-2 pr-4 font-medium">#</th>
              <th class="py-2 pr-4 font-medium">Name</th>
              <th class="py-2 pr-4 font-medium">Mobile</th>
              <th class="py-2 pr-4 font-medium">Group</th>
              <th class="py-2 pr-4 font-medium">Area</th>
              <th class="py-2 pr-4 font-medium">Last donation</th>
              <th class="py-2 pr-4 font-medium">Availability</th>
              <th class="py-2 pr-4 font-medium">Verified</th>
              <th class="py-2 font-medium">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($donors as $i => $d) { ?>
              <tr>
                <td class="py-3 pr-4 text-gray-400"><?= $offset + $i + 1 ?></td>
                <td class="py-3 pr-4 font-medium text-gray-900"><?= e($d['name']) ?></td>
                <td class="py-3 pr-4"><?= e($d['mobile_no']) ?></td>
                <td class="py-3 pr-4 font-semibold text-brand-600"><?= e($d['blood_group']) ?></td>
                <td class="py-3 pr-4"><?= e($d['area_name'] ?: '-') ?></td>
                <td class="py-3 pr-4 whitespace-nowrap"><?= e($d['last_donation_date'] ?: 'Never') ?></td>
                <td class="py-3 pr-4">
                  <?php if (is_donor_available($d)) { ?>
                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Available</span>
                  <?php } else { ?>
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Not available</span>
                  <?php } ?>
                </td>
                <td class="py-3 pr-4"><?= $d['is_verified'] ? 'Yes' : 'No' ?></td>
                <td class="py-3"><?= $d['status'] == USER_ACTIVE ? 'Active' : ($d['status'] == USER_UNAVAILABLE ? 'Unavailable' : 'Inactive') ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <?php if ($total === 0) { ?>
        <p class="mt-4 text-center text-sm text-gray-500">No donors found. Try changing the filters.</p>
      <?php } ?>

      <!-- Pagination -->
      <div class="mt-6 flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-gray-500">Showing <?= $from ?> to <?= $to ?> of <?= $total ?> donors</p>

        <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
          <?php if ($page > 1) { ?>
            <a href="<?= e(page_url($page - 1)) ?>" class="rounded-lg border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-50">Previous</a>
          <?php } ?>

          <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++) { ?>
            <a href="<?= e(page_url($p)) ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>
               class="rounded-lg border px-3 py-1 text-sm <?= $p === $page ? 'border-brand-600 bg-brand-600 font-semibold text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?>"><?= $p ?></a>
          <?php } ?>

          <?php if ($page < $total_pages) { ?>
            <a href="<?= e(page_url($page + 1)) ?>" class="rounded-lg border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-50">Next</a>
          <?php } ?>
        </nav>
      </div>
    </section>
  </main>

<?php require 'includes/footer.php'; ?>
