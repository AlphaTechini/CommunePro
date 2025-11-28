
<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['community_id'])) {
    header('Location: communities.php');
    exit;
}

$community_id = $_GET['community_id'];
$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch community details
$stmt = $pdo->prepare('SELECT * FROM communities WHERE id = ?');
$stmt->execute([$community_id]);
$community = $stmt->fetch();

if (!$community) {
    header('Location: communities.php');
    exit;
}

// Fetch user role
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

// Handle new proposal form submission
$title = $description = $end_time = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_role === 'leader') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    if (empty($description)) {
        $errors[] = 'Description is required');
    }
    if (empty($end_time)) {
        $errors[] = 'End time is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO proposals (community_id, user_id, title, description, end_time) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$community_id, $user_id, $title, $description, $end_time]);
            header('Location: proposals.php?community_id=' . $community_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch proposals
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
        <h1 class="text-center mb-4">Proposals in <?php echo htmlspecialchars($community['name']); ?></h1>
        
        <?php if ($user_role === 'leader'): ?>
        <div class="card card-neon mb-4">
            <div class="card-body">
                <h2 class="card-title">Create a New Proposal</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="proposals.php?community_id=<?php echo $community_id; ?>" method="POST">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                     <div class="mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="<?php echo htmlspecialchars($end_time); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-gradient">Create Proposal</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <h2>Active Proposals</h2>
        <div class="list-group">
            <?php if (empty($proposals)): ?>
                <p>No proposals yet. <?php if ($user_role === 'leader'): ?>Be the first to create one!<?php endif; ?></p>
            <?php else: ?>
                <?php foreach ($proposals as $proposal): ?>
                    <a href="proposal.php?id=<?php echo $proposal['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($proposal['title']); ?></h5>
                            <small>Ends <?php echo date('M j, Y, g:i a', strtotime($proposal['end_time'])); ?></small>
                        </div>
                        <p class="mb-1">Created by: <?php echo htmlspecialchars($proposal['user_name']); ?></p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="bg-dark text-white text-center p-3 mt-auto">
    <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
