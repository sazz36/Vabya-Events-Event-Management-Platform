<?php
// Ensure $user_data is always set
if (!isset($user_data)) {
    if (isset($_SESSION['user'])) {
        // Fallback: load from session
        $user_data = [
            'name' => $_SESSION['user']['name'] ?? '',
            'email' => $_SESSION['user']['email'] ?? '',
            'role' => ucfirst($_SESSION['user']['role'] ?? ''),
            'profile_image' => '',
            'joined_date' => '',
            'events_attended' => 0,
            'upcoming_events' => 0,
            'vip_bookings' => 0,
            'pending_bookings' => 0
        ];
    } else {
        $user_data = [
            'name' => '',
            'email' => '',
            'role' => '',
            'profile_image' => '',
            'joined_date' => '',
            'events_attended' => 0,
            'upcoming_events' => 0,
            'vip_bookings' => 0,
            'pending_bookings' => 0
        ];
    }
}
// Professional success message
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success" style="margin-bottom:20px; font-size:1.1em; border-radius:8px;">'
        . '<i class="fas fa-check-circle" style="color:#43a047;"></i> '
        . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($errors) && count($errors) > 0) {
    echo '<div class="alert alert-danger">';
    foreach ($errors as $err) echo $err . '<br>';
    echo '</div>';
}
$isEdit = isset($_GET['edit']) && $_GET['edit'] == '1';
?>
<style>
/* Profile Success Message */
.alert-success {
    background: linear-gradient(90deg, #e0f7fa 0%, #c8e6c9 100%);
    color: #256029;
    border: 1px solid #b2dfdb;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(67, 160, 71, 0.08);
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-success i {
    font-size: 1.3em;
    margin-right: 8px;
}

/* Profile Card Enhancements */
.profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 18px;
}
.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #5e35b1, #26a69a);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
    font-weight: bold;
    box-shadow: 0 4px 16px rgba(94, 53, 177, 0.10);
}
.profile-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.profile-name {
    font-size: 24px;
    font-weight: 700;
    color: white;
}
.profile-email {
    color: #26a69a;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.profile-info form input[type="text"],
.profile-info form input[type="email"] {
    padding: 10px 16px;
    border-radius: 8px;
    border: 1px solid #b0bec5;
    font-size: 16px;
    margin-bottom: 8px;
    width: 100%;
    background: #f5f7fb;
    transition: border 0.2s;
}
.profile-info form input[type="text"]:focus,
.profile-info form input[type="email"]:focus {
    border: 1.5px solid #5e35b1;
    outline: none;
    background: #fff;
}
.action-btn.btn-primary {
    background: white
    color: black
    border: none;
    transition: background 0.2s, box-shadow 0.2s;
}
.action-btn.btn-primary:hover {
    background: black;
    box-shadow: 0 2px 8px rgba(94, 53, 177, 0.12);
}
.action-btn.btn-outline {
    background: transparent;
    border: 1.5px solid black;
    color: white
    transition: background 0.2s, color 0.2s;
}
.action-btn.btn-outline:hover {
    background: black;
    color: white;
}
</style>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user"></i> My Profile</h3>
    </div>
    <div class="profile-info">
        <?php if ($isEdit): ?>
        <form id="profileEditForm">
            <div class="profile-header">
                <div class="profile-avatar">AP</div>
                <div class="profile-details">
                    <div class="profile-name">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                    </div>
                    <div class="profile-email">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                </div>
            </div>
            <button type="submit" class="action-btn btn-primary" style="margin-top:20px;">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="?tab=profile" class="action-btn btn-outline" style="margin-top:20px;">Cancel</a>
        </form>
        <div id="profile-toast" style="display:none;position:fixed;top:30px;right:30px;z-index:9999;padding:16px 28px;background:#43a047;color:#fff;border-radius:8px;font-weight:500;box-shadow:0 2px 8px rgba(67,160,71,0.18);"></div>
        <script>
        const profileForm = document.getElementById('profileEditForm');
        if (profileForm) {
            profileForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const response = await fetch('profile_update.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                const toast = document.getElementById('profile-toast');
                toast.textContent = result.message;
                toast.style.display = 'block';
                toast.style.background = result.success ? '#43a047' : '#e53935';
                setTimeout(() => { toast.style.display = 'none'; }, 3000);
                if (result.success) {
                    document.querySelector('.profile-name input').value = formData.get('name');
                    document.querySelector('.profile-email input').value = formData.get('email');
                    setTimeout(() => { window.location.href = '?tab=profile'; }, 1200);
                }
            });
        }
        </script>
        <?php else: ?>
        <div class="profile-header">
            <div class="profile-avatar">AP</div>
            <div class="profile-details">
                <div class="profile-name"><?php echo htmlspecialchars($user_data['name']); ?></div>
                <div class="profile-email">
                    <i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($user_data['email']); ?>
                </div>
            </div>
        </div>
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($user_data['events_attended']); ?></div>
                <div class="stat-label">Events Attended</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($user_data['upcoming_events']); ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($user_data['vip_bookings']); ?></div>
                <div class="stat-label">VIP Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo htmlspecialchars($user_data['pending_bookings']); ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <a href="?tab=profile&edit=1" class="action-btn btn-primary">
            <i class="fas fa-edit"></i>
            Edit Profile
        </a>
        <?php endif; ?>
    </div>
</div> 