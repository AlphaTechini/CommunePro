<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$community_id = $_GET['community_id'] ?? null;

if (!$community_id) {
    echo "Community ID is required.";
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM communities WHERE id = ?');
$stmt->execute([$community_id]);
$community = $stmt->fetch();

if (!$community) {
    echo "Community not found.";
    exit;
}

// Fetch user role
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

// Fetch proposals for the community
$stmt = $pdo->prepare('SELECT p.*, u.name as user_name FROM proposals p JOIN users u ON p.user_id = u.id WHERE p.community_id = ? ORDER BY p.created_at DESC');
$stmt->execute([$community_id]);
$proposals = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($community['name']); ?> Proposals - Community Hub</title>
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
                <li class="nav-item"><a class="nav-link" href="discussions.php?community_id=<?php echo $community_id; ?>">Discussions</a></li>
                <li class="nav-item"><a class="nav-link" href="events.php?community_id=<?php echo $community_id; ?>">Events</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="proposals.php?community_id=<?php echo $community_id; ?>">Proposals</a></li>
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
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="display-4 text-white">Proposals in <?php echo htmlspecialchars($community['name']); ?></h1>
            <p class="lead text-white-50">Shape the future of your community.</p>
        </div>
        <a href="create_proposal.php?community_id=<?php echo $community_id; ?>" class="btn btn-primary btn-lg"><i class="fas fa-plus me-2"></i>New Proposal</a>
    </div>
    <div class="row">
        <?php if (empty($proposals)): ?>
            <div class="col">
                <div class="form-container text-center">
                    <p class="lead text-white-50">No proposals yet. Be the first to create one!</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($proposals as $proposal): ?>
                <div class="col-lg-6">
                    <div class="proposal-card">
                        <a href="proposal.php?id=<?php echo $proposal['id']; ?>">
                            <div class="proposal-card-body">
                                <h5 class="card-title text-white"><?php echo htmlspecialchars($proposal['title']); ?></h5>
                                <p class="card-text text-white-50"><?php echo htmlspecialchars(substr($proposal['description'], 0, 150)); ?>...</p>
                            </div>
                            <div class="proposal-card-footer">
                                <small>By <?php echo htmlspecialchars($proposal['user_name']); ?></small>
                                <small><?php echo date('M j, Y', strtotime($proposal['created_at'])); ?></small>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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