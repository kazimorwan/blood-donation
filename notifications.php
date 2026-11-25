<?php
require_once 'includes/functions.php';
require_login();

$user = current_user();

// ---------- Handle buttons (delete one, clear all) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete one notification (only my own)
    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0), $user['id']]);
        set_flash('success', 'Notification deleted.');
    }

    // Clear all my notifications
    if ($action === 'clear_all') {
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        set_flash('success', 'All notifications cleared.');
    }

    redirect('notifications.php');
}

// ---------- Load my notifications, then mark them as read ----------
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
$stmt->execute([$user['id']]);

$page_title = 'Notifications';
$active = 'notifications';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-2xl px-4 py-10">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
      <?php if (count($notifications) > 0) { ?>
        <form method="post" action="notifications.php" onsubmit="return confirm('Clear all notifications?')">
          <input type="hidden" name="action" value="clear_all">
          <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Clear all</button>
        </form>
      <?php } ?>
    </div>

    <?php if (count($notifications) === 0) { ?>
      <p class="mt-6 rounded-2xl bg-white p-6 text-center text-gray-500 shadow-sm">You have no notifications.</p>
    <?php } ?>

    <ul class="mt-6 space-y-3">
      <?php foreach ($notifications as $n) { ?>
        <li class="flex items-start justify-between gap-4 rounded-2xl bg-white p-4 shadow-sm">
          <div>
            <p class="text-sm font-medium text-gray-900"><?= e($n['message']) ?></p>
            <p class="mt-1 text-xs text-gray-400"><?= e($n['created_at']) ?></p>
            <?php if ($n['donation_request_id']) { ?>
              <a href="requests.php?request=<?= (int) $n['donation_request_id'] ?>#request-<?= (int) $n['donation_request_id'] ?>" class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:underline">View blood request</a>
            <?php } ?>
          </div>
          <form method="post" action="notifications.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
            <button type="submit" class="text-sm text-gray-400 hover:text-brand-600" aria-label="Delete notification">Delete</button>
          </form>
        </li>
      <?php } ?>
    </ul>
  </main>

<?php require 'includes/footer.php'; ?>
