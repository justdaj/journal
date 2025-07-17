<?php
require 'bootstrap.php';

$error = $_GET['error'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (
        $username === $config['auth_user'] &&
        password_verify($password, $config['auth_pass'])
    ) {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;

        header('Location: /');
        exit;
    } else {
        header('Location: login.php?error=Invalid+credentials');
        exit;
    }
}
?>

<!doctype html>
<html lang="en-GB">
<?php include 'head.php'; ?>
<body>
    <main>
        <div class="login">
            <h1>Login</h1>
            <?php if ($error): ?>
                <p class="notice">❌ <?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="post" action="login.php">
                <p>
                    <label class="hidden" for="username">Username</label>
                    <input type="text" name="username" id="username" placeholder="Username" required>
                </p>

                <p>
                    <label class="hidden" for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Password" required><br>
                </p>

                <button type="submit">Log in</button>
            </form>
        </div>
    </main>
</body>
</html>