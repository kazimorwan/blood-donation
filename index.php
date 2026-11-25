<?php
require_once 'includes/functions.php';

$viewer = current_user();

// Numbers for the stats bar
$total_donors    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status IN (" . USER_ACTIVE . ', ' . USER_UNAVAILABLE . ')')->fetchColumn();
$total_donations = $pdo->query('SELECT COUNT(*) FROM donation_requests_transactions WHERE status = ' . TX_COMPLETED)->fetchColumn();
$total_completed = $pdo->query('SELECT COUNT(*) FROM donation_requests WHERE status = ' . REQ_COMPLETED)->fetchColumn();
$total_areas     = $pdo->query("SELECT COUNT(DISTINCT area_id) FROM users WHERE role = 'user'")->fetchColumn();

// Next 3 open requests
$latest_requests = get_open_requests('', 0, 3);

$page_title = 'Donate Blood, Save Lives';
$active = 'home';
require 'includes/header.php';
?>

  <main class="bg-white">
    <!-- Hero -->
    <section class="hero-bg">
      <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 md:grid-cols-2 md:py-24">
        <div>
          <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-700">Every drop counts</span>
          <h1 class="mt-4 text-4xl font-extrabold leading-tight text-gray-900 sm:text-5xl">
            Donate blood.<br><span class="text-brand-600">Save a life today.</span>
          </h1>
          <p class="mt-4 max-w-md text-gray-600">Blood Care connects willing donors with patients who urgently need blood. Join our community or find a donor near you in minutes.</p>
          <div class="mt-8 flex flex-wrap gap-3">
            <a href="register.php" class="rounded-lg bg-brand-600 px-6 py-3 font-semibold text-white shadow hover:bg-brand-700">Become a Donor</a>
            <a href="request-blood.php" class="rounded-lg border border-gray-300 bg-white px-6 py-3 font-semibold text-gray-700 hover:border-brand-600 hover:text-brand-600">Request Blood</a>
          </div>
        </div>

        <!-- Quick donor search -->
        <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-gray-100">
          <h2 class="text-lg font-bold text-gray-900">Find a donor quickly</h2>
          <form action="donors.php" method="get" class="mt-4 space-y-4">
            <div>
              <label for="q-group" class="mb-1 block text-sm font-medium text-gray-700">Blood group</label>
              <select id="q-group" name="group" class="<?= INPUT_CLASS ?>">
                <?= select_options($blood_groups, '', 'Any group') ?>
              </select>
            </div>
            <div>
              <label for="q-area" class="mb-1 block text-sm font-medium text-gray-700">Area</label>
              <select id="q-area" name="area" class="<?= INPUT_CLASS ?>">
                <?= area_options($areas, '', 'Any area') ?>
              </select>
            </div>
            <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 font-semibold text-white hover:bg-brand-700">Search Donors</button>
          </form>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="border-y border-gray-100 bg-gray-50">
      <div class="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-4 py-10 text-center md:grid-cols-4">
        <div><p class="text-3xl font-extrabold text-brand-600"><?= e($total_donors) ?></p><p class="text-sm text-gray-500">Registered Donors</p></div>
        <div><p class="text-3xl font-extrabold text-brand-600"><?= e($total_donations) ?></p><p class="text-sm text-gray-500">Successful Donations</p></div>
        <div><p class="text-3xl font-extrabold text-brand-600"><?= e($total_completed) ?></p><p class="text-sm text-gray-500">Requests Completed</p></div>
        <div><p class="text-3xl font-extrabold text-brand-600"><?= e($total_areas) ?></p><p class="text-sm text-gray-500">Areas Covered</p></div>
      </div>
    </section>

    <!-- Blood groups -->
    <section class="mx-auto max-w-6xl px-4 py-16">
      <h2 class="text-center text-3xl font-bold text-gray-900">Search by blood group</h2>
      <p class="mt-2 text-center text-gray-500">Click a group to see donors.</p>
      <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <?php foreach ($blood_groups as $group) { ?>
          <a href="donors.php?group=<?= urlencode($group) ?>" class="rounded-2xl border border-gray-100 p-6 text-center shadow-sm hover:border-brand-600 hover:shadow-md"><span class="text-3xl font-extrabold text-brand-600"><?= e($group) ?></span></a>
        <?php } ?>
      </div>
    </section>

    <!-- How it works -->
    <section class="bg-gray-50 py-16">
      <div class="mx-auto max-w-6xl px-4">
        <h2 class="text-center text-3xl font-bold text-gray-900">How it works</h2>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
          <div class="rounded-2xl bg-white p-6 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white">1</div>
            <h3 class="mt-4 font-semibold text-gray-900">Register</h3>
            <p class="mt-2 text-sm text-gray-500">Create a free profile with your blood group and area.</p>
          </div>
          <div class="rounded-2xl bg-white p-6 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white">2</div>
            <h3 class="mt-4 font-semibold text-gray-900">Request or Accept</h3>
            <p class="mt-2 text-sm text-gray-500">Post a blood request, or accept a request that matches your blood group.</p>
          </div>
          <div class="rounded-2xl bg-white p-6 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white">3</div>
            <h3 class="mt-4 font-semibold text-gray-900">Donate</h3>
            <p class="mt-2 text-sm text-gray-500">Meet at the hospital, donate safely, and save lives.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Open requests -->
    <section class="mx-auto max-w-6xl px-4 py-16">
      <div class="flex items-end justify-between">
        <h2 class="text-3xl font-bold text-gray-900">Open blood requests</h2>
        <a href="requests.php" class="text-sm font-semibold text-brand-600 hover:underline">View all &rarr;</a>
      </div>
      <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($latest_requests as $r) { request_card($r, $viewer); } ?>
      </div>
      <?php if (count($latest_requests) === 0) { ?>
        <p class="mt-8 text-center text-gray-500">No open requests right now.</p>
      <?php } ?>
    </section>

    <!-- Call to action -->
    <section class="mx-auto max-w-6xl px-4 pb-16">
      <div class="rounded-3xl bg-brand-600 px-6 py-12 text-center text-white">
        <h2 class="text-3xl font-bold">Ready to be someone's hero?</h2>
        <p class="mx-auto mt-2 max-w-md text-brand-100">It takes less than a minute to register. Your one donation can save up to three lives.</p>
        <a href="register.php" class="mt-6 inline-block rounded-lg bg-white px-6 py-3 font-semibold text-brand-700 hover:bg-brand-50">Register as a Donor</a>
      </div>
    </section>
  </main>

<?php require 'includes/footer.php'; ?>
