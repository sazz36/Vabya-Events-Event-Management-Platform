<?php
// Settings tab content
require_once __DIR__ . '/../Config/db.php';
$db = new Database();
$conn = $db->getConnection();

// Get user data
$user_id = $_SESSION['user']['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cog"></i> Account Settings</h3>
    </div>
    <div class="settings-list">
        <!-- Profile Information -->
        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-name">Profile Information</div>
                <div class="setting-desc">Update your personal details</div>
            </div>
            <button class="action-btn btn-outline" onclick="openProfileModal()">
                <i class="fas fa-edit"></i>
                Edit
            </button>
        </div>

        <!-- Password Change -->
        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-name">Change Password</div>
                <div class="setting-desc">Update your account password</div>
            </div>
            <button class="action-btn btn-outline" onclick="openPasswordModal()">
                <i class="fas fa-key"></i>
                Change
            </button>
        </div>

        <!-- Privacy Settings -->
        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-name">Privacy Settings</div>
                <div class="setting-desc">Control your data visibility</div>
            </div>
            <button class="action-btn btn-outline" onclick="openPrivacyModal()">
                <i class="fas fa-shield-alt"></i>
                Manage
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-palette"></i> Display Preferences</h3>
    </div>
    <div class="settings-list">
        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-name">Dark Mode</div>
                <div class="setting-desc">Switch between light and dark themes</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider"></span>
            </label>
        </div>

        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-name"><?php echo t('language_preference'); ?></div>
                <div class="setting-desc" id="selected-language"><?php echo getCurrentLanguage() === 'en' ? 'English (Default)' : 'नेपाली'; ?></div>
            </div>
            <a href="#" id="changeLanguageBtn" class="action-btn btn-outline">
                <i class="fas fa-globe"></i>
                <?php echo t('change'); ?>
            </a>
            <div id="languageDropdown" style="display:none; margin-top:10px;">
                <form method="POST" id="languageForm">
                    <select id="languageSelect" name="language" class="form-select" style="width:200px;">
                        <option value="en" <?php echo getCurrentLanguage() === 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="ne" <?php echo getCurrentLanguage() === 'ne' ? 'selected' : ''; ?>>नेपाली</option>
                </select>
                    <button type="submit" name="change_language" class="btn btn-sm btn-primary mt-2"><?php echo t('save'); ?></button>
                </form>
            </div>
        </div>

        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-name">Auto-refresh Dashboard</div>
                <div class="setting-desc">Automatically refresh dashboard data</div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="autoRefreshToggle" checked>
                <span class="slider"></span>
            </label>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-download"></i> Data & Export</h3>
    </div>
    <div class="settings-list">
        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-name">Export My Data</div>
                <div class="setting-desc">Download your booking history and data</div>
            </div>
            <button class="action-btn btn-outline" onclick="exportUserData()">
                <i class="fas fa-download"></i>
                Export
            </button>
        </div>

        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-name">Delete Account</div>
                <div class="setting-desc">Permanently delete your account and data</div>
            </div>
            <button class="action-btn btn-danger" onclick="openDeleteAccountModal()">
                <i class="fas fa-trash"></i>
                Delete
            </button>
        </div>
    </div>
</div>

<!-- Profile Edit Modal -->
<div id="profileModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Profile</h3>
            <span class="close" onclick="closeProfileModal()">&times;</span>
        </div>
        <form id="profileForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="profileName" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="profilePhone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeProfileModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Password Change Modal -->
<div id="passwordModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Password</h3>
            <span class="close" onclick="closePasswordModal()">&times;</span>
        </div>
        <form id="passwordForm">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" id="currentPassword" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="newPassword" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" id="confirmPassword" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closePasswordModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Privacy Settings Modal -->
<div id="privacyModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Privacy Settings</h3>
            <span class="close" onclick="closePrivacyModal()">&times;</span>
        </div>
        <div class="privacy-settings">
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-name">Profile Visibility</div>
                    <div class="setting-desc">Allow others to see your profile</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="profileVisibilityToggle" checked>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-name">Booking History</div>
                    <div class="setting-desc">Show your booking history to event organizers</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="bookingHistoryToggle">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-name">Email Notifications</div>
                    <div class="setting-desc">Receive email updates about your bookings</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="emailNotificationsToggle" checked>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-outline" onclick="closePrivacyModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="savePrivacySettings()">Save Settings</button>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteAccountModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Account</h3>
            <span class="close" onclick="closeDeleteAccountModal()">&times;</span>
        </div>
        <div class="delete-account-warning">
            <i class="fas fa-exclamation-triangle" style="color: #e74c3c; font-size: 48px; margin-bottom: 20px;"></i>
            <h4>Warning: This action cannot be undone!</h4>
            <p>Deleting your account will permanently remove:</p>
            <ul>
                <li>All your booking history</li>
                <li>Personal information</li>
                <li>Account preferences</li>
                <li>All associated data</li>
            </ul>
            <div class="form-group">
                <label>Type "DELETE" to confirm</label>
                <input type="text" id="deleteConfirmation" placeholder="Type DELETE to confirm">
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-outline" onclick="closeDeleteAccountModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="deleteAccount()" disabled id="deleteAccountBtn">Delete Account</button>
        </div>
    </div>
</div>
<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: var(--light);
    margin: 5% auto;
    padding: 0;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid var(--light-gray);
}

.modal-header h3 {
    margin: 0;
    color: var(--primary-dark);
    font-size: 20px;
    font-weight: 600;
}

.close {
    color: var(--gray);
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover {
    color: var(--danger);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark);
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--light-gray);
    border-radius: 12px;
    font-size: 15px;
    transition: border-color 0.3s ease;
    background: var(--light);
    color: var(--dark);
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary);
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding: 20px 25px;
    border-top: 1px solid var(--light-gray);
}

.btn {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--primary);
    color: var(--primary);
}

.btn-outline:hover {
    background: var(--primary);
    color: white;
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #c62828;
    transform: translateY(-2px);
}

.privacy-settings {
    padding: 20px 25px;
}

.delete-account-warning {
    padding: 20px 25px;
    text-align: center;
}

.delete-account-warning h4 {
    color: var(--danger);
    margin-bottom: 15px;
}

.delete-account-warning ul {
    text-align: left;
    margin: 15px 0;
    padding-left: 20px;
}

.delete-account-warning li {
    margin-bottom: 8px;
    color: var(--dark);
}

/* Action button styles */
.action-btn {
    padding: 8px 16px;
    border-radius: 10px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.action-btn.btn-outline {
    background: transparent;
    border: 1px solid var(--primary);
    color: var(--primary);
}

.action-btn.btn-outline:hover {
    background: var(--primary);
    color: white;
}

.action-btn.btn-danger {
    background: var(--danger);
    color: white;
}

.action-btn.btn-danger:hover {
    background: #c62828;
}
</style>

<script>
// Language dropdown logic
const changeBtn = document.getElementById('changeLanguageBtn');
const dropdown = document.getElementById('languageDropdown');

if (changeBtn) {
    changeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    });
}

// Modal Functions
function openProfileModal() {
    document.getElementById('profileModal').style.display = 'block';
}

function closeProfileModal() {
    document.getElementById('profileModal').style.display = 'none';
}

function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'block';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
    document.getElementById('passwordForm').reset();
}

function openPrivacyModal() {
    document.getElementById('privacyModal').style.display = 'block';
}

function closePrivacyModal() {
    document.getElementById('privacyModal').style.display = 'none';
}

function openDeleteAccountModal() {
    document.getElementById('deleteAccountModal').style.display = 'block';
}

function closeDeleteAccountModal() {
    document.getElementById('deleteAccountModal').style.display = 'none';
    document.getElementById('deleteConfirmation').value = '';
    document.getElementById('deleteAccountBtn').disabled = true;
}

// Profile Form Submission
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'updateProfile');
    formData.append('name', document.getElementById('profileName').value);
    formData.append('email', document.getElementById('profileEmail').value);
    formData.append('phone', document.getElementById('profilePhone').value);
    
    fetch('controllers/UserController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Profile updated successfully!');
            closeProfileModal();
        } else {
            showToast('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while updating profile');
    });
});

// Password Form Submission
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        showToast('New passwords do not match!');
        return;
    }
    
    if (newPassword.length < 6) {
        showToast('Password must be at least 6 characters long!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'changePassword');
    formData.append('currentPassword', document.getElementById('currentPassword').value);
    formData.append('newPassword', newPassword);
    
    fetch('controllers/UserController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Password changed successfully!');
            closePasswordModal();
        } else {
            showToast('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while changing password');
    });
});

// Privacy Settings
function savePrivacySettings() {
    const settings = {
        profileVisibility: document.getElementById('profileVisibilityToggle').checked,
        bookingHistory: document.getElementById('bookingHistoryToggle').checked,
        emailNotifications: document.getElementById('emailNotificationsToggle').checked
    };
    
    localStorage.setItem('privacySettings', JSON.stringify(settings));
    showToast('Privacy settings saved successfully!');
    closePrivacyModal();
}

// Delete Account Confirmation
document.getElementById('deleteConfirmation').addEventListener('input', function(e) {
    const deleteBtn = document.getElementById('deleteAccountBtn');
    deleteBtn.disabled = e.target.value !== 'DELETE';
});

function deleteAccount() {
    const formData = new FormData();
    formData.append('action', 'deleteAccount');
    
    fetch('controllers/UserController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Account deleted successfully. Redirecting to login...');
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 2000);
        } else {
            showToast('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while deleting account');
    });
}

// Export User Data
function exportUserData() {
    showToast('Preparing your data for download...');
    
    fetch('controllers/UserController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=exportData'
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'my_event_data.json';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        showToast('Data exported successfully!');
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while exporting data');
    });
}

// Auto-refresh functionality
document.getElementById('autoRefreshToggle').addEventListener('change', function(e) {
    if (e.target.checked) {
        localStorage.setItem('autoRefresh', 'enabled');
        showToast('Auto-refresh enabled');
    } else {
        localStorage.removeItem('autoRefresh');
        showToast('Auto-refresh disabled');
    }
});

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Toast notification function
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Add toast styles
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    .toast-notification {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: var(--primary);
        color: white;
        padding: 15px 25px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    
    .toast-notification.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
`;
document.head.appendChild(toastStyle);
</script> 