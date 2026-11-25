<?php
// Page header + navigation.
// Before including this file, a page sets:  $page_title  and  $active
$current_user = current_user();
$unread_count = $current_user ? count_unread_notifications($current_user['id']) : 0;

// Admins only see admin menu items (no public website menu)
$is_admin = $current_user && $current_user['role'] === 'admin';
$dashboard_url = $is_admin ? 'admin.php' : 'dashboard.php';

$nav_links = [
    'home'     => ['Home', 'index.php'],
    'donors'   => ['Find Donors', 'donors.php'],
    'requests' => ['Blood Requests', 'requests.php'],
    'about'    => ['About', 'about.php'],
    'contact'  => ['Contact', 'contact.php'],
];
if ($is_admin) {
    $nav_links = [
        'doners'   => ['Doners', 'admin-donors.php'],
        'requests' => ['Blood Requests', 'admin-requests.php'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> - <?= SITE_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="assets/js/tailwind-config.js"></script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

  <header class="sticky top-0 z-50 border-b border-gray-100 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
      <a href="<?= $dashboard_url ?>" class="flex items-center gap-2">
        <svg class="h-8 w-8 text-brand-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.2-.8.4C9.3 5 5 9.6 5 14a7 7 0 0 0 14 0c0-4.4-4.3-9-6.2-11.1-.2-.2-.5-.4-.8-.4z"/></svg>
        <span class="text-xl font-bold text-gray-900">Blood<span class="text-brand-600">Care</span></span>
      </a>

      <!-- Desktop menu -->
      <nav class="hidden items-center gap-6 md:flex" aria-label="Main">
        <?php foreach ($nav_links as $key => $link) { ?>
          <a href="<?= $link[1] ?>" class="text-sm <?= $active === $key ? 'font-semibold text-brand-600' : 'text-gray-600 hover:text-brand-600' ?>"><?= $link[0] ?></a>
        <?php } ?>
      </nav>

      <div class="hidden items-center gap-3 md:flex">
        <?php if ($current_user) { ?>
          <a href="notifications.php" class="text-sm font-medium text-gray-700 hover:text-brand-600">Notifications<?php if ($unread_count > 0) { ?> <span class="rounded-full bg-brand-600 px-2 py-0.5 text-xs font-semibold text-white"><?= $unread_count ?></span><?php } ?></a>
          <a href="<?= $dashboard_url ?>" class="text-sm font-medium text-gray-700 hover:text-brand-600">My Dashboard</a>
          <a href="logout.php" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Logout</a>
        <?php } else { ?>
          <a href="login.php" class="text-sm font-medium text-gray-700 hover:text-brand-600">Login</a>
          <a href="register.php" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Become a Donor</a>
        <?php } ?>
      </div>

      <button id="menu-btn" class="rounded-lg p-2 text-gray-700 hover:bg-gray-100 md:hidden" aria-label="Open menu">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden border-t border-gray-100 px-4 pb-4 pt-2 md:hidden">
      <?php foreach ($nav_links as $key => $link) { ?>
        <a href="<?= $link[1] ?>" class="block rounded-lg px-3 py-2 text-sm font-medium <?= $active === $key ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-50' ?>"><?= $link[0] ?></a>
      <?php } ?>
      <div class="mt-3 flex gap-2">
        <?php if ($current_user) { ?>
          <a href="notifications.php" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700">Alerts<?= $unread_count > 0 ? ' (' . $unread_count . ')' : '' ?></a>
          <a href="<?= $dashboard_url ?>" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700">Dashboard</a>
          <a href="logout.php" class="flex-1 rounded-lg bg-brand-600 px-4 py-2 text-center text-sm font-semibold text-white">Logout</a>
        <?php } else { ?>
          <a href="login.php" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700">Login</a>
          <a href="register.php" class="flex-1 rounded-lg bg-brand-600 px-4 py-2 text-center text-sm font-semibold text-white">Become a Donor</a>
        <?php } ?>
      </div>
    </div>
  </header>

  <?php show_flash(); ?>
