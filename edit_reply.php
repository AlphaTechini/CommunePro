
<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user role if logged in
$user_role = null;
if (isset($_SESSION['user_id'])) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user_role = $stmt->fetchColumn();
}

if (!isset($_GET['id'])) {
    header('Location: communities.php');
    exit;
}

$reply_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch reply
$stmt = $pdo->prepare('SELECT * FROM discussion_replies WHERE id = ?');
$stmt->execute([$reply_id]);
$reply = $stmt->fetch();

// Fetch discussion to get community_id
$stmt = $pdo->prepare('SELECT community_id FROM discussions WHERE id = ?');
$stmt->execute([$reply['discussion_id']]);
$discussion = $stmt->fetch();

if (!$reply) {
    header('Location: communities.php');
    exit;
}

// Fetch user role
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

// Check if user is authorized to edit
if ($reply['user_id'] != $user_id && $user_role != 'leader') {
    header('Location: discussion.php?id=' . $reply['discussion_id']);
    exit;
}

$content = $reply['content'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    if (empty($content)) {
        $errors[] = 'Content is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE discussion_replies SET content = ? WHERE id = ?');
            $stmt->execute([$content, $reply_id]);
            header('Location: discussion.php?id=' . $reply['discussion_id']);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Reply - Community Hub</title>
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
                <li class="nav-item"><a class="nav-link" href="events.php?community_id=<?php echo $discussion['community_id']; ?>">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="proposals.php?community_id=<?php echo $discussion['community_id']; ?>">Proposals</a></li>
                <li class="nav-item"><a class="nav-link" href="discussions.php?community_id=<?php echo $discussion['community_id']; ">Discussions</a></li>
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

<main class="flex-grow-1">
    <section class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container">
                    <h1 class="text-white text-center mb-4">Edit Reply</h1>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form action="edit_reply.php?id=<?php echo $reply_id; ?>" method="POST">
                        <div class="mb-3">
                            <label for="content" class="form-label text-white-50">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="8" required><?php echo htmlspecialchars($content); ?></textarea>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="discussion.php?id=<?php echo $reply['discussion_id']; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container text-center">
        <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
