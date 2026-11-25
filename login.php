<?php
require_once 'includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile   = clean_mobile($_POST['mobile_no'] ?? '');
    $password = $_POST['password'] ?? '';

    // Find the user by mobile number, then check the password against the stored hash
    $stmt = $pdo->prepare('SELECT * FROM users WHERE mobile_no = ?');
    $stmt->execute([$mobile]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] == USER_INACTIVE) {
            $errors[] = 'Your account is inactive. Please contact the admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];

            if ($user['role'] === 'admin') {
                redirect('admin.php');
            }
            redirect('dashboard.php');
        }
    } else {
        // Same message for wrong number or wrong password (do not reveal which one)
        $errors[] = 'Wrong mobile number or password.';
    }
}

$page_title = 'Login';
$active = 'login';
require 'includes/header.php';
?>

  <main class="mx-auto max-w-md px-4 py-14">
    <div class="rounded-2xl bg-white p-8 shadow-sm">
      <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
      <p class="mt-1 text-sm text-gray-500">Login to manage your profile and blood requests.</p>

      <form method="post" action="login.php" class="mt-6">
        <?php show_errors($errors); ?>

        <div class="space-y-4">
          <div>
            <label for="mobile-no" class="mb-1 block text-sm font-medium text-gray-700">Mobile number</label>
            <input id="mobile-no" name="mobile_no" type="tel" value="<?= old('mobile_no') ?>" placeholder="01XXXXXXXXX" required class="<?= INPUT_CLASS ?>">
          </div>
          <div>
            <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password" required class="<?= INPUT_CLASS ?>">
          </div>
        </div>

        <button type="submit" class="mt-6 w-full rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700">Login</button>
      </form>

      <p class="mt-6 text-center text-sm text-gray-500">New here? <a href="register.php" class="font-semibold text-brand-600 hover:underline">Become a donor</a></p>
    </div>
  </main>

<?php require 'includes/footer.php'; ?>
