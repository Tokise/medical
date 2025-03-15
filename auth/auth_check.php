<?php
if (!isset($_SESSION['user_email'])) {
    header('Location: ../../login.php');
    exit();
}
