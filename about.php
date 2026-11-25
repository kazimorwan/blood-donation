<?php
require_once 'includes/functions.php';

$page_title = 'About';
$active = 'about';

// Compatibility table: patient group => groups it can receive from
$compatibility = [
    'A+'  => 'A+, A-, O+, O-',
    'A-'  => 'A-, O-',
    'B+'  => 'B+, B-, O+, O-',
    'B-'  => 'B-, O-',
    'AB+' => 'All groups',
    'AB-' => 'A-, B-, AB-, O-',
    'O+'  => 'O+, O-',
    'O-'  => 'O-',
];

require 'includes/header.php';
?>

  <main class="bg-white">
    <section class="hero-bg">
      <div class="mx-auto max-w-3xl px-4 py-16 text-center">
        <h1 class="text-4xl font-extrabold text-gray-900">About <span class="text-brand-600">Blood Care</span></h1>
        <p class="mt-4 text-gray-600">Blood Care is a community platform that makes it easy for people in need to find willing blood donors quickly &mdash; and for donors to help without any hassle.</p>
      </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-14">
      <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 p-6 shadow-sm">
          <h2 class="font-semibold text-brand-600">Our Mission</h2>
          <p class="mt-2 text-sm text-gray-600">Make sure no one waits for blood when a willing donor is nearby.</p>
        </div>
        <div class="rounded-2xl border border-gray-100 p-6 shadow-sm">
          <h2 class="font-semibold text-brand-600">Our Vision</h2>
          <p class="mt-2 text-sm text-gray-600">A connected community where finding blood takes minutes, not hours.</p>
        </div>
        <div class="rounded-2xl border border-gray-100 p-6 shadow-sm">
          <h2 class="font-semibold text-brand-600">Our Values</h2>
          <p class="mt-2 text-sm text-gray-600">Free for everyone, respectful of privacy, and built on voluntary donation.</p>
        </div>
      </div>
    </section>

    <section class="bg-gray-50 py-14">
      <div class="mx-auto max-w-3xl px-4">
        <h2 class="text-2xl font-bold text-gray-900">Who can donate?</h2>
        <ul class="mt-4 list-disc space-y-2 pl-5 text-gray-600">
          <li>Age between 18 and 60 years.</li>
          <li>Weight at least 50 kg.</li>
          <li>In good general health.</li>
          <li>At least <?= DONATION_GAP_DAYS ?> days since your last donation.</li>
        </ul>

        <h2 class="mt-10 text-2xl font-bold text-gray-900">Blood group compatibility</h2>
        <div class="mt-4 overflow-x-auto rounded-2xl bg-white shadow-sm">
          <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-100 text-gray-500">
              <tr><th class="px-4 py-3 font-medium">Patient group</th><th class="px-4 py-3 font-medium">Can receive from</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($compatibility as $group => $can_receive) { ?>
                <tr><td class="px-4 py-2 font-semibold text-brand-600"><?= e($group) ?></td><td class="px-4 py-2"><?= e($can_receive) ?></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

<?php require 'includes/footer.php'; ?>
