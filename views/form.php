<!-- Event Creation Form - Styled to match register.php theme -->
<div class="register-container animate-card" style="max-width: 480px; margin: 50px auto; background: rgba(255,255,255,0.98); border-radius: 1.5rem; box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18); overflow: hidden;">
    <div class="register-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 32px 20px 24px 20px; text-align: center; border-bottom: none;">
        <div class="avatar-circle" style="width: 70px; height: 70px; background: rgba(255,255,255,0.18); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.10);">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Event Icon" style="width: 38px; height: 38px; opacity: 0.95;">
        </div>
        <h2 class="mb-1 fw-bold" style="letter-spacing: 0.5px;">Create Event</h2>
        <p class="mb-0 small opacity-75">Fill in the details below</p>
    </div>
    <div class="p-4">
        <form method="post" enctype="multipart/form-data" action="add_event.php">
            <div class="mb-3">
                <label class="form-label fw-semibold">Event Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="3" required></textarea>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="col">
                    <label class="form-label fw-semibold">Time</label>
                    <input type="time" name="time" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Venue</label>
                <input type="text" name="venue" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Category</label>
                <select name="category" class="form-select" required>
                    <option value="" disabled selected>Select category</option>
                    <option value="Conference">Conference</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Concert">Concert</option>
                    <option value="Seminar">Seminar</option>
                </select>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-semibold">Capacity</label>
                    <input type="number" name="capacity" class="form-control" min="1" required>
                </div>
                <div class="col">
                    <label class="form-label fw-semibold">Ticket Price</label>
                    <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Event Image</label>
                <input type="file" name="image" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Contact Email</label>
                <input type="email" name="contact_email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Registration Deadline</label>
                <input type="datetime-local" name="registration_deadline" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Additional Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-gradient w-100 py-2">Create Event</button>
        </form>
    </div>
</div>
<!-- Bootstrap CSS & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .register-container { font-family: 'Poppins', sans-serif; }
    .register-header { border-bottom: none; }
    .avatar-circle { transition: transform 0.3s ease; }
    .register-container:hover .avatar-circle { transform: scale(1.08); }
    .form-label, .form-check-label { font-weight: 500; }
    .form-control, .form-select { border-radius: 10px !important; background: #f7f8fa; border: 1px solid #e0e0e0; font-size: 16px; }
    .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.18); border-color: #764ba2; background: #f0f4fa; }
    .btn-gradient { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; letter-spacing: 0.5px; font-size: 15px; font-weight: 600; transition: all 0.3s ease; }
    .btn-gradient:hover { background: linear-gradient(90deg, #764ba2 0%, #667eea 100%); color: #fff; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.18); }
</style>
<?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
    <a href="form.php" class="btn btn-gradient mb-3">
        <i class="bi bi-plus-circle"></i> New Event
    </a>
<?php endif; ?>