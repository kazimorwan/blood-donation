<?php
require_once 'includes/functions.php';

$page_title = 'Contact';
$active = 'contact';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-4xl px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-900">Contact Us</h1>
    <p class="mt-1 text-gray-500">Questions or feedback? Reach us using the details below.</p>

    <div class="mt-8 grid gap-6 md:grid-cols-3">
      <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Email</p><p class="font-medium">help@bloodcare.example</p></div>
      <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Phone</p><p class="font-medium">01700-000000</p></div>
      <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Address</p><p class="font-medium">123 Demo Road, Dhaka</p></div>
    </div>

    <div class="mt-6 rounded-2xl bg-brand-50 p-6">
      <h2 class="font-semibold text-brand-800">Emergency helpline</h2>
      <p class="mt-1 text-3xl font-extrabold text-brand-600">16000</p>
      <p class="mt-1 text-sm text-gray-600">Available 24/7 (demo number)</p>
    </div>
  </main>

<?php require 'includes/footer.php'; ?>
