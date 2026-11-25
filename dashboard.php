<?php
require_once 'includes/functions.php';
require_login();

$user = current_user();

// ---------- Handle button clicks (each button is a small POST form) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    // Switch between active and unavailable
    if ($action === 'toggle_availability') {
        $new_status = ($user['status'] == USER_ACTIVE) ? USER_UNAVAILABLE : USER_ACTIVE;
        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$new_status, $user['id']]);
        set_flash('success', 'Your availability was updated.');
    }

    // Donor cancels an acceptance (id = transaction id)
    if ($action === 'cancel_acceptance') {
        $stmt = $pdo->prepare('SELECT donation_request_id FROM donation_requests_transactions WHERE id = ? AND doner_id = ? AND status = ?');
        $stmt->execute([$id, $user['id'], TX_ACCEPTED]);
        $tx = $stmt->fetch();

        if ($tx) {
            $stmt = $pdo->prepare('UPDATE donation_requests_transactions SET status = ? WHERE id = ?');
            $stmt->execute([TX_CANCELLED, $id]);
            refresh_request_status($tx['donation_request_id']);
            set_flash('success', 'You cancelled your acceptance.');
        }
    }

    // Seeker marks own request as completed (id = request id)
    if ($action === 'complete_request') {
        $stmt = $pdo->prepare('SELECT id FROM donation_requests WHERE id = ? AND blood_seeker_id = ? AND status IN (?, ?)');
        $stmt->execute([$id, $user['id'], REQ_ONGOING, REQ_ACCEPTED]);

        if ($stmt->fetch()) {
            // Donors who accepted get "last donation date = today"
            $stmt = $pdo->prepare('UPDATE users SET last_donation_date = CURDATE() WHERE id IN (SELECT doner_id FROM donation_requests_transactions WHERE donation_request_id = ? AND status = ?)');
            $stmt->execute([$id, TX_ACCEPTED]);

            $stmt = $pdo->prepare('UPDATE donation_requests_transactions SET status = ? WHERE donation_request_id = ? AND status = ?');
            $stmt->execute([TX_COMPLETED, $id, TX_ACCEPTED]);

            $stmt = $pdo->prepare('UPDATE donation_requests SET status = ? WHERE id = ?');
            $stmt->execute([REQ_COMPLETED, $id]);

            set_flash('success', 'Request marked as completed. Thank you to all donors!');
        }
    }

    // Seeker cancels own request (id = request id)
    if ($action === 'cancel_request') {
        $stmt = $pdo->prepare('SELECT id FROM donation_requests WHERE id = ? AND blood_seeker_id = ? AND status IN (?, ?, ?)');
        $stmt->execute([$id, $user['id'], REQ_PENDING, REQ_ONGOING, REQ_ACCEPTED]);

        if ($stmt->fetch()) {
            cancel_request($id);
            set_flash('success', 'Request cancelled.');
        }
    }

    redirect('dashboard.php');
}

// ---------- Data for the page ----------

// Requests I accepted as a donor
$stmt = $pdo->prepare('SELECT t.id AS tx_id, t.status AS tx_status, r.patient_problem, r.blood_group, r.donation_date, r.`time`,
                              a.name AS area_name, u.name AS seeker_name, u.mobile_no AS seeker_mobile
                       FROM donation_requests_transactions t
                       JOIN donation_requests r ON r.id = t.donation_request_id
                       JOIN areas a ON a.id = r.area_id
                       JOIN users u ON u.id = r.blood_seeker_id
                       WHERE t.doner_id = ?
                       ORDER BY r.donation_date DESC');
$stmt->execute([$user['id']]);
$my_donations = $stmt->fetchAll();

// Requests I posted as a seeker
$stmt = $pdo->prepare('SELECT r.*, a.name AS area_name FROM donation_requests r JOIN areas a ON a.id = r.area_id WHERE r.blood_seeker_id = ? ORDER BY r.created_at DESC, r.id DESC');
$stmt->execute([$user['id']]);
$my_requests = $stmt->fetchAll();

// Number of completed donations
$stmt = $pdo->prepare('SELECT COUNT(*) FROM donation_requests_transactions WHERE doner_id = ? AND status = ?');
$stmt->execute([$user['id'], TX_COMPLETED]);
$total_donations = $stmt->fetchColumn();

// Open requests with my blood group (not mine)
$matching = [];
foreach (get_open_requests($user['blood_group'], 0, 0) as $r) {
    if ($r['blood_seeker_id'] != $user['id']) {
        $matching[] = $r;
    }
}

$next_date = next_donation_date($user['last_donation_date']);
$can_donate_now = ($next_date === null || $next_date <= date('Y-m-d'));
$available = is_donor_available($user);

$unread_count = count_unread_notifications($user['id']);

$page_title = 'My Dashboard';
$active = 'dashboard';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Hello, <?= e(explode(' ', $user['name'])[0]) ?>!</h1>
    <p class="mt-1 text-gray-500">Thank you for being part of Blood Care.</p>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
      <!-- Profile card -->
      <section class="self-start rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-xl font-bold text-brand-600"><?= e(initials($user['name'])) ?></div>
          <div>
            <h2 class="font-semibold text-gray-900"><?= e($user['name']) ?></h2>
            <p class="text-sm text-gray-500"><?= e($user['area_name'] ?: 'Area not set') ?></p>
          </div>
          <div class="ml-auto flex h-14 w-14 items-center justify-center rounded-xl bg-brand-600 text-xl font-bold text-white"><?= e($user['blood_group']) ?></div>
        </div>

        <dl class="mt-6 space-y-3 text-sm">
          <div class="flex justify-between"><dt class="text-gray-500">Mobile</dt><dd class="font-medium"><?= e($user['mobile_no']) ?></dd></div>
          <div class="flex justify-between"><dt class="text-gray-500">Last donation</dt><dd class="font-medium"><?= e($user['last_donation_date'] ?: 'never') ?></dd></div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Verified</dt>
            <dd class="font-medium"><?= $user['is_verified'] ? 'Yes' : 'Not yet' ?></dd>
          </div>
          <div class="flex items-center justify-between">
            <dt class="text-gray-500">Status</dt>
            <dd>
              <?php if ($available) { ?>
                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Available</span>
              <?php } else { ?>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500">Not available</span>
              <?php } ?>
            </dd>
          </div>
        </dl>

        <a href="notifications.php" class="mt-6 block rounded-lg bg-brand-50 px-4 py-2 text-center text-sm font-semibold text-brand-700 hover:bg-brand-100">View notifications<?= $unread_count > 0 ? ' (' . $unread_count . ' new)' : '' ?></a>

        <form method="post" action="dashboard.php" class="mt-3">
          <input type="hidden" name="action" value="toggle_availability">
          <button type="submit" class="w-full rounded-lg border border-brand-600 px-4 py-2 text-sm font-semibold text-brand-600 hover:bg-brand-50">
            <?= $user['status'] == USER_ACTIVE ? 'Mark as unavailable' : 'Mark as available' ?>
          </button>
        </form>
        <a href="profile.php" class="mt-3 block text-center text-sm text-gray-500 hover:text-brand-600">Edit profile</a>
      </section>

      <div class="space-y-6 lg:col-span-2">
        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-3">
          <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Completed donations</p><p class="mt-1 text-3xl font-extrabold text-brand-600"><?= e($total_donations) ?></p></div>
          <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Lives saved (up to)</p><p class="mt-1 text-3xl font-extrabold text-brand-600"><?= e($total_donations * 3) ?></p></div>
          <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Next eligible date</p>
            <?php if ($can_donate_now) { ?>
              <p class="mt-1 text-xl font-extrabold text-green-600">Now</p>
            <?php } else { ?>
              <p class="mt-1 text-xl font-extrabold text-orange-500"><?= e($next_date) ?></p>
            <?php } ?>
          </div>
        </div>

        <!-- Requests I posted -->
        <section class="rounded-2xl bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">My blood requests</h2>
            <a href="request-blood.php" class="text-sm font-semibold text-brand-600 hover:underline">+ New request</a>
          </div>

          <ul class="mt-4 divide-y divide-gray-100 text-sm">
            <?php foreach ($my_requests as $r) {
                // Donors who accepted this request
                $stmt = $pdo->prepare('SELECT u.name, u.mobile_no FROM donation_requests_transactions t JOIN users u ON u.id = t.doner_id WHERE t.donation_request_id = ? AND t.status IN (?, ?)');
                $stmt->execute([$r['id'], TX_ACCEPTED, TX_COMPLETED]);
                $donors = $stmt->fetchAll();
                ?>
              <li class="py-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p class="font-medium"><?= e($r['blood_group']) ?> &middot; <?= e($r['quantity']) ?> bag(s) &middot; <?= e($r['area_name']) ?> &middot; <?= e($r['donation_date']) ?></p>
                    <p class="text-gray-500"><?= e($r['patient_problem']) ?></p>
                  </div>
                  <?= request_status_badge($r['status']) ?>
                </div>

                <?php if (count($donors) > 0) { ?>
                  <p class="mt-2 text-gray-600">Donors:
                    <?php foreach ($donors as $d) { ?>
                      <span class="mr-2 inline-block rounded bg-gray-50 px-2 py-0.5"><?= e($d['name']) ?> (<a href="tel:<?= e($d['mobile_no']) ?>" class="text-brand-600"><?= e($d['mobile_no']) ?></a>)</span>
                    <?php } ?>
                  </p>
                <?php } ?>

                <div class="mt-3 flex gap-2">
                  <?php if ($r['status'] == REQ_ONGOING || $r['status'] == REQ_ACCEPTED) { ?>
                    <form method="post" action="dashboard.php">
                      <input type="hidden" name="action" value="complete_request">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <button type="submit" class="rounded-lg bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700">Mark completed</button>
                    </form>
                  <?php } ?>
                  <?php if ($r['status'] == REQ_PENDING || $r['status'] == REQ_ONGOING || $r['status'] == REQ_ACCEPTED) { ?>
                    <form method="post" action="dashboard.php" onsubmit="return confirm('Cancel this request?');">
                      <input type="hidden" name="action" value="cancel_request">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <button type="submit" class="rounded-lg border border-red-300 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Cancel request</button>
                    </form>
                  <?php } ?>
                </div>
              </li>
            <?php } ?>
          </ul>
          <?php if (count($my_requests) === 0) { ?>
            <p class="mt-4 text-sm text-gray-500">You have not posted any request.</p>
          <?php } ?>
        </section>

        <!-- Requests I accepted -->
        <section class="rounded-2xl bg-white p-6 shadow-sm">
          <h2 class="font-semibold text-gray-900">Requests I accepted</h2>
          <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="border-b border-gray-100 text-gray-500">
                <tr>
                  <th class="py-2 pr-4 font-medium">Date</th>
                  <th class="py-2 pr-4 font-medium">Patient</th>
                  <th class="py-2 pr-4 font-medium">Contact</th>
                  <th class="py-2 pr-4 font-medium">Status</th>
                  <th class="py-2 font-medium"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <?php foreach ($my_donations as $d) { ?>
                  <tr>
                    <td class="py-3 pr-4 whitespace-nowrap"><?= e($d['donation_date']) ?><?= $d['time'] ? ' ' . e(substr($d['time'], 0, 5)) : '' ?></td>
                    <td class="py-3 pr-4"><?= e($d['patient_problem']) ?><br><span class="text-gray-400"><?= e($d['area_name']) ?></span></td>
                    <td class="py-3 pr-4"><?= e($d['seeker_name']) ?><br><a href="tel:<?= e($d['seeker_mobile']) ?>" class="text-brand-600"><?= e($d['seeker_mobile']) ?></a></td>
                    <td class="py-3 pr-4"><?= e(tx_status_label($d['tx_status'])) ?></td>
                    <td class="py-3">
                      <?php if ($d['tx_status'] == TX_ACCEPTED) { ?>
                        <form method="post" action="dashboard.php" onsubmit="return confirm('Cancel your acceptance?');">
                          <input type="hidden" name="action" value="cancel_acceptance">
                          <input type="hidden" name="id" value="<?= (int) $d['tx_id'] ?>">
                          <button type="submit" class="rounded-lg border border-red-300 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Cancel</button>
                        </form>
                      <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <?php if (count($my_donations) === 0) { ?>
            <p class="mt-2 text-sm text-gray-500">You have not accepted any request yet.</p>
          <?php } ?>
        </section>

        <!-- Matching requests -->
        <section class="rounded-2xl bg-white p-6 shadow-sm">
          <h2 class="font-semibold text-gray-900">Open requests for your blood group (<?= e($user['blood_group']) ?>)</h2>
          <ul class="mt-4 divide-y divide-gray-100 text-sm">
            <?php foreach ($matching as $r) { ?>
              <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                <div>
                  <p class="font-medium"><?= e($r['patient_problem']) ?></p>
                  <p class="text-gray-500"><?= e($r['area_name']) ?> &middot; <?= e($r['donation_date']) ?> &middot; <?= e($r['quantity']) ?> bag(s)</p>
                </div>
                <a href="requests.php?group=<?= urlencode($r['blood_group']) ?>" class="rounded-lg bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">View</a>
              </li>
            <?php } ?>
          </ul>
          <?php if (count($matching) === 0) { ?>
            <p class="mt-4 text-sm text-gray-500">No open requests for your blood group right now.</p>
          <?php } ?>
        </section>
      </div>
    </div>
  </main>

<?php require 'includes/footer.php'; ?>
