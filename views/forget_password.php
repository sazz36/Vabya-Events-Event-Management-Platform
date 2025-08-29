<?php
session_start();

$errors = [];
$success_message = '';

require_once __DIR__ . '/../models/User.php';
$userModel = new User();

if (isset($_GET['token'])) {
    // Password reset form
    $token = $_GET['token'];
    $user = $userModel->findByResetToken($token);
    if (!$user) {
        $errors['token'] = 'Invalid or expired reset link.';
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        if (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters.';
        } else if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        } else {
            $userModel->updatePassword($user['id'], $newPassword);
            $userModel->clearResetToken($user['id']);
            $success_message = 'Password changed successfully. You can now <a href=\'login.php\'>login</a>.';
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }
    if (empty($errors)) {
        $user = $userModel->findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $userModel->setResetToken($email, $token, $expires);
            // Send email (replace with real mail in production)
            $resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/forget_password.php?token=' . $token;
            if ($_SERVER['SERVER_NAME'] === 'localhost') {
                echo "<div class='alert alert-info mt-3' style='font-size:1.05em; border-radius:10px;'><strong>Password reset link (for testing):</strong><br><a href='$resetLink' style='word-break:break-all;'>$resetLink</a></div>";
            } else {
                @mail($email, 'Password Reset', "Click the following link to reset your password: $resetLink");
            }
        }
        $success_message = 'If this email address exists in our system, you will receive password reset instructions shortly.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - भव्य Event</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background: var(--neutral-light); font-family: 'Poppins', sans-serif; min-height: 100vh;">

<div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="row justify-content-center w-100">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-lg border-0 rounded-5 overflow-hidden animate-card">
                <!-- Header Section -->
                <div class="card-header text-center text-white py-4" style="background: linear-gradient(135deg, #1B3C53 0%, #456882 100%); border-bottom: none;">
                    <div class="avatar-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background: rgba(255,255,255,0.18); border-radius: 50%;">
                        <i class="bi bi-lock-fill text-white" style="font-size: 28px; opacity: 0.95;"></i>
                    </div>
                    <h2 class="mb-1 fw-bold" style="letter-spacing: 0.5px;">Forgot Password?</h2>
                    <p class="mb-0 small opacity-75">Don't worry, we'll help you reset it</p>
                </div>

                <!-- Body Section -->
                <div class="card-body p-4 p-lg-5">
                    <?php if (isset($errors['token'])): ?>
                        <div class="alert alert-danger"> <?php echo htmlspecialchars($errors['token']); ?> </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['token']) && !$success_message && empty($errors['token'])): ?>
                        <form action="" method="POST" novalidate class="needs-validation">
                            <div class="mb-4">
                                <label for="new_password" class="form-label fw-semibold">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>" required placeholder="Enter new password">
                                <?php if (isset($errors['new_password'])): ?>
                                    <div class="invalid-feedback d-block mt-1"><?php echo htmlspecialchars($errors['new_password']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" required placeholder="Repeat new password">
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback d-block mt-1"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-forgot-gradient btn-lg w-100 rounded-pill fw-semibold py-2 text-uppercase shadow-sm">
                                    <i class="bi bi-send me-2"></i> Change Password
                                </button>
                            </div>
                        </form>
                    <?php elseif ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php else: ?>
                        <!-- Existing forgot password form and instructions remain here -->
                        <!-- Success Message -->
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            
                            <!-- Additional Instructions -->
                            <div class="text-center mb-4">
                                <div class="alert alert-info border-0" style="background: rgba(27, 60, 83, 0.1);">
                                    <i class="bi bi-info-circle text-primary me-2"></i>
                                    <small class="text-muted">
                                        Please check your email inbox and spam folder for the reset link.
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Instruction Text -->
                        <?php if (!$success_message): ?>
                            <div class="text-center mb-4">
                                <p class="text-muted">
                                    Enter your email address and we'll send you a link to reset your password.
                                </p>
                            </div>
                        <?php endif; ?>

                        <form action="forget_password.php<?php echo (isset($_GET['debug']) ? '?debug=1' : ''); ?>" method="POST" novalidate class="needs-validation">
                            <!-- Email Field -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-envelope text-primary"></i>
                                    </span>
                                    <input type="email"
                                           class="form-control border-start-0 bg-light <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                           id="email" name="email"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                           placeholder="Enter your registered email address"
                                           required
                                           <?php echo $success_message ? 'readonly' : ''; ?>>
                                </div>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback d-block mt-1"><?php echo htmlspecialchars($errors['email']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Submit Button -->
                            <?php if (!$success_message): ?>
                                <div class="d-grid mb-4">
                                    <button type="submit" class="btn btn-forgot-gradient btn-lg w-100 rounded-pill fw-semibold py-2 text-uppercase shadow-sm">
                                        <i class="bi bi-send me-2"></i> Send Reset Link
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="d-grid mb-4">
                                    <button type="button" class="btn btn-outline-success btn-lg w-100 rounded-pill fw-semibold py-2 shadow-sm" disabled>
                                        <i class="bi bi-check2 me-2"></i> Reset Link Sent
                                    </button>
                                </div>
                            <?php endif; ?>

                            <!-- Divider -->
                            <div class="position-relative my-4">
                                <div class="border-bottom"></div>
                                <div class="position-absolute top-50 start-50 translate-middle bg-white px-3 small text-muted" style="font-weight: 500;">
                                    or
                                </div>
                            </div>

                            <!-- Centered Back to Login Button -->
                            <div class="row justify-content-center mb-4">
                                <div class="col-12 col-md-8">
                                    <a href="login.php" class="btn btn-outline-primary btn-lg w-100 rounded-pill fw-semibold py-2 shadow-sm">
                                        <i class="bi bi-arrow-left me-1"></i> Back to Login
                                    </a>
                                </div>
                            </div>

                            <!-- Security Note -->
                            <?php if ($success_message): ?>
                                <div class="mt-4 p-3 border rounded" style="background: rgba(255, 193, 7, 0.1); border-color: rgba(255, 193, 7, 0.2) !important;">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-shield-exclamation text-warning me-2 mt-1"></i>
                                        <div>
                                            <small class="text-muted">
                                                <strong>Security Note:</strong> The reset link will expire in 1 hour. 
                                                If you didn't request this, please ignore this message.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styles -->
<style>
    .animate-card {
        animation: floatIn 0.8s cubic-bezier(0.23, 1, 0.32, 1);
    }
    @keyframes floatIn {
        0% { opacity: 0; transform: translateY(40px) scale(0.98); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    .card {
        border: none;
        border-radius: 1.5rem !important;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: rgba(255,255,255,0.98);
    }
    .card:hover {
        transform: translateY(-5px) scale(1.01);
        box-shadow: 0 16px 40px rgba(31, 38, 135, 0.18);
    }
    .form-control {
        padding: 12px 18px;
        border-radius: 10px !important;
        background: #f7f8fa;
        border: 1px solid #e0e0e0;
        font-size: 16px;
        transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    }
            .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(27, 60, 83, 0.18);
            border-color: #1B3C53;
            background: rgba(27, 60, 83, 0.05);
        }
    .form-control[readonly] {
        background: #e9ecef;
        opacity: 0.8;
    }
    .input-group-text {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 10px 0 0 10px !important;
        font-size: 1.1rem;
        color: #764ba2;
        transition: background 0.3s;
    }
    .btn-forgot-gradient {
        background: linear-gradient(90deg, #1B3C53 0%, #456882 100%);
        color: #fff;
        border: none;
        letter-spacing: 0.5px;
        font-size: 15px;
        transition: all 0.3s ease;
    }
    .btn-forgot-gradient:hover {
        background: linear-gradient(90deg, #456882 0%, #1B3C53 100%);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(27, 60, 83, 0.18);
    }
    .btn-outline-primary {
        border-color: #667eea;
        color: #667eea;
        transition: all 0.3s ease;
    }
    .btn-outline-primary:hover {
        background: #667eea;
        border-color: #667eea;
        color: white;
        transform: translateY(-2px);
    }
    .btn-outline-success {
        border-color: #28a745;
        color: #28a745;
        cursor: default;
    }
    .avatar-circle {
        transition: transform 0.3s ease;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.10);
    }
    .card:hover .avatar-circle {
        transform: scale(1.08);
    }
    .fw-semibold { font-weight: 500 !important; }
    .fw-bold { font-weight: 700 !important; }
    .form-control::placeholder {
        font-size: 13px !important;
        color: #b0b3b8;
        opacity: 1;
    }
    .border-bottom {
        border-bottom: 1.5px solid #e0e0e0 !important;
    }
    
    /* Success state styling */
    .alert-success {
        border: none;
        border-radius: 12px;
        background: rgba(40, 167, 69, 0.1);
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    /* Custom button animations */
    .btn {
        transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    }
    
    .btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
        .card-body {
            padding: 2rem 1.5rem !important;
        }
        .col-md-8 {
            width: 100%;
        }
    }
</style>

<!-- JavaScript for interactive elements -->
<script>
    // Add animation to form inputs when focused
    document.querySelectorAll('.form-control').forEach(function(input) {
        input.addEventListener('focus', function() {
            if (!this.readOnly) {
                this.parentNode.querySelector('.input-group-text').style.backgroundColor = 'rgba(102, 126, 234, 0.1)';
            }
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.querySelector('.input-group-text').style.backgroundColor = '';
        });
    });
    
    // Auto-focus on email input when page loads
    document.addEventListener('DOMContentLoaded', function() {
        const emailInput = document.getElementById('email');
        if (emailInput && !emailInput.readOnly) {
            emailInput.focus();
        }
    });
    
    // Form submission loading state
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i> Sending...';
                submitBtn.disabled = true;
                // Re-enable after 3 seconds if form doesn't redirect
                setTimeout(function() {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    }
</script>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>