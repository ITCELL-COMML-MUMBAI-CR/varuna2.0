<?php
/**
 * VARUNA System - Main Navigation Bar (Animated Version)
 * Current Time: Thursday, June 19, 2025 at 2:20 PM IST
 * Location: Kalyan, Maharashtra, India
 */
if (!defined('VARUNA_ENTRY_POINT')) { die('Direct access not allowed.'); }
$current_page = $request_uri ?? 'login';

// Helper function to determine if a menu item or its children are active
function is_active($slug, $current_page) {
    if ($current_page === $slug || strpos($current_page, $slug . '/') === 0) {
        return 'active';
    }
    return '';
}
?>
<nav class="navbar navbar-expand-custom navbar-mainbg">
    <div id="nav-content">
        <ul class="navbar-nav ml-auto">
            <div class="hori-selector"><div class="left"></div><div class="right"></div></div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item <?php echo is_active('dashboard', $current_page); ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>dashboard">Dashboard</a>
                </li>
                
                <?php if (in_array($_SESSION['role'], ['ADMIN', 'VIEWER', 'SCI'])): ?>
                    <li class="nav-item <?php echo is_active('viewer', $current_page); ?>">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>viewer">Master View</a>
                    </li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] !== 'VIEWER'): // Hide for Viewers ?>
                    <li class="nav-item has-dropdown <?php echo is_active('licensees', $current_page); ?>">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>licensees/manage">Licensees</a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo BASE_URL; ?>licensees/manage">View All Licensees</a></li>
                            <li><a href="<?php echo BASE_URL; ?>licensees/add">Add New Licensee</a></li>
                        </ul>
                    </li>
                    <li class="nav-item has-dropdown <?php echo is_active('contracts', $current_page); ?>">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>contracts/manage">Contracts</a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo BASE_URL; ?>contracts/manage">View All Contracts</a></li>
                            <li><a href="<?php echo BASE_URL; ?>contracts/add">Add New Contract</a></li>
                        </ul>
                    </li>
                    <li class="nav-item has-dropdown <?php echo is_active('staff', $current_page) || is_active('bulk-print', $current_page) ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>staff/add">Staff</a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo BASE_URL; ?>staff/add">Onboard New Staff</a></li>
                            <li><a href="<?php echo BASE_URL; ?>staff/approved">Approved Staff</a></li>
                            <li><a href="<?php echo BASE_URL; ?>bulk-print">Bulk Print IDs</a></li>
                            <?php if ($_SESSION['role'] === 'SCI'): ?>
                                <li><a href="<?php echo BASE_URL; ?>staff/approval">Staff Approval</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] === 'ADMIN'): ?>
                    <li class="nav-item has-dropdown <?php echo is_active('admin', $current_page) || is_active('id-cards', $current_page) ? 'active' : ''; ?>">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin-panel">Admin</a>
                         <ul class="dropdown-menu">
                            <li><a href="<?php echo BASE_URL; ?>admin-panel">Admin Panel</a></li>
                            <li><a href="<?php echo BASE_URL; ?>id-cards/admin">ID Card Styles</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            <?php else: ?>
                <li class="nav-item active"><a class="nav-link" href="<?php echo BASE_URL; ?>login">Login</a></li>
            <?php endif; ?>
        </ul>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="navbar-user-section">
                <a href="<?php echo BASE_URL; ?>profile" class="nav-username" title="Click to view your profile and manage settings">
                    <i class="fas fa-user-circle" style="margin-right: 8px; font-size: 1.1em;"></i>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-cog" style="margin-left: 8px; opacity: 0.7; font-size: 0.9em;"></i>
                </a>
                <a href="<?php echo BASE_URL; ?>logout" class="nav-link-logout">
                    <i class="fas fa-sign-out-alt" style="margin-right: 5px;"></i>
                    Logout
                </a>
            </div>
        <?php endif; ?>
    </div>
</nav>