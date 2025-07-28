<?php
// views/manual_reset.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manual Password Reset</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f8fa; font-family: 'Poppins', sans-serif; }
        .card { border-radius: 1.5rem; box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18); }
        .form-label { font-weight: 500; }
        .btn-primary { background: linear-gradient(90deg, #1B3C53 0%, #456882 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(90deg, #456882 0%, #1B3C53 100%); }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow p-4">
                <h3 class="mb-3 text-center">Manual Password Reset</h3>
                <form action="forget_password.php" method="GET">
                    <div class="mb-3">
                        <label for="token" class="form-label">Reset Token</label>
                        <input type="text" class="form-control" id="token" name="token" required placeholder="Paste your reset token here">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Continue</button>
                </form>
                <div class="mt-3 text-center">
                    <small class="text-muted">Paste the token you received (or see on screen) to continue to the password reset form.</small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 