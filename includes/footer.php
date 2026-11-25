  <footer class="mt-16 bg-gray-900 text-gray-300">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
      <div>
        <a href="index.php" class="flex items-center gap-2">
          <svg class="h-8 w-8 text-brand-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.2-.8.4C9.3 5 5 9.6 5 14a7 7 0 0 0 14 0c0-4.4-4.3-9-6.2-11.1-.2-.2-.5-.4-.8-.4z"/></svg>
          <span class="text-xl font-bold text-white">Blood<span class="text-brand-500">Care</span></span>
        </a>
        <p class="mt-3 text-sm text-gray-400">Connecting blood donors with people in need. One donation can save up to three lives.</p>
      </div>
      <div>
        <h3 class="text-sm font-semibold text-white">Quick Links</h3>
        <ul class="mt-3 space-y-2 text-sm">
          <li><a href="index.php" class="hover:text-white">Home</a></li>
          <li><a href="donors.php" class="hover:text-white">Find Donors</a></li>
          <li><a href="requests.php" class="hover:text-white">Blood Requests</a></li>
          <li><a href="about.php" class="hover:text-white">About Us</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-sm font-semibold text-white">Get Involved</h3>
        <ul class="mt-3 space-y-2 text-sm">
          <li><a href="register.php" class="hover:text-white">Become a Donor</a></li>
          <li><a href="request-blood.php" class="hover:text-white">Request Blood</a></li>
          <li><a href="login.php" class="hover:text-white">Login</a></li>
          <li><a href="contact.php" class="hover:text-white">Contact</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-sm font-semibold text-white">Emergency Helpline</h3>
        <p class="mt-3 text-2xl font-bold text-brand-500">16000</p>
        <p class="mt-1 text-sm text-gray-400">Available 24/7 (demo number)</p>
      </div>
    </div>
    <div class="border-t border-gray-800 py-4 text-center text-xs text-gray-500">
      &copy; <?= date('Y') ?> <?= SITE_NAME ?>. A training project.
    </div>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>
