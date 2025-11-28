
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

$proposal_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch proposal details
$stmt = $pdo->prepare('SELECT p.*, u.name as user_name, c.name as community_name FROM proposals p JOIN users u ON p.user_id = u.id JOIN communities c ON p.community_id = c.id WHERE p.id = ?');
$stmt->execute([$proposal_id]);
$proposal = $stmt->fetch();

if (!$proposal) {
    header('Location: communities.php');
    exit;
}

// Handle voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $vote = $_POST['vote'];
    if (in_array($vote, ['up', 'down'])) {
        try {
            // Check if user has already voted
            $stmt = $pdo->prepare('SELECT id FROM proposal_votes WHERE user_id = ? AND proposal_id = ?');
            $stmt->execute([$user_id, $proposal_id]);
            if ($stmt->fetch()) {
                // Update existing vote
                $stmt = $pdo->prepare('UPDATE proposal_votes SET vote = ? WHERE user_id = ? AND proposal_id = ?');
                $stmt->execute([$vote, $user_id, $proposal_id]);
            } else {
                // Insert new vote
                $stmt = $pdo->prepare('INSERT INTO proposal_votes (proposal_id, user_id, vote) VALUES (?, ?, ?)');
                $stmt->execute([$proposal_id, $user_id, $vote]);
            }
            header('Location: proposal.php?id=' . $proposal_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch votes
$stmt = $pdo->prepare('SELECT vote, COUNT(*) as count FROM proposal_votes WHERE proposal_id = ? GROUP BY vote');
$stmt->execute([$proposal_id]);
$votes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$up_votes = $votes['up'] ?? 0;
$down_votes = $votes['down'] ?? 0;

// Check if the current user has voted
$stmt = $pdo->prepare('SELECT vote FROM proposal_votes WHERE user_id = ? AND proposal_id = ?');
$stmt->execute([$user_id, $proposal_id]);
$user_vote = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($proposal['title']); ?> - Community Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Community Hub</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="messages.php">Messages</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    <section class="container mt-5">
        <div class="card card-neon mb-4">
            <div class="card-header">
                <h1 class="mb-0"><?php echo htmlspecialchars($proposal['title']); ?></h1>
                 <small>in <a href="discussions.php?community_id=<?php echo $proposal['community_id']; ?>"><?php echo htmlspecialchars($proposal['community_name']); ?></a> by <?php echo htmlspecialchars($proposal['user_name']); ?> on <?php echo date('M j, Y, g:i a', strtotime($proposal['created_at'])); ?></small>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($proposal['description'])); ?></p>
                <p><strong>Voting ends:</strong> <?php echo date('M j, Y, g:i a', strtotime($proposal['end_time'])); ?></p>
            </div>
        </div>

        <h2>Voting</h2>
        <div class="d-flex justify-content-around mb-4">
            <div class="text-center">
                <h3>Upvotes</h3>
                <p class="display-4"><?php echo $up_votes; ?></p>
            </div>
            <div class="text-center">
                <h3>Downvotes</h3>
                <p class="display-4"><?php echo $down_votes; ?></p>
            </div>
        </div>

        <?php if (strtotime($proposal['end_time']) > time()): ?>
        <div class="card card-neon">
            <div class="card-body text-center">
                <h2 class="card-title">Cast Your Vote</h2>
                 <form action="proposal.php?id=<?php echo $proposal_id; ?>" method="POST" class="d-inline">
                    <input type="hidden" name="vote" value="up">
                    <button type="submit" class="btn btn-success btn-lg me-2 <?php echo ($user_vote === 'up') ? 'active' : ''; ?>">Vote Up</button>
                </form>
                <form action="proposal.php?id=<?php echo $proposal_id; ?>" method="POST" class="d-inline">
                    <input type="hidden" name="vote" value="down">
                    <button type="submit" class="btn btn-danger btn-lg <?php echo ($user_vote === 'down') ? 'active' : ''; ?>">Vote Down</button>
                </form>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-info text-center">Voting on this proposal has ended.</div>
        <?php endif; ?>

    </section>
</main>

<footer class="bg-dark text-white text-center p-3 mt-auto">
    <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
