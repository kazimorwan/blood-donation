// Small browser-side script. All real work is done by PHP now.

// Mobile menu toggle
const menuBtn = document.getElementById('menu-btn');
const mobileMenu = document.getElementById('mobile-menu');
if (menuBtn && mobileMenu) {
  menuBtn.addEventListener('click', function () {
    mobileMenu.classList.toggle('hidden');
  });
}
