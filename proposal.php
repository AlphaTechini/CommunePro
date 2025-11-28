<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$proposal_id = $_GET['id'] ?? null;

if (!$proposal_id) {
    // Redirect or show an error if no proposal ID is provided
    header('Location: proposals.php');
    exit;
}

$pdo = db();

// Fetch proposal data
$stmt = $pdo->prepare("SELECT p.*, u.name, c.id as community_id FROM proposals p JOIN users u ON p.user_id = u.id JOIN communities c ON p.community_id = c.id WHERE p.id = ?");
$stmt->execute([$proposal_id]);
$proposal = $stmt->fetch();

if (!$proposal) {
    // Redirect or show an error if proposal not found
    header('Location: proposals.php');
    exit;
}

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
    <title>Proposal Details - Community Hub</title>
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
                <li class="nav-item"><a class="nav-link" href="discussions.php?community_id=<?php echo $proposal['community_id']; ?>">Discussions</a></li>
                <li class="nav-item"><a class="nav-link" href="events.php?community_id=<?php echo $proposal['community_id']; ?>">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="proposals.php?community_id=<?php echo $proposal['community_id']; ?>">Proposals</a></li>
                <?php if ($user_role === 'leader'): ?>
                    <li class="nav-item"><a class="nav-link" href="manage_communities.php">Manage</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="messages.php">Messages</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-5 flex-grow-1">
    <div class="proposal-details-container">
        <div class="proposal-details-header">
            <h1 class="display-4 text-white"><?php echo htmlspecialchars($proposal['title']); ?></h1>
            <p class="lead text-white-50">Submitted by <?php echo htmlspecialchars($proposal['name']); ?></p>
        </div>
        <div class="proposal-details-body text-white">
            <p class="fs-5"><?php echo nl2br(htmlspecialchars($proposal['description'])); ?></p>
            <hr class="border-light">
            <div class="d-flex justify-content-between align-items-center mt-4">
                <p class="fs-5 mb-0"><strong>Status:</strong></p>
                <?php
                $status_class = 'proposal-status-pending';
                if ($proposal['status'] === 'approved') {
                    $status_class = 'proposal-status-approved';
                } elseif ($proposal['status'] === 'rejected') {
                    $status_class = 'proposal-status-rejected';
                }
                ?>
                <span class="proposal-status <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($proposal['status'])); ?></span>
            </div>
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