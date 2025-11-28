
<?php
session_start();
require_once 'db/config.php';

$name = $email = $password = $city = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $city = trim($_POST['city'] ?? '');

    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    if (empty($city)) {
        $errors[] = 'City is required';
    }

    if (empty($errors)) {
        try {
            $pdo = db();

            // Check if user already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'User with this email already exists';
            } else {
                $pdo->beginTransaction();

                // Check if community for the city exists
                $stmt = $pdo->prepare('SELECT id FROM communities WHERE name = ?');
                $stmt->execute([$city]);
                $community = $stmt->fetch();
                
                $role = 'member';
                if (!$community) {
                    $role = 'leader';
                }

                // Insert the new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, city, role) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hashed_password, $city, $role]);
                $user_id = $pdo->lastInsertId();

                if ($role === 'leader') {
                    $stmt = $pdo->prepare('INSERT INTO communities (name, leader_id) VALUES (?, ?)');
                    $stmt->execute([$city, $user_id]);
                    $community_id = $pdo->lastInsertId();
                } else {
                    $community_id = $community['id'];
                }
                
                // Add user to community
                $stmt = $pdo->prepare('INSERT INTO community_members (user_id, community_id) VALUES (?, ?)');
                $stmt->execute([$user_id, $community_id]);
                
                $pdo->commit();

                $_SESSION['user_id'] = $user_id;
                header('Location: communities.php');
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
    <title>Sign Up - Community Hub</title>
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="messages.php">Messages</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>


<main class="flex-grow-1">
    <section class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-neon">
                    <div class="card-body">
                        <h1 class="text-center mb-4">Create Your Account</h1>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <form action="signup.php" method="POST" id="signup-form">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-gradient">Sign Up</button>
                            </div>
                        </form>
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
<script>
    function getCity() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(position => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                // Using a free reverse geocoding API
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.address && data.address.city) {
                            document.getElementById('city').value = data.address.city;
                        }
                    })
                    .catch(err => console.error("Error fetching city:", err));
            });
        }
    }
    window.onload = getCity;
</script>

</body>
</html>