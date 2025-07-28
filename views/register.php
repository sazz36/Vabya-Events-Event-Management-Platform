<?php
require_once __DIR__ . '/../config/db.php';

$message = '';
$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $user_type = trim($_POST['user_type'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms_accepted = isset($_POST['terms']);

    // reCAPTCHA validation
    $recaptcha_secret = '6LcI6I8rAAAAANo2gh_DOkUgqgQLQICh3splu9wi';
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    if (!$recaptcha_response) {
        $errors['recaptcha'] = 'Please complete the reCAPTCHA.';
    } else {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
        $captcha_success = json_decode($verify);
        if (empty($captcha_success->success)) {
            $errors['recaptcha'] = 'reCAPTCHA verification failed. Please try again.';
        }
    }

    // Validate inputs
    if (!$fullname) {
        $errors[] = "Full Name is required.";
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (!$email) {
            $errors[] = 'Email is required.';
        } elseif (strpos($email, '@') === false) {
            $errors[] = 'Email must contain @.';
        } elseif (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) {
            $errors[] = 'Email format is invalid.';
        } else {
            $errors[] = 'Valid Email is required.';
        }
    }
    if (!$user_type) {
        $errors[] = "Please select user type.";
    }
    if (!$password || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (!$terms_accepted) {
        $errors[] = "You must agree to the Terms of Service.";
    }

    if (empty($errors)) {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errors[] = "Email is already registered.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$fullname, $email, $hashedPassword, $user_type]);

                header('Location: login.php');
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - भव्य Event</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: var(--neutral-light);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }
        .animate-card {
            animation: floatIn 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        }
        @keyframes floatIn {
            0% { opacity: 0; transform: translateY(40px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .register-container {
            max-width: 480px;
            margin: 50px auto;
            background: rgba(255,255,255,0.98);
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #1B3C53 0%, #456882 100%);
            color: white;
            padding: 32px 20px 24px 20px;
            text-align: center;
            border-bottom: none;
        }
        .avatar-circle {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.18);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.10);
            transition: transform 0.3s ease;
        }
        .register-container:hover .avatar-circle {
            transform: scale(1.08);
        }
        .form-label, .form-check-label {
            font-weight: 500;
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
        .form-control::placeholder {
            font-size: 12px !important;
            color: #b0b3b8;
            opacity: 1;
        }
        .input-group-text {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px 0 0 10px !important;
            font-size: 1.1rem;
            color: #1B3C53;
            transition: background 0.3s;
        }
        .btn-gradient {
            background: linear-gradient(90deg, #1B3C53 0%, #456882 100%);
            color: #fff;
            border: none;
            letter-spacing: 0.5px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(90deg, #456882 0%, #1B3C53 100%);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(27, 60, 83, 0.18);
        }
        .btn-google {
            background: #fff;
            color: #4285F4;
            border: 1.5px solid #4285F4;
            letter-spacing: 0.5px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-google:hover {
            background: #f0f7ff;
            color: #357ae8;
            border-color: #357ae8;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.12);
        }
        .border-bottom {
            border-bottom: 1.5px solid #e0e0e0 !important;
        }
        .fw-semibold { font-weight: 500 !important; }
        .fw-bold { font-weight: 700 !important; }
        .form-select {
            padding: 12px 18px;
            border-radius: 10px !important;
            background: #f7f8fa;
            border: 1px solid #e0e0e0;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
        }
        .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(27, 60, 83, 0.18);
            border-color: #1B3C53;
            background: rgba(27, 60, 83, 0.05);
        }
    </style>
</head>
<body>

<div class="register-container animate-card">
    <div class="register-header">
        <div class="avatar-circle">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="User Icon" style="width: 38px; height: 38px; opacity: 0.95;">
        </div>
        <h2 class="mb-1 fw-bold" style="letter-spacing: 0.5px;">Create Account</h2>
        <p class="mb-0 small opacity-75">Join भव्य Event today</p>
    </div>

    <div class="p-4">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" novalidate>
            <!-- Full Name -->
            <div class="mb-3">
                <label for="fullname" class="form-label fw-semibold">Full Name</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-person text-primary"></i>
                    </span>
                    <input type="text" name="fullname" id="fullname" class="form-control border-start-0 bg-light" placeholder="John Doe" required value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
                </div>
            </div>
            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-envelope text-primary"></i>
                    </span>
                    <input type="email" name="email" id="email" class="form-control border-start-0 bg-light" placeholder="you@email.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <!-- User Type Dropdown -->
            <div class="mb-3">
                <label for="user_type" class="form-label fw-semibold">User Type</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-person-badge text-primary"></i>
                    </span>
                    <select class="form-select border-start-0 bg-light" id="user_type" name="user_type" required style="font-size: 14px; padding: 8px 15px;">
                        <option value="" disabled selected>Select user type</option>
                        <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>>Event Organizer</option>
                        <option value="attendee" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'attendee') ? 'selected' : ''; ?>>Attendee</option>
                    </select>
                </div>
            </div>
            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-lock text-primary"></i>
                    </span>
                    <input type="password" name="password" id="password" class="form-control border-start-0 bg-light" placeholder="At least 8 characters" required>
                </div>
            </div>
            <!-- Confirm Password -->
            <div class="mb-3">
                <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-lock-fill text-primary"></i>
                    </span>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0 bg-light" placeholder="Repeat your password" required>
                </div>
            </div>
            <!-- Terms and Privacy Checkbox -->
            <div class="mb-2 form-check">
                <label class="form-check-label mb-0 d-flex align-items-center small" for="terms" style="gap: 8px; cursor:pointer; font-size: 0.92rem;">
                    <input type="checkbox" class="form-check-input m-0" id="terms" name="terms" required style="margin-right: 8px;">
                    <span style="white-space: normal;">
                        I agree to the <span style="white-space: nowrap;"><a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a></span> and <span style="white-space: nowrap;"><a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a></span>.
                    </span>
                </label>
                <?php if (isset($errors) && in_array('You must agree to the Terms of Service.', $errors)): ?>
                    <div class="invalid-feedback d-block mt-1 w-100 small">You must agree to the Terms of Service and Privacy Policy.</div>
                <?php endif; ?>
            </div>
            <!-- Google reCAPTCHA widget -->
            <div class="mb-3 text-center">
                <div class="g-recaptcha d-inline-block" data-sitekey="6LcI6I8rAAAAAFASIVu9Vx3MEK4gUJ7uSHH7VgfI"></div>
                <?php if (isset($errors['recaptcha'])): ?>
                    <div class="invalid-feedback d-block mt-1"><?php echo htmlspecialchars($errors['recaptcha']); ?></div>
                <?php endif; ?>
            </div>
            <!-- Submit -->
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-gradient btn-lg w-100 rounded-pill fw-semibold py-2 text-uppercase shadow-sm">Create Account</button>
            </div>
            <!-- Divider -->
            <div class="position-relative my-4">
                <div class="border-bottom"></div>
                <div class="position-absolute top-50 start-50 translate-middle bg-white px-3 small text-muted" style="font-weight: 500;">or continue with</div>
            </div>
            <!-- Google Sign-in -->
            <div class="text-center" id="google-signin-wrapper" style="display:none;">
                <a href="google-login.php" class="btn btn-google btn-lg w-100 rounded-pill fw-semibold py-2 shadow-sm">
                    <img src="https://developers.google.com/identity/images/g-logo.png" style="width:20px; margin-right:8px;"> Continue with Google
                </a>
            </div>
            <!-- Already have account -->
            <div class="text-center small mt-4">
                Already have an account?
                <a href="login.php" class="text-decoration-none fw-semibold" style="color: #456882;">Log in</a>
            </div>
        </form>
    </div>
</div>

<!-- Terms of Service Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>1. Acceptance of Terms</strong></p>
        <p>By accessing and using our services, you accept and agree to be bound by the terms and provision of this agreement.</p>
        <p><strong>2. Use of Service</strong></p>
        <p>You agree not to misuse or interfere with the operation of the platform and to use it only as permitted by law.</p>
        <p><strong>3. Account Responsibility</strong></p>
        <p>You are responsible for maintaining the confidentiality of your account and password.</p>
        <p><strong>4. Modifications</strong></p>
        <p>We may modify these terms at any time, and such changes will be effective immediately.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>1. Data Collection</strong></p>
        <p>We collect information you provide directly and information we get from your use of our services.</p>
        <p><strong>2. Data Usage</strong></p>
        <p>We use the data to improve user experience, provide personalized content, and ensure system security.</p>
        <p><strong>3. Data Sharing</strong></p>
        <p>We do not share your personal information with third parties except as necessary for service delivery or legal compliance.</p>
        <p><strong>4. Cookies</strong></p>
        <p>We use cookies to enhance functionality and analyze site usage. You can disable cookies in your browser settings.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

</body>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
// Show/hide Google sign-in based on user type
function toggleGoogleBtn() {
    var userType = document.getElementById('user_type').value;
    var googleBtn = document.getElementById('google-signin-btn');
    if (userType === 'attendee') {
        googleBtn.style.display = '';
    } else {
        googleBtn.style.display = 'none';
    }
}
document.getElementById('user_type').addEventListener('change', toggleGoogleBtn);
window.addEventListener('DOMContentLoaded', toggleGoogleBtn);
// Email and password validation on submit
const form = document.querySelector('form');
form.addEventListener('submit', function(e) {
    let email = document.getElementById('email').value;
    let password = document.getElementById('password').value;
    let emailError = '';
    let passwordError = '';
    if (!email.includes('@')) {
        emailError = 'Email must contain @.';
    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
        emailError = 'Invalid email format.';
    }
    if (password.length < 8) {
        passwordError = 'Password must be at least 8 characters.';
    } else if (!/[A-Z]/.test(password)) {
        passwordError = 'Password must contain at least one uppercase letter.';
    } else if (!/[0-9]/.test(password)) {
        passwordError = 'Password must contain at least one number.';
    } else if (!/[^a-zA-Z0-9]/.test(password)) {
        passwordError = 'Password must contain at least one special character.';
    }
    if (emailError) {
        e.preventDefault();
        document.getElementById('email').classList.add('is-invalid');
        if (!document.getElementById('email-js-error')) {
            let div = document.createElement('div');
            div.className = 'invalid-feedback d-block mt-1';
            div.id = 'email-js-error';
            div.innerText = emailError;
            document.getElementById('email').parentNode.parentNode.appendChild(div);
        } else {
            document.getElementById('email-js-error').innerText = emailError;
        }
    } else if (document.getElementById('email-js-error')) {
        document.getElementById('email-js-error').remove();
        document.getElementById('email').classList.remove('is-invalid');
    }
    if (passwordError) {
        e.preventDefault();
        document.getElementById('password').classList.add('is-invalid');
        if (!document.getElementById('password-js-error')) {
            let div = document.createElement('div');
            div.className = 'invalid-feedback d-block mt-1';
            div.id = 'password-js-error';
            div.innerText = passwordError;
            document.getElementById('password').parentNode.parentNode.appendChild(div);
        } else {
            document.getElementById('password-js-error').innerText = passwordError;
        }
    } else if (document.getElementById('password-js-error')) {
        document.getElementById('password-js-error').remove();
        document.getElementById('password').classList.remove('is-invalid');
    }
});
// ===============================
// Toggle Google Sign-in Button
// ===============================
document.getElementById("user_type").addEventListener("change", function () {
    const selectedType = this.value;
    const googleSigninWrapper = document.getElementById("google-signin-wrapper");
    if (selectedType === "attendee") {
        googleSigninWrapper.style.display = "block";
    } else {
        googleSigninWrapper.style.display = "none";
    }
});
window.addEventListener("DOMContentLoaded", function () {
    const userTypeSelect = document.getElementById("user_type");
    const googleSigninWrapper = document.getElementById("google-signin-wrapper");
    if (userTypeSelect.value === "attendee") {
        googleSigninWrapper.style.display = "block";
    } else {
        googleSigninWrapper.style.display = "none";
    }
});
</script>
<!-- Bootstrap Bundle JS (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</html>