<header class="header">
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="<?php echo t('search_placeholder'); ?>">
    </div>
    <div class="header-right">
        <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
            <i class="fas fa-moon" style="font-size: 12px; color: var(--dark); position: absolute; left: 6px; z-index: 1;"></i>
            <i class="fas fa-sun" style="font-size: 12px; color: white; position: absolute; right: 6px; z-index: 1; opacity: 0;"></i>
        </button>
        <div class="user-profile">
            <div class="user-avatar">
                <?php
                $initials = '';
                $nameParts = explode(' ', trim($user_data['name']));
                if (count($nameParts) > 1) {
                    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                } else {
                    $initials = strtoupper(substr($user_data['name'], 0, 2));
                }
                echo $initials;
                ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user_data['name']); ?></div>
                <div class="user-email" style="font-size:12px; color:var(--gray); "><?php echo htmlspecialchars($user_data['email']); ?></div>
                <div class="user-role"><?php echo $user_data['role']; ?></div>
            </div>
        </div>
    </div>
    <?php echo '<div style="color:red;font-weight:bold;">BELL TEST</div>'; ?>
</header> 