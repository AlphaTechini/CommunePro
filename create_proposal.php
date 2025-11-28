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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['title']) && !empty($_POST['description'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $end_time = !empty($_POST['end_time']) ? date('Y-m-d H:i:s', strtotime($_POST['end_time'])) : date('Y-m-d H:i:s', strtotime('+7 days'));

        try {
            $stmt = $pdo->prepare("INSERT INTO proposals (user_id, community_id, title, description, end_time) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $community_id, $title, $description, $end_time]);

            // Send email notification
            require_once __DIR__ . '/mail/MailService.php';
            $user_stmt = $pdo->prepare('SELECT email, name FROM users WHERE id = ?');
            $user_stmt->execute([$user_id]);
            $user = $user_stmt->fetch();

            if ($user) {
                $to = $user['email'];
                $subject = "Proposal Created: " . $title;
                $html_content = "<h1>Your new proposal has been created!</h1><p><b>Title:</b> {$title}</p><p><b>Community:</b> {$community['name']}</p>";
                $text_content = "Your new proposal '{$title}' has been created in the community '{$community['name']}'.";
                MailService::sendMail($to, $subject, $html_content, $text_content);
            }

            header("Location: proposals.php?community_id=$community_id");
            exit;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            exit;
        }
    } else {
        echo "Title and description are required.";
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Proposal - <?php echo htmlspecialchars($community['name']); ?> - Community Hub</title>
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
                <li class="nav-item"><a class="nav-link" href="proposals.php?community_id=<?php echo $community_id; ?>">Proposals</a></li>
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
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-container">
                <h2 class="text-white text-center mb-4">Create a New Proposal in <?php echo htmlspecialchars($community['name']); ?></h2>
                <form method="POST" action="create_proposal.php?community_id=<?php echo $community_id; ?>">
                    <div class="mb-3">
                        <label for="title" class="form-label text-white-50">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label text-white-50">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="8" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="end_time" class="form-label text-white-50">End Time</label>
                        <input type="datetime-local" class="form-control" id="end_time" name="end_time">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Submit Proposal</button>
                    </div>
                </form>
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