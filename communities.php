
<?php
session_start();
require_once 'db/config.php';

// Fetch user role if logged in
$user_role = null;
if (isset($_SESSION['user_id'])) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user_role = $stmt->fetchColumn();
}

try {
    $pdo = db();
    $stmt = $pdo->query('SELECT * FROM communities ORDER BY name');
    $communities = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching communities: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communities - Community Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Community Hub</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="communities.php">Communities</a></li>
                     <?php if ($user_role === 'leader'): ?>
                        <li class="nav-item"><a class="nav-link" href="manage_communities.php">Manage Communities</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="messages.php">Messages</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    <div class="container py-5">
        <h1 class="text-center mb-5">Explore Communities</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php elseif (empty($communities)): ?>
            <div class="text-center">
                <p>No communities found. Be the first to create one!</p>
                <a href="signup.php" class="btn btn-primary">Get Started</a>
            </div>
        <?php else: ?>
            <div class="row gy-4">
                <?php foreach ($communities as $community): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100" style="background-color: #161b22; border-color: #30363d;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title" style="color: #f0f6fc;"><?php echo htmlspecialchars($community['name']); ?></h5>
                                <p class="card-text flex-grow-1" style="color: #8b949e;">A place for residents of <?php echo htmlspecialchars($community['name']); ?> to connect and organize.</p>
                                <a href="discussions.php?community_id=<?php echo $community['id']; ?>" class="btn btn-secondary mt-auto">View Discussions</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="footer text-center">
    <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
