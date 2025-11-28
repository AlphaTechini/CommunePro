<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$with_id = $_GET['with'] ?? null;

if (!$with_id) {
    header('Location: messages.php');
    exit;
}

$pdo = db();

// Fetch user role
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user_role = $stmt->fetchColumn();

// Fetch the other user's info
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$with_id]);
$with_user = $stmt->fetch();

// Fetch conversation
$stmt = $pdo->prepare("SELECT m.*, u.username as sender_username FROM messages m JOIN users u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.created_at ASC");
$stmt->execute([$user_id, $with_id, $with_id, $user_id]);
$messages = $stmt->fetchAll();

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $with_id, $message]);
    header("Location: conversation.php?with=$with_id");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversation with <?php echo htmlspecialchars($with_user['username']); ?> - Community Hub</title>
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

<main class="container mt-5 flex-grow-1 d-flex flex-column">
    <div class="text-center mb-4">
        <h1 class="display-5 text-white">Conversation with <?php echo htmlspecialchars($with_user['username']); ?></h1>
    </div>

    <div class="chat-container flex-grow-1 d-flex flex-column">
        <div class="chat-messages flex-grow-1">
            <?php foreach ($messages as $msg): ?>
                <div class="d-flex <?php echo $msg['sender_id'] == $user_id ? 'justify-content-end' : 'justify-content-start'; ?> mb-3">
                    <div class="message-bubble <?php echo $msg['sender_id'] == $user_id ? 'message-sent' : 'message-received'; ?>">
                        <p class="mb-0"><?php echo htmlspecialchars($msg['message']); ?></p>
                        <small class="d-block text-end mt-1 opacity-75"><?php echo date('g:i a', strtotime($msg['created_at'])); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="chat-input-group">
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="message" class="form-control" placeholder="Type your message..." autocomplete="off">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                </div>
            </form>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container text-center">
        <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Scroll to the bottom of the chat on page load
    const chatMessages = document.querySelector('.chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
</script>
</body>
</html>
