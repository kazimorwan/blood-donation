<?php
require_once 'includes/functions.php';
require_admin();

// ---------- Cancel a request (button is a small POST form) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'cancel_request') {
        cancel_request((int) ($_POST['id'] ?? 0));
        set_flash('success', 'Request cancelled.');
    }
    // Go back to the same page with the same filters
    redirect('admin-requests.php?' . ($_POST['return_query'] ?? ''));
}

// ---------- Read filters from the URL (GET form) ----------
$search   = trim($_GET['q'] ?? '');
$group    = $_GET['group'] ?? '';
$area_id  = (int) ($_GET['area'] ?? 0);
$status   = (int) ($_GET['status'] ?? 0);      // 0 = all
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';
$per_page = (int) ($_GET['per_page'] ?? 10);
$page     = max(1, (int) ($_GET['page'] ?? 1));

if (!in_array($per_page, [10, 25, 50, 100])) {
    $per_page = 10;
}
// Dates must look like YYYY-MM-DD, otherwise ignore them
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = '';
}

// ---------- Build the WHERE part (values always go in as "?" parameters) ----------
$where  = '1 = 1';
$params = [];

if ($search !== '') {
    $where .= ' AND (u.name LIKE ? OR u.mobile_no LIKE ? OR r.patient_problem LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if (in_array($group, $blood_groups)) {
    $where .= ' AND r.blood_group = ?';
    $params[] = $group;
}
if ($area_id > 0) {
    $where .= ' AND r.area_id = ?';
    $params[] = $area_id;
}
if ($status >= REQ_PENDING && $status <= REQ_CANCELLED) {
    $where .= ' AND r.status = ?';
    $params[] = $status;
}
if ($date_from !== '') {
    $where .= ' AND r.donation_date >= ?';
    $params[] = $date_from;
}
if ($date_to !== '') {
    $where .= ' AND r.donation_date <= ?';
    $params[] = $date_to;
}

// ---------- Count rows, then fetch only the current page ----------
$from_sql = 'FROM donation_requests r
             JOIN users u ON u.id = r.blood_seeker_id
             JOIN areas a ON a.id = r.area_id';

$stmt = $pdo->prepare("SELECT COUNT(*) $from_sql WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$total_pages = max(1, (int) ceil($total / $per_page));
$page   = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// LIMIT/OFFSET are plain integers (cast above), so they are safe to put in the SQL
$stmt = $pdo->prepare("SELECT r.*, a.name AS area_name, u.name AS seeker_name, u.mobile_no AS seeker_mobile,
                              (SELECT COUNT(*) FROM donation_requests_transactions t
                               WHERE t.donation_request_id = r.id AND t.status <> " . TX_CANCELLED . ") AS donor_count
                       $from_sql
                       WHERE $where
                       ORDER BY r.id DESC
                       LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Link to another page number, keeping all current filters
function page_url($page_number) {
    $query = $_GET;
    $query['page'] = $page_number;
    return 'admin-requests.php?' . http_build_query($query);
}

$from = $total === 0 ? 0 : $offset + 1;
$to   = min($offset + $per_page, $total);
$return_query = http_build_query($_GET);

$page_title = 'Blood Requests';
$active = 'requests';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Blood Requests</h1>
    <p class="mt-1 text-gray-500">Search and filter all blood donation requests.</p>

    <!-- Search + filters -->
    <form method="get" action="admin-requests.php" class="mt-6 grid gap-4 rounded-2xl bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
      <div class="sm:col-span-2 lg:col-span-4">
        <label for="q" class="mb-1 block text-sm font-medium text-gray-700">Search</label>
        <input id="q" name="q" type="search" value="<?= e($search) ?>" placeholder="Seeker name, mobile or patient problem" class="<?= INPUT_CLASS ?>">
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
          <?php foreach ([REQ_PENDING, REQ_ONGOING, REQ_ACCEPTED, REQ_COMPLETED, REQ_CANCELLED] as $s) { ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(request_status_label($s)) ?></option>
          <?php } ?>
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
      <div>
        <label for="date_from" class="mb-1 block text-sm font-medium text-gray-700">Needed from</label>
        <input id="date_from" name="date_from" type="date" value="<?= e($date_from) ?>" class="<?= INPUT_CLASS ?>">
      </div>
      <div>
        <label for="date_to" class="mb-1 block text-sm font-medium text-gray-700">Needed until</label>
        <input id="date_to" name="date_to" type="date" value="<?= e($date_to) ?>" class="<?= INPUT_CLASS ?>">
      </div>
      <div class="flex items-end gap-2 sm:col-span-2">
        <button type="submit" class="flex-1 rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">Apply</button>
        <a href="admin-requests.php" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-center font-medium text-gray-700 hover:bg-gray-50">Reset</a>
      </div>
    </form>

    <!-- Table -->
    <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-gray-100 text-gray-500">
            <tr>
              <th class="py-2 pr-4 font-medium">#</th>
              <th class="py-2 pr-4 font-medium">Seeker</th>
              <th class="py-2 pr-4 font-medium">Group</th>
              <th class="py-2 pr-4 font-medium">Area</th>
              <th class="py-2 pr-4 font-medium">Patient problem</th>
              <th class="py-2 pr-4 font-medium">Date</th>
              <th class="py-2 pr-4 font-medium">Donors</th>
              <th class="py-2 pr-4 font-medium">Status</th>
              <th class="py-2 font-medium">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($requests as $i => $r) { ?>
              <tr>
                <td class="py-3 pr-4 text-gray-400"><?= $offset + $i + 1 ?></td>
                <td class="py-3 pr-4"><?= e($r['seeker_name']) ?><br><span class="text-gray-400"><?= e($r['seeker_mobile']) ?></span></td>
                <td class="py-3 pr-4 font-semibold text-brand-600"><?= e($r['blood_group']) ?> &times; <?= e($r['quantity']) ?></td>
                <td class="py-3 pr-4"><?= e($r['area_name']) ?></td>
                <td class="py-3 pr-4"><?= e($r['patient_problem'] ?: '-') ?></td>
                <td class="py-3 pr-4 whitespace-nowrap"><?= e($r['donation_date']) ?><?= $r['time'] ? '<br><span class="text-gray-400">' . e(substr($r['time'], 0, 5)) . '</span>' : '' ?></td>
                <td class="py-3 pr-4"><?= (int) $r['donor_count'] ?> / <?= (int) $r['quantity'] ?></td>
                <td class="py-3 pr-4"><?= request_status_badge($r['status']) ?></td>
                <td class="py-3">
                  <?php if ($r['status'] != REQ_COMPLETED && $r['status'] != REQ_CANCELLED) { ?>
                    <form method="post" action="admin-requests.php" onsubmit="return confirm('Cancel this request?');">
                      <input type="hidden" name="action" value="cancel_request">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <input type="hidden" name="return_query" value="<?= e($return_query) ?>">
                      <button type="submit" class="rounded-lg border border-red-300 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Cancel</button>
                    </form>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <?php if ($total === 0) { ?>
        <p class="mt-4 text-center text-sm text-gray-500">No requests found. Try changing the filters.</p>
      <?php } ?>

      <!-- Pagination -->
      <div class="mt-6 flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-gray-500">Showing <?= $from ?> to <?= $to ?> of <?= $total ?> requests</p>

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
