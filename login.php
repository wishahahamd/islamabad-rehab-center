<?php
// Login Page
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            // Query user and resolve role details
            $stmt = $pdo->prepare("
                SELECT u.*, r.role_name 
                FROM users u
                JOIN sys_roles r ON u.role = r.role_key
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_active'] == 0) {
                    $error = 'Your account has been deactivated. Please contact support.';
                } elseif ($user['role'] === 'suspended') {
                    $error = 'Your account is currently suspended.';
                } else {
                    // Set session details
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_role_name'] = $user['role_name'];
                    
                    // Redirect to originally requested page or dashboard
                    $redirectUrl = $_SESSION['redirect_url'] ?? (BASE_URL . 'dashboard.php');
                    unset($_SESSION['redirect_url']);
                    header("Location: " . $redirectUrl);
                    exit;
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred during authentication. Please try again.';
        }
    }
}

// Fetch system settings for branding
$system_name = "Islamabad Rehab Center";
$system_logo = "https://cdn-icons-png.flaticon.com/512/3063/3063176.png";

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'system_name') $system_name = $row['setting_value'];
        if ($row['setting_key'] === 'system_logo') $system_logo = $row['setting_value'];
    }
    if (!empty($system_logo) && strpos($system_logo, 'http') !== 0) {
        $system_logo = BASE_URL . ltrim($system_logo, '/\\');
    }
} catch (Exception $e) {
    // Fail silently, fall back to defaults
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo sanitize($system_name); ?> | Log in</title>
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit:300,400,600,700|Inter:300,400,500,600&display=swap">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- AdminLTE 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4/dist/css/adminlte.min.css">
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/custom.css">
    <script>
        // Check local storage for theme preference immediately to avoid flash
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>
    <style>
        body.login-page {
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.05) 0%, rgba(255, 75, 0, 0.05) 100%), var(--bs-body-bg) !important;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-box animate-fade-in">
        <div class="card glass-panel border shadow-lg">
            <div class="card-header text-center py-4 border-bottom-0">
                <div class="h3 mb-0 text-decoration-none text-body">
                    <img src="<?php echo sanitize($system_logo); ?>" alt="System Logo" style="max-height: 60px;" class="mb-3 rounded bg-light p-1 shadow-sm"><br>
                    <b class="text-primary"><?php echo sanitize($system_name); ?></b>
                </div>
            </div>
            <div class="card-body login-card-body bg-transparent">
                <p class="login-box-msg text-secondary">Sign in to start your secure session</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo sanitize($error); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required autocomplete="username" value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                        <div class="input-group-text">
                            <span class="bi bi-envelope"></span>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required autocomplete="current-password">
                        <div class="input-group-text">
                            <span class="bi bi-lock-fill"></span>
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-8">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember">
                                <label class="form-check-label text-secondary" for="remember">
                                    Remember Me
                                </label>
                            </div>
                        </div>
                        <!-- /.col -->
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill">Sign In</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

                <div class="mt-4 text-center">
                    <button class="btn btn-outline-secondary btn-sm" onclick="toggleDarkMode()">
                        <i class="bi bi-sun-fill me-1" id="theme-toggle-icon"></i> Toggle Theme
                    </button>
                </div>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
    <!-- /.login-box -->

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDarkMode() {
            const html = document.documentElement;
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            updateToggleIcon(next);
        }

        function updateToggleIcon(theme) {
            const icon = document.getElementById('theme-toggle-icon');
            if (icon) {
                if (theme === 'dark') {
                    icon.className = 'bi bi-moon-fill me-1';
                } else {
                    icon.className = 'bi bi-sun-fill me-1';
                }
            }
        }

        // Sync toggle icon on load
        const savedTheme = localStorage.getItem('theme') || 'light';
        updateToggleIcon(savedTheme);
    </script>
</body>
</html>
