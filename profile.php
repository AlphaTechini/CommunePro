
<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch user details
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch user's communities
$stmt = $pdo->prepare('SELECT c.* FROM communities c JOIN community_members cm ON c.id = cm.community_id WHERE cm.user_id = ?');
$stmt->execute([$user_id]);
$communities = $stmt->fetchAll();

// Fetch user's discussions
$stmt = $pdo->prepare('SELECT * FROM discussions WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$discussions = $stmt->fetchAll();

$name = $user['name'];
$city = $user['city'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');

    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    if (empty($city)) {
        $errors[] = 'City is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, city = ? WHERE id = ?');
            $stmt->execute([$name, $city, $user_id]);
            header('Location: profile.php');
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
    <title>Your Profile - Community Hub</title>
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
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="messages.php">Messages</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    <section class="container mt-5">
        <h1 class="text-center mb-4">Your Profile</h1>
        <div class="row">
            <div class="col-md-4">
                <div class="card card-neon">
                    <div class="card-body">
                        <h2 class="card-title">Your Information</h2>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>City:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
                        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                    </div>
                </div>
                <div class="card card-neon mt-4">
                    <div class="card-body">
                        <h2 class="card-title">Edit Profile</h2>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <form action="profile.php" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-gradient">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card card-neon">
                    <div class="card-body">
                        <h2 class="card-title">Your Communities</h2>
                        <div class="list-group">
                            <?php if (empty($communities)): ?>
                                <p>You are not a member of any communities yet.</p>
                            <?php else: ?>
                                <?php foreach ($communities as $community): ?>
                                    <a href="discussions.php?community_id=<?php echo $community['id']; ?>" class="list-group-item list-group-item-action">
                                        <?php echo htmlspecialchars($community['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card card-neon mt-4">
                    <div class="card-body">
                        <h2 class="card-title">Your Discussions</h2>
                        <div class="list-group">
                            <?php if (empty($discussions)): ?>
                                <p>You have not started any discussions yet.</p>
                            <?php else: ?>
                                <?php foreach ($discussions as $discussion): ?>
                                    <a href="discussion.php?id=<?php echo $discussion['id']; ?>" class="list-group-item list-group-item-action">
                                        <?php echo htmlspecialchars($discussion['title']); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
