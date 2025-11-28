<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch conversations
$stmt = $pdo->prepare("SELECT u.id as user_id, u.username, m.message, m.created_at
    FROM messages m
    JOIN users u ON u.id = m.sender_id OR u.id = m.receiver_id
    WHERE (m.sender_id = :user_id OR m.receiver_id = :user_id)
    AND u.id != :user_id
    AND m.id IN (
        SELECT MAX(id) FROM messages
        WHERE sender_id = :user_id OR receiver_id = :user_id
        GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
    )
    ORDER BY m.created_at DESC");

$stmt->execute(['user_id' => $user_id]);
$conversations = $stmt->fetchAll();

// Fetch user role
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user_role = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Community Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex flex-column min-vh-100">
    <div class="background-animation"></div>
<nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-cubes me-2"></i>Community Hub
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                <li class="nav-item"><a class="nav-link" href="events.php">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="proposals.php">Proposals</a></li>
                <?php if ($user_role === 'leader'): ?>
                    <li class="nav-item"><a class="nav-link" href="manage_communities.php">Manage</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="messages.php">Messages</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-5 flex-grow-1">
    <div class="text-center mb-5">
        <h1 class="display-4 text-white">Messages</h1>
        <p class="lead text-white-50">Your recent conversations.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php if (empty($conversations)): ?>
                <div class="form-container text-center">
                    <p class="lead text-white-50">You have no conversations yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $convo): ?>
                    <div class="message-item">
                        <a href="conversation.php?with=<?php echo $convo['user_id']; ?>">
                            <div class="message-item-header">
                                <h5 class="text-white mb-0"><?php echo htmlspecialchars($convo['username']); ?></h5>
                                <small class="text-white-50"><?php echo date('M j, Y, g:i a', strtotime($convo['created_at'])); ?></small>
                            </div>
                            <p class="text-white-50 mb-0"><?php echo htmlspecialchars(substr($convo['message'], 0, 100)); ?>...</p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container text-center">
        <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
