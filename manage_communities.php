<?php
session_start();
require_once 'db/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$pdo = db();

// Check if user is a leader
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

if ($user_role !== 'leader') {
    // Redirect non-leaders
    header('Location: communities.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $community_id_to_delete = $_GET['delete'];
    // further check if the current user is the owner of the community
    $stmt = $pdo->prepare('DELETE FROM communities WHERE id = ? AND leader_id = ?');
    $stmt->execute([$community_id_to_delete, $user_id]);
    header('Location: manage_communities.php');
    exit;
}

// Handle form submission for creating/editing
$name = $description = '';
$errors = [];
$edit_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $edit_id = $_POST['edit_id'] ?? null;

    if (empty($name)) {
        $errors[] = 'Community name is required';
    }

    if (empty($errors)) {
        try {
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
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch community to edit if an id is passed
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


// Fetch all communities led by the user
$stmt = $pdo->prepare('SELECT * FROM communities WHERE leader_id = ? ORDER BY created_at DESC');
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
    <link rel="stylesheet" href="assets/css/custom.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<?php include 'includes/header.php'; ?>

<main class="flex-grow-1">
    <section class="container mt-5">
        <h1 class="text-center mb-4">Manage Communities</h1>

        <div class="card card-neon mb-4">
            <div class="card-body">
                <h2 class="card-title"><?php echo $edit_id ? 'Edit Community' : 'Create a New Community'; ?></h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="manage_communities.php" method="POST">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Community Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-gradient"><?php echo $edit_id ? 'Save Changes' : 'Create Community'; ?></button>
                    <?php if ($edit_id): ?>
                        <a href="manage_communities.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <h2 class="mb-3">Your Communities</h2>
        <div class="list-group">
            <?php if (empty($communities)): ?>
                <p>You have not created any communities yet.</p>
            <?php else: ?>
                <?php foreach ($communities as $community):
                    <div class="list-group-item">
                        <h5><?php echo htmlspecialchars($community['name']); ?></h5>
                        <p><?php echo htmlspecialchars($community['description']); ?></p>
                        <div>
                            <a href="manage_communities.php?edit=<?php echo $community['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="manage_communities.php?delete=<?php echo $community['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this community?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
