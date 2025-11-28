<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch user role
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

if ($user_role !== 'leader') {
    header('Location: communities.php');
    exit;
}

$errors = [];
$name = '';
$description = '';
$edit_id = null;

// Handle community creation and updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $edit_id = $_POST['edit_id'] ?? null;

    if (empty($name)) {
        $errors[] = 'Community name is required.';
    }
    if (empty($description)) {
        $errors[] = 'Description is required.';
    }

    if (empty($errors)) {
        if ($edit_id) {
            // Update existing community
            $stmt = $pdo->prepare('UPDATE communities SET name = ?, description = ? WHERE id = ? AND leader_id = ?');
            $stmt->execute([$name, $description, $edit_id, $user_id]);
        } else {
            // Create new community
            $stmt = $pdo->prepare('INSERT INTO communities (name, description, leader_id) VALUES (?, ?, ?)');
            $stmt->execute([$name, $description, $user_id]);
        }
        header('Location: manage_communities.php');
        exit;
    }
}

// Handle community deletion
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM communities WHERE id = ? AND leader_id = ?');
    $stmt->execute([$delete_id, $user_id]);
    header('Location: manage_communities.php');
    exit;
}

// Handle editing a community
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM communities WHERE id = ? AND leader_id = ?');
    $stmt->execute([$edit_id, $user_id]);
    $community_to_edit = $stmt->fetch();
    if ($community_to_edit) {
        $name = $community_to_edit['name'];
        $description = $community_to_edit['description'];
    }
}


// Fetch communities led by the user
$stmt = $pdo->prepare('SELECT * FROM communities WHERE leader_id = ? ORDER BY name');
$stmt->execute([$user_id]);
$communities = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Communities - Community Hub</title>
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
                <?php if ($user_role === 'leader'): ?>
                    <li class="nav-item"><a class="nav-link active" href="manage_communities.php">Manage</a></li>
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
        <div class="text-center mb-5">
            <h1 class="display-4 text-white">Manage Communities</h1>
            <p class="lead text-white-50">Create, edit, and manage your communities.</p>
        </div>

        <div class="form-container mb-5">
            <h2 class="text-white mb-4"><?php echo $edit_id ? 'Edit Community' : 'Create a New Community'; ?></h2>
            <?php if (!empty($errors)):
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error):
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="manage_communities.php" method="POST">
                <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                <div class="mb-3">
                    <label for="name" class="form-label text-white-50">Community Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label text-white-50">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <?php if ($edit_id):
                        <a href="manage_communities.php" class="btn btn-secondary me-md-2">Cancel Edit</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?php echo $edit_id ? 'Save Changes' : 'Create Community'; ?></button>
                </div>
            </form>
        </div>

        <h2 class="text-white mb-4">Your Communities</h2>
        <div class="row">
            <?php if (empty($communities)):
                <div class="col">
                    <div class="form-container text-center">
                        <p class="lead text-white-50">You have not created any communities yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($communities as $community):
                    <div class="col-md-6 mb-4">
                        <div class="card bg-white-10 text-white h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($community['name']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo htmlspecialchars($community['description']); ?></p>
                                <div class="mt-auto">
                                    <a href="manage_communities.php?edit=<?php echo $community['id']; ?>" class="btn btn-sm btn-outline-light me-2"><i class="fas fa-edit me-1"></i>Edit</a>
                                    <a href="manage_communities.php?delete=<?php echo $community['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this community?');"><i class="fas fa-trash me-1"></i>Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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