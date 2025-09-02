<?php
echo '<h2>Resetting All Demo Users</h2>';
echo '<div style="background:#f9f9f9;padding:20px;border-radius:8px;margin:20px 0;">';

echo '<h3>Admin User</h3>';
include 'reset_admin_password.php';

echo '<h3>Teacher User</h3>';
include 'reset_teacher_password.php';

echo '<h3>Student User</h3>';
include 'reset_student_password.php';

echo '<h3>Parent User</h3>';
include 'reset_parent_password.php';

echo '</div>';
echo '<p><strong>All users reset successfully! Password for all: "password"</strong></p>';
echo '<p><a href="/login.php" style="color:#2563eb;">Go to Login</a></p>';
?>
