
<?php 
session_start();
require_once 'db/config.php'; // Add this line

// Fetch user role if logged in
$user_role = null;
if (isset($_SESSION['user_id'])) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user_role = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Hub</title>
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
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                    
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
    <header class="hero text-white text-center">
        <div class="container">
            <h1 class="display-4">Build Your Community. Shape Your City.</h1>
            <p class="lead">The platform where neighbors connect, leaders emerge, and real change starts with one discussion.</p>
            <a href="signup.php" class="btn btn-primary me-2">Join Your City</a>
            <a href="communities.php" class="btn btn-secondary">Explore Communities</a>
        </div>
    </header>

    <section class="features text-center py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-icon mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <h3>Connect</h3>
                    <p>Join communities in your city and connect with your neighbors.</p>
                </div>
                <div class="col-md-4">
                    <div class="feature-icon mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-message-circle"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    </div>
                    <h3>Discuss</h3>
                    <p>Start and participate in discussions about topics that matter to you.</p>
                </div>
                <div class="col-md-4">
                    <div class="feature-icon mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <h3>Organize</h3>
                    <p>Create events and proposals to drive real change in your community.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="footer text-center">
    <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>