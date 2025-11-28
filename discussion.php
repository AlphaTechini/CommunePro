
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

// Fetch discussion details
$stmt = $pdo->prepare('SELECT d.*, u.name as user_name, c.name as community_name, c.id as community_id FROM discussions d JOIN users u ON d.user_id = u.id JOIN communities c ON d.community_id = c.id WHERE d.id = ?');
$stmt->execute([$discussion_id]);
$discussion = $stmt->fetch();

if (!$discussion) {
    header('Location: communities.php');
    exit;
}

// Fetch user role in the community
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

// Handle new reply form submission
$content = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);

    if (empty($content)) {
        $errors[] = 'Reply content cannot be empty';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO discussion_replies (discussion_id, user_id, content) VALUES (?, ?, ?)');
            $stmt->execute([$discussion_id, $user_id, $content]);
            header('Location: discussion.php?id=' . $discussion_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch replies
$stmt = $pdo->prepare('SELECT dr.*, u.name as user_name FROM discussion_replies dr JOIN users u ON dr.user_id = u.id WHERE dr.discussion_id = ? ORDER BY dr.created_at ASC');
$stmt->execute([$discussion_id]);
$replies = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($discussion['title']); ?> - Community Hub</title>
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
                <h1 class="mb-0"><?php echo htmlspecialchars($discussion['title']); ?></h1>
                <small>in <a href="discussions.php?community_id=<?php echo $discussion['community_id']; ?>"><?php echo htmlspecialchars($discussion['community_name']); ?></a> by <?php echo htmlspecialchars($discussion['user_name']); ?> on <?php echo date('M j, Y, g:i a', strtotime($discussion['created_at'])); ?></small>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($discussion['content'])); ?></p>
                <?php if ($discussion['user_id'] == $user_id || $user_role == 'leader'): ?>
                <div class="mt-3">
                    <a href="edit_discussion.php?id=<?php echo $discussion['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="delete_discussion.php?id=<?php echo $discussion['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this discussion?');">Delete</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <h2>Replies</h2>
        <div class="mb-4">
            <?php if (empty($replies)): ?>
                <p>No replies yet. Be the first to reply!</p>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                    <div class="card card-neon mb-3">
                        <div class="card-body">
                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                            <small>by <?php echo htmlspecialchars($reply['user_name']); ?> on <?php echo date('M j, Y, g:i a', strtotime($reply['created_at'])); ?></small>
                            <?php if ($reply['user_id'] == $user_id || $user_role == 'leader'): ?>
                            <div class="mt-2">
                                <a href="edit_reply.php?id=<?php echo $reply['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <a href="delete_reply.php?id=<?php echo $reply['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this reply?');">Delete</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card card-neon">
             <div class="card-body">
                <h2 class="card-title">Post a Reply</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="discussion.php?id=<?php echo $discussion_id; ?>" method="POST">
                    <div class="mb-3">
                        <label for="content" class="form-label">Your Reply</label>
                        <textarea class="form-control" id="content" name="content" rows="3" required><?php echo htmlspecialchars($content); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-gradient">Post Reply</button>
                </form>
            </div>
        </div>
    </section>
</main>

<footer class="bg-dark text-white text-center p-3 mt-auto">
    <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
