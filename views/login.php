<?php
session_start();

require_once __DIR__ . '/../config/db.php';  

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

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
    if (!$email) {
        $errors['email'] = 'Email is required.';
    } elseif (strpos($email, '@') === false) {
        $errors['email'] = 'Email must contain @.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    } elseif (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) {
        $errors['email'] = 'Email format is invalid.';
    }

    if (!$password) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one special character.';
    }

    if (!$user_type) {
        $errors['user_type'] = 'Please select user type.';
    }

    if (empty($errors)) {
        try {
            // Connect to database
            $db = new Database();
            $conn = $db->getConnection();

            // Prepare SQL to select user by email and role
            $stmt = $conn->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ? AND role = ?");
            $stmt->execute([$email, $user_type]);
            $user = $stmt->fetch();

            if ($user) {
                // Verify password hash
                if (password_verify($password, $user['password_hash'])) {
                    // Password matches
                    $_SESSION['user'] = [
                        'user_id' => $user['id'], // or $user['user_id'] if that's your column
                        'name'    => $user['name'],
                        'email'   => $user['email'],
                        'role'    => $user['role']
                    ];
                    $_SESSION['user_type'] = $user['role'];
                    $_SESSION['user_name'] = $user['name'];

                    // Redirect based on user type
                    if ($user['role'] === 'Event Organizer') {
                        header('Refresh: 2; URL=admin_dashboard.php');
                    } else {

                        header('Refresh: 2; URL=dashboard.php');
                    }
                } else {
                    $errors['login'] = 'Incorrect email or password.';
                }
            } else {
                $errors['login'] = 'No account found with that email and user type.';
            }
        } catch (PDOException $e) {
            $errors['login'] = "Database error: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - भव्य Event</title>
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
                        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="User Icon" style="width: 38px; height: 38px; opacity: 0.95;">
                    </div>
                    <h2 class="mb-1 fw-bold" style="letter-spacing: 0.5px;">Welcome Back</h2>
                    <p class="mb-0 small opacity-75">Please login to your भव्य Event account</p>
                </div>

                <!-- Body Section -->
                <div class="card-body p-4 p-lg-5">
                    <!-- Login Error Message -->
                    <?php if (isset($errors['login'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($errors['login']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" novalidate class="needs-validation">
                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-envelope text-primary"></i>
                                </span>
                                <input type="email"
                                       class="form-control border-start-0 bg-light <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                       id="email" name="email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="you@email.com"
                                       required>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block mt-1"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Password Field -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-lock text-primary"></i>
                                </span>
                                <input type="password"
                                       class="form-control border-start-0 bg-light <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                       id="password" name="password"
                                       placeholder="Enter your password"
                                       required>
                                <button class="input-group-text bg-white border-start-0 toggle-password" type="button">
                                    <i class="bi bi-eye-slash text-muted"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block mt-1"><?php echo htmlspecialchars($errors['password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- User Type Dropdown - Updated Styling -->
                        <div class="mb-3">
                            <label for="user_type" class="form-label fw-semibold">User Type</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-person-badge text-primary"></i>
                                </span>
                                <select class="form-select border-start-0 bg-light <?php echo isset($errors['user_type']) ? 'is-invalid' : ''; ?>" 
                                        id="user_type" name="user_type" required style="font-size: 14px; padding: 8px 15px;">
                                    <option value="" disabled selected>Select user type</option>
                                    <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>>Event Organizer</option>
                                    <option value="attendee" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'attendee') ? 'selected' : ''; ?>>Attendee</option>
                                </select>
                            </div>
                            <?php if (isset($errors['user_type'])): ?>
                                <div class="invalid-feedback d-block mt-1"><?php echo htmlspecialchars($errors['user_type']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- reCAPTCHA -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-center">
                                <div class="g-recaptcha" data-sitekey="6LcI6I8rAAAAAFASIVu9Vx3MEK4gUJ7uSHH7VgfI"></div>
                            </div>
                            <?php if (isset($errors['recaptcha'])): ?>
                                <div class="text-danger text-center mt-2 small"><?php echo htmlspecialchars($errors['recaptcha']); ?></div>
                            <?php endif; ?>
                        </div>
                       <!-- Remember Me and Forgot Password (same line) -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                                <label class="form-check-label" for="rememberMe">Remember Me</label>
                            </div>
                            <a href="forget_password.php" class="text-decoration-none small text-muted" style="font-size: 0.98em;">Forgot password?</a>
                        </div>
                        <!-- Terms and Privacy Checkbox (below) -->
                        <div class="form-check mb-3">
                            <label class="form-check-label mb-0 d-flex align-items-center small" for="terms" style="gap: 8px; cursor:pointer; font-size: 0.92rem;">
                                <input type="checkbox" class="form-check-input m-0" id="terms" name="terms" required style="margin-right: 8px;">
                                <span style="white-space: normal;">
                                    I agree to the <span style="white-space: nowrap;"><a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a></span> and <span style="white-space: nowrap;"><a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a></span>.
                                </span>
                            </label>
                        </div>
                        <?php if (isset($errors) && in_array('You must agree to the Terms of Service.', $errors)): ?>
                            <div class="invalid-feedback d-block mt-1 w-100 small">You must agree to the Terms of Service and Privacy Policy.</div>
                        <?php endif; ?>

                        <!-- Login Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-login-gradient btn-lg w-100 rounded-pill fw-semibold py-2 text-uppercase shadow-sm">
                                Log In
                            </button>
                        </div>
                        <!-- Divider -->
                        <div class="position-relative my-4">
                            <div class="border-bottom"></div>
                            <div class="position-absolute top-50 start-50 translate-middle bg-white px-3 small text-muted" style="font-weight: 500;">or continue with</div>
                        </div>
                        <!-- Google Sign-in Button -->
                        <div class="text-center" id="google-signin-wrapper" style="display:none;">
                            <a href="google-login.php" class="btn btn-google btn-lg w-100 rounded-pill fw-semibold py-2 shadow-sm">
                                <img src="https://developers.google.com/identity/images/g-logo.png" style="width:20px; margin-right:8px;"> Sign in with Google
                            </a>
                        </div>

                        <!-- Sign up Prompt -->
                        <div class="text-center small mt-4">
                            Don't have an account? 
                            <a href="register.php" class="text-decoration-none fw-semibold" style="color: #456882;">Create one</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add cookie consent banner -->
<div id="cookieConsentBanner" style="display:none; position:fixed; bottom:0; left:0; right:0; background:#222; color:#fff; padding:18px 10px; text-align:center; z-index:9999;">
    We use cookies to remember you and improve your experience. By clicking Accept, you agree to our cookie policy.
    <button id="acceptCookiesBtn" style="margin-left:18px; background:#27AE60; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600;">Accept</button>
</div>
<script>
// Show cookie consent banner if not accepted
if (!document.cookie.includes('cookie_consent=1')) {
    document.getElementById('cookieConsentBanner').style.display = 'block';
}
document.getElementById('acceptCookiesBtn').onclick = function() {
    document.cookie = 'cookie_consent=1; path=/; max-age=' + (60*60*24*365);
    document.getElementById('cookieConsentBanner').style.display = 'none';
};
</script>

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
    .input-group-text {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 10px 0 0 10px !important;
        font-size: 1.1rem;
        color: #1B3C53;
        transition: background 0.3s;
    }
    .btn-login-gradient {
        background: linear-gradient(90deg, #1B3C53 0%, #456882 100%);
        color: #fff;
        border: none;
        letter-spacing: 0.5px;
        font-size: 15px;
        transition: all 0.3s ease;
    }
    .btn-login-gradient:hover {
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
    .toggle-password {
        cursor: pointer;
        background: none;
        border: none;
        outline: none;
    }
    .toggle-password:hover {
        background-color: rgba(27, 60, 83, 0.08);
    }
    .border-bottom {
        border-bottom: 1.5px solid #e0e0e0 !important;
    }
    .avatar-circle {
        transition: transform 0.3s ease;
        box-shadow: 0 2px 8px rgba(27, 60, 83, 0.10);
    }
    .card:hover .avatar-circle {
        transform: scale(1.08);
    }
    .fw-semibold { font-weight: 500 !important; }
    .fw-bold { font-weight: 700 !important; }
    .form-control::placeholder {
        font-size: 12px !important;
        color: #b0b3b8;
        opacity: 1;
    }
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

<!-- JavaScript for interactive elements -->
<script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const passwordInput = this.parentNode.querySelector('input');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    });
    
    // Add animation to form inputs when focused
    document.querySelectorAll('.form-control').forEach(function(input) {
        input.addEventListener('focus', function() {
            this.parentNode.querySelector('.input-group-text').style.backgroundColor = 'rgba(102, 126, 234, 0.1)';
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.querySelector('.input-group-text').style.backgroundColor = '';
        });
    });

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
    // Google Sign-in Visibility Logic
    // ===============================
    document.getElementById("user_type").addEventListener("change", function () {
        const selectedType = this.value;
        const googleSigninWrapper = document.getElementById("google-signin-wrapper");
        const googleLoginBtn = document.getElementById("google-login-btn");

        if (selectedType === "attendee") {
            googleSigninWrapper.style.display = "block";
            if (googleLoginBtn) {
                googleLoginBtn.style.display = "block"; // 👈 your requested line
            }
        } else {
            googleSigninWrapper.style.display = "none";
            if (googleLoginBtn) {
                googleLoginBtn.style.display = "none";
            }
        }
    });

    window.addEventListener("DOMContentLoaded", function () {
        const userTypeSelect = document.getElementById("user_type");
        const googleSigninWrapper = document.getElementById("google-signin-wrapper");
        const googleLoginBtn = document.getElementById("google-login-btn");

        if (userTypeSelect.value === "attendee") {
            googleSigninWrapper.style.display = "block";
            if (googleLoginBtn) {
                googleLoginBtn.style.display = "block";
            }
        } else {
            googleSigninWrapper.style.display = "none";
            if (googleLoginBtn) {
                googleLoginBtn.style.display = "none";
            }
        }
    });

    // ===============================
    // Cookie Consent Banner
    // ===============================
    if (!document.cookie.includes('cookie_consent=1')) {
        document.getElementById('cookieConsentBanner').style.display = 'block';
    }
    document.getElementById('acceptCookiesBtn').onclick = function() {
        document.cookie = 'cookie_consent=1; path=/; max-age=' + (60*60*24*365);
        document.getElementById('cookieConsentBanner').style.display = 'none';
    };
</script>

<!-- Bootstrap Bundle JS (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

</body>
</html>