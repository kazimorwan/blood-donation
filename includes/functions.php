<?php
// Shared helper functions. Every page includes this file first.

session_start();
require_once __DIR__ . '/db.php';

// ---------- Lists used in forms ----------

$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

// Areas from the database as [id => name]
$areas = $pdo->query('SELECT id, name FROM areas ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);

// Tailwind classes shared by all inputs
define('INPUT_CLASS', 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600');

// ---------- Small helpers ----------

// Escape text before printing it (protects from XSS)
function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Value the user typed earlier (so the form keeps its data after an error)
function old($name) {
    return e($_POST[$name] ?? '');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Print <option> tags for a simple list
function select_options($list, $selected, $placeholder) {
    $html = '<option value="">' . e($placeholder) . '</option>';
    foreach ($list as $item) {
        $is_selected = ($item === $selected) ? ' selected' : '';
        $html .= '<option value="' . e($item) . '"' . $is_selected . '>' . e($item) . '</option>';
    }
    return $html;
}

// Print <option> tags for areas ([id => name])
function area_options($areas, $selected, $placeholder) {
    $html = '<option value="">' . e($placeholder) . '</option>';
    foreach ($areas as $id => $name) {
        $is_selected = ((string) $id === (string) $selected) ? ' selected' : '';
        $html .= '<option value="' . (int) $id . '"' . $is_selected . '>' . e($name) . '</option>';
    }
    return $html;
}

function initials($name) {
    $parts = explode(' ', trim($name));
    $letters = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) {
        $letters .= strtoupper(substr($parts[1], 0, 1));
    }
    return $letters;
}

// Keep only digits: "01711-000001" -> "01711000001"
function clean_mobile($mobile) {
    return preg_replace('/[^0-9]/', '', $mobile);
}

// Bangladesh mobile number: 11 digits, starts with 01
function is_valid_mobile($mobile) {
    return preg_match('/^01[0-9]{9}$/', $mobile) === 1;
}

// ---------- Flash messages (shown once after a redirect) ----------

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash() {
    if (!isset($_SESSION['flash'])) {
        return;
    }
    $type = $_SESSION['flash']['type'];
    $message = $_SESSION['flash']['message'];
    unset($_SESSION['flash']);

    $color = ($type === 'success') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700';
    echo '<div class="mx-auto mt-4 max-w-6xl px-4"><div class="rounded-lg px-4 py-3 text-sm ' . $color . '">' . e($message) . '</div></div>';
}

// Print a list of validation errors
function show_errors($errors) {
    if (count($errors) === 0) {
        return;
    }
    echo '<div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><ul class="list-disc pl-4">';
    foreach ($errors as $error) {
        echo '<li>' . e($error) . '</li>';
    }
    echo '</ul></div>';
}

// ---------- Login helpers ----------

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Returns the logged-in user (array, with area_name) or null
function current_user() {
    global $pdo;
    if (!is_logged_in()) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT users.*, areas.name AS area_name FROM users LEFT JOIN areas ON areas.id = users.area_id WHERE users.id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ? $user : null;
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Please login first.');
        redirect('login.php');
    }
}

function require_admin() {
    require_login();
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        set_flash('error', 'You are not allowed to open that page.');
        redirect('index.php');
    }
}

// ---------- Donation rules ----------

// Date (Y-m-d) when the donor can donate again, or null if never donated
function next_donation_date($last_donation_date) {
    if (!$last_donation_date) {
        return null;
    }
    return date('Y-m-d', strtotime($last_donation_date . ' +' . DONATION_GAP_DAYS . ' days'));
}

// True if the donor is active AND waited long enough since the last donation
function is_donor_available($donor) {
    if ($donor['status'] != USER_ACTIVE) {
        return false;
    }
    $next = next_donation_date($donor['last_donation_date']);
    return $next === null || $next <= date('Y-m-d');
}

// ---------- Status labels ----------

function request_status_label($status) {
    $labels = [
        REQ_PENDING   => 'Pending',
        REQ_ONGOING   => 'Ongoing',
        REQ_ACCEPTED  => 'Accepted',
        REQ_COMPLETED => 'Completed',
        REQ_CANCELLED => 'Cancelled',
    ];
    return $labels[$status] ?? 'Unknown';
}

function request_status_badge($status) {
    $colors = [
        REQ_PENDING   => 'bg-orange-100 text-orange-700',
        REQ_ONGOING   => 'bg-blue-100 text-blue-700',
        REQ_ACCEPTED  => 'bg-green-100 text-green-700',
        REQ_COMPLETED => 'bg-gray-200 text-gray-700',
        REQ_CANCELLED => 'bg-red-100 text-red-700',
    ];
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-600';
    return '<span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ' . $color . '">' . e(request_status_label($status)) . '</span>';
}

function tx_status_label($status) {
    $labels = [TX_ACCEPTED => 'Accepted', TX_CANCELLED => 'Cancelled', TX_COMPLETED => 'Completed'];
    return $labels[$status] ?? 'Unknown';
}

// ---------- Blood request logic ----------

// Open requests = still need donors and the date is not over. Optional filters.
function get_open_requests($group, $area_id, $limit) {
    global $pdo;

    $sql = 'SELECT r.*, a.name AS area_name, u.name AS seeker_name, u.mobile_no AS seeker_mobile,
                   (SELECT COUNT(*) FROM donation_requests_transactions t
                    WHERE t.donation_request_id = r.id AND t.status = ' . TX_ACCEPTED . ') AS accepted_count
            FROM donation_requests r
            JOIN areas a ON a.id = r.area_id
            JOIN users u ON u.id = r.blood_seeker_id
            WHERE r.status IN (' . REQ_PENDING . ', ' . REQ_ONGOING . ') AND r.donation_date >= CURDATE()';
    $params = [];

    if ($group !== '') {
        $sql .= ' AND r.blood_group = ?';
        $params[] = $group;
    }
    if ($area_id > 0) {
        $sql .= ' AND r.area_id = ?';
        $params[] = $area_id;
    }
    $sql .= ' ORDER BY r.donation_date, r.id';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Did this user already accept this request?
function has_accepted($user_id, $request_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM donation_requests_transactions WHERE doner_id = ? AND donation_request_id = ? AND status = ' . TX_ACCEPTED);
    $stmt->execute([$user_id, $request_id]);
    return $stmt->fetchColumn() > 0;
}

// Can this user accept this request? ($r is a row of donation_requests)
function can_accept_request($user, $r) {
    if (!$user || $user['status'] != USER_ACTIVE) {
        return false;
    }
    if ($r['status'] != REQ_PENDING && $r['status'] != REQ_ONGOING) {
        return false;
    }
    if ($r['donation_date'] < date('Y-m-d')) {
        return false;
    }
    if ($r['blood_seeker_id'] == $user['id'] || $r['blood_group'] !== $user['blood_group']) {
        return false;
    }
    // The donor must be allowed to donate again by the request date
    $next = next_donation_date($user['last_donation_date']);
    if ($next !== null && $next > $r['donation_date']) {
        return false;
    }
    return !has_accepted($user['id'], $r['id']);
}

// Set the request status from the number of accepted donors:
// none = pending, some = ongoing, enough = accepted. Completed/cancelled stay as they are.
function refresh_request_status($request_id) {
    global $pdo;

    $stmt = $pdo->prepare('SELECT quantity, status FROM donation_requests WHERE id = ?');
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    if (!$request || $request['status'] == REQ_COMPLETED || $request['status'] == REQ_CANCELLED) {
        return;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM donation_requests_transactions WHERE donation_request_id = ? AND status = ' . TX_ACCEPTED);
    $stmt->execute([$request_id]);
    $accepted = $stmt->fetchColumn();

    if ($accepted == 0) {
        $new_status = REQ_PENDING;
    } elseif ($accepted >= $request['quantity']) {
        $new_status = REQ_ACCEPTED;
    } else {
        $new_status = REQ_ONGOING;
    }

    $stmt = $pdo->prepare('UPDATE donation_requests SET status = ? WHERE id = ?');
    $stmt->execute([$new_status, $request_id]);
}

// Cancel a request and all its accepted donors
function cancel_request($request_id) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE donation_requests SET status = ' . REQ_CANCELLED . ' WHERE id = ?');
    $stmt->execute([$request_id]);
    $stmt = $pdo->prepare('UPDATE donation_requests_transactions SET status = ' . TX_CANCELLED . ' WHERE donation_request_id = ? AND status = ' . TX_ACCEPTED);
    $stmt->execute([$request_id]);
}

// ---------- Notifications ----------

// Notify every available donor with the same blood group about a new request
function notify_matching_donors($request_id, $seeker_id, $group, $date, $area_name) {
    global $pdo;

    // Active donors with the same group (not the seeker)
    $stmt = $pdo->prepare('SELECT id, last_donation_date FROM users WHERE blood_group = ? AND status = ? AND id <> ?');
    $stmt->execute([$group, USER_ACTIVE, $seeker_id]);
    $donors = $stmt->fetchAll();

    $message = $group . ' blood needed in ' . $area_name . ' on ' . $date . '.';
    $insert = $pdo->prepare('INSERT INTO notifications (user_id, donation_request_id, message) VALUES (?, ?, ?)');

    foreach ($donors as $donor) {
        // Skip donors who cannot donate again by the request date
        $next = next_donation_date($donor['last_donation_date']);
        if ($next !== null && $next > $date) {
            continue;
        }
        $insert->execute([$donor['id'], $request_id, $message]);
    }
}

// How many unread notifications does this user have?
function count_unread_notifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

// ---------- HTML pieces used on several pages ----------

// One blood request card. $r comes from get_open_requests(). $viewer is the logged-in user or null.
// $highlight_id is an optional request id to highlight (used when opened from a notification).
function request_card($r, $viewer, $highlight_id = 0) {
    $still_needed = max(0, $r['quantity'] - $r['accepted_count']);
    $card_class = ($r['id'] == $highlight_id) ? 'border-2 border-brand-600 ring-4 ring-brand-100' : 'border border-gray-100';
    ?>
  <article id="request-<?= (int) $r['id'] ?>" class="flex flex-col rounded-2xl bg-white p-5 shadow-sm <?= $card_class ?>">
    <div class="flex items-start justify-between">
      <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-50 text-xl font-bold text-brand-600"><?= e($r['blood_group']) ?></div>
      <?= request_status_badge($r['status']) ?>
    </div>
    <h3 class="mt-4 font-semibold text-gray-900"><?= e($r['patient_problem'] ?: 'Blood needed') ?></h3>
    <p class="mt-2 text-sm text-gray-500"><?= e($r['quantity']) ?> bag(s) needed &middot; <?= e($still_needed) ?> more donor(s) wanted</p>
    <p class="text-sm text-gray-500"><?= e($r['area_name']) ?> &middot; <?= e($r['donation_date']) ?><?= $r['time'] ? ' at ' . e(substr($r['time'], 0, 5)) : '' ?></p>
    <p class="mt-1 text-xs text-gray-400">Requested by <?= e($r['seeker_name']) ?></p>

    <div class="mt-4">
      <?php if (!$viewer) { ?>
        <a href="login.php" class="block rounded-lg border border-brand-600 px-4 py-2 text-center text-sm font-semibold text-brand-600 hover:bg-brand-50">Login to help</a>
      <?php } else { ?>
        <?php if ($viewer['id'] == $r['blood_seeker_id']) { ?>
          <p class="text-center text-sm text-gray-500">This is your request.</p>
        <?php } elseif (has_accepted($viewer['id'], $r['id'])) { ?>
          <p class="rounded-lg bg-green-50 px-4 py-2 text-center text-sm font-semibold text-green-700">You accepted this request</p>
        <?php } elseif (can_accept_request($viewer, $r)) { ?>
          <form method="post" action="requests.php">
            <input type="hidden" name="action" value="accept">
            <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">I will donate</button>
          </form>
        <?php } ?>
        <a href="tel:<?= e($r['seeker_mobile']) ?>" class="mt-2 block text-center text-sm text-gray-600 hover:text-brand-600">Call <?= e($r['seeker_mobile']) ?></a>
      <?php } ?>
    </div>
  </article>
<?php }

// One donor card. $viewer is the logged-in user or null (guests do not see mobile numbers).
function donor_card($d, $viewer) {
    $available = is_donor_available($d);
    ?>
  <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex items-center gap-4">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 font-semibold text-gray-600"><?= e(initials($d['name'])) ?></div>
      <div class="flex-1">
        <h3 class="font-semibold text-gray-900"><?= e($d['name']) ?></h3>
        <p class="text-sm text-gray-500"><?= e($d['area_name'] ?: 'Area not set') ?></p>
      </div>
      <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-lg font-bold text-brand-600"><?= e($d['blood_group']) ?></div>
    </div>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
      <div class="flex gap-2">
        <?php if ($available) { ?>
          <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Available</span>
        <?php } else { ?>
          <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-500">Not available</span>
        <?php } ?>
        <?php if ($d['is_verified']) { ?>
          <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Verified</span>
        <?php } ?>
      </div>
      <span class="text-xs text-gray-400">Last donated: <?= e($d['last_donation_date'] ?: 'never') ?></span>
    </div>
    <?php if ($viewer) { ?>
      <a href="tel:<?= e($d['mobile_no']) ?>" class="mt-4 block rounded-lg border border-brand-600 px-4 py-2 text-center text-sm font-semibold text-brand-600 hover:bg-brand-50">Call <?= e($d['mobile_no']) ?></a>
    <?php } else { ?>
      <a href="login.php" class="mt-4 block rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-600 hover:bg-gray-50">Login to see contact</a>
    <?php } ?>
  </article>
<?php }
