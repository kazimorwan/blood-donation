<?php
require_once 'includes/functions.php';

// ---------- A donor clicked "I will donate" ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'accept') {
    require_login();
    $user = current_user();

    $stmt = $pdo->prepare('SELECT * FROM donation_requests WHERE id = ?');
    $stmt->execute([(int) ($_POST['request_id'] ?? 0)]);
    $request = $stmt->fetch();

    if ($request && can_accept_request($user, $request)) {
        // Save the acceptance, then update the request status
        $stmt = $pdo->prepare('INSERT INTO donation_requests_transactions (donation_request_id, doner_id, donation_date, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$request['id'], $user['id'], $request['donation_date'], TX_ACCEPTED]);
        refresh_request_status($request['id']);

        set_flash('success', 'Thank you! Please contact the patient family and donate on time.');
    } else {
        set_flash('error', 'You cannot accept this request.');
    }
    redirect('requests.php');
}

// ---------- Show the list ----------
$viewer  = current_user();
$group   = $_GET['group'] ?? '';
$area_id = (int) ($_GET['area'] ?? 0);

if (!in_array($group, $blood_groups)) {
    $group = '';
}
$requests = get_open_requests($group, $area_id, 0);

// Request to highlight (link from a notification: requests.php?request=5)
$highlight_id = (int) ($_GET['request'] ?? 0);

$page_title = 'Blood Requests';
$active = 'requests';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Blood Requests</h1>
        <p class="mt-1 text-gray-500">People who need blood. Can you help?</p>
      </div>
      <a href="request-blood.php" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Post a Request</a>
    </div>

    <form method="get" action="requests.php" class="mt-6 grid gap-4 rounded-2xl bg-white p-5 shadow-sm sm:grid-cols-3">
      <div>
        <label for="group" class="mb-1 block text-sm font-medium text-gray-700">Blood group</label>
        <select id="group" name="group" class="<?= INPUT_CLASS ?>"><?= select_options($blood_groups, $group, 'All groups') ?></select>
      </div>
      <div>
        <label for="area" class="mb-1 block text-sm font-medium text-gray-700">Area</label>
        <select id="area" name="area" class="<?= INPUT_CLASS ?>"><?= area_options($areas, $area_id, 'All areas') ?></select>
      </div>
      <div class="flex items-end">
        <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">Filter</button>
      </div>
    </form>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($requests as $r) { request_card($r, $viewer, $highlight_id); } ?>
    </div>

    <?php if (count($requests) === 0) { ?>
      <p class="mt-10 rounded-2xl bg-white p-8 text-center text-gray-500">No open requests match your filters.</p>
    <?php } ?>
  </main>

<?php require 'includes/footer.php'; ?>
