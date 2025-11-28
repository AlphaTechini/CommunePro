
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

$reply_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch reply to get discussion_id and user_id
$stmt = $pdo->prepare('SELECT discussion_id, user_id FROM discussion_replies WHERE id = ?');
$stmt->execute([$reply_id]);
$reply = $stmt->fetch();

if (!$reply) {
    header('Location: communities.php');
    exit;
}

// Fetch user role
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

// Check if user is authorized to delete
if ($reply['user_id'] != $user_id && $user_role != 'leader') {
    header('Location: discussion.php?id=' . $reply['discussion_id']);
    exit;
}

// Delete reply
$stmt = $pdo->prepare('DELETE FROM discussion_replies WHERE id = ?');
$stmt->execute([$reply_id]);

header('Location: discussion.php?id=' . $reply['discussion_id']);
exit;
