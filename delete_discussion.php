
<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: communities.php');
    exit;
}

$discussion_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch discussion to get community_id and user_id
$stmt = $pdo->prepare('SELECT community_id, user_id FROM discussions WHERE id = ?');
$stmt->execute([$discussion_id]);
$discussion = $stmt->fetch();

if (!$discussion) {
    header('Location: communities.php');
    exit;
}

// Fetch user role
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

// Check if user is authorized to delete
if ($discussion['user_id'] != $user_id && $user_role != 'leader') {
    header('Location: discussion.php?id=' . $discussion_id);
    exit;
}

// Delete replies first
$stmt = $pdo->prepare('DELETE FROM discussion_replies WHERE discussion_id = ?');
$stmt->execute([$discussion_id]);

// Delete discussion
$stmt = $pdo->prepare('DELETE FROM discussions WHERE id = ?');
$stmt->execute([$discussion_id]);

header('Location: discussions.php?community_id=' . $discussion['community_id']);
exit;
