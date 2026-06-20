<?php
session_start();
echo "Welcome, " . htmlspecialchars($_SESSION['full_name']) . "! You are logged in as: " . htmlspecialchars($_SESSION['role']);
?>