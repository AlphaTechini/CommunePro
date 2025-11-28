
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

// Handle new discussion form submission
$title = $content = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    if (empty($content)) {
        $errors[] = 'Content is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO discussions (community_id, user_id, title, content) VALUES (?, ?, ?, ?)');
            $stmt->execute([$community_id, $user_id, $title, $content]);
            header('Location: discussions.php?community_id=' . $community_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build where clause for search
$where_clause = 'WHERE d.community_id = ?';
$params = [$community_id];
if (!empty($search)) {
    $where_clause .= ' AND (d.title LIKE ? OR d.content LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

// Fetch total number of discussions
$stmt = $pdo->prepare('SELECT COUNT(*) FROM discussions d ' . $where_clause);
$stmt->execute($params);
$total_discussions = $stmt->fetchColumn();
$total_pages = ceil($total_discussions / $limit);

// Fetch discussions for the current page
$sql = 'SELECT d.*, u.name as user_name FROM discussions d JOIN users u ON d.user_id = u.id ' . $where_clause . ' ORDER BY d.created_at DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($sql);
$params[] = $limit;
$params[] = $offset;

foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}

$stmt->execute();
$discussions = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($community['name']); ?> Discussions - Community Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                <?php if ($user_role === 'leader'): ?>
                    <li class="nav-item"><a class="nav-link" href="manage_communities.php">Manage Communities</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="messages.php">Messages</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    <div class="container py-5">
        <h1 class="text-center mb-5">Discussions in <?php echo htmlspecialchars($community['name']); ?></h1>
        
        <div class="form-container mb-5">
            <h2 class="mb-4">Start a New Discussion</h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-0"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="discussions.php?community_id=<?php echo $community_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="4" required><?php echo htmlspecialchars($content); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Post Discussion</button>
            </form>
        </div>

        <h2 class="mb-4">Existing Discussions</h2>

        <form action="discussions.php" method="GET" class="mb-4">
            <input type="hidden" name="community_id" value="<?php echo $community_id; ?>">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search discussions..." name="search" value="<?php echo htmlspecialchars($search); ?>" style="background-color: #0d1117; color: #c9d1d9; border-color: #30363d;">
                <button class="btn btn-outline-secondary" type="submit" style="border-color: #30363d; color: #58a6ff;">Search</button>
            </div>
        </form>

        <div>
            <?php if (empty($discussions)): ?>
                <div class="text-center p-4" style="background-color: #161b22; border-radius: 0.5rem;">
                    <p class="mb-0">No discussions found. Be the first to start one!</p>
                </div>
            <?php else: ?>
                <?php foreach ($discussions as $discussion): ?>
                    <div class="discussion-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">
                                <a href="discussion.php?id=<?php echo $discussion['id']; ?>"><?php echo htmlspecialchars($discussion['title']); ?></a>
                            </h5>
                            <small class="text-muted"><?php echo date('M j, Y, g:i a', strtotime($discussion['created_at'])); ?></small>
                        </div>
                        <p class="mb-1 text-muted">Started by: <?php echo htmlspecialchars($discussion['user_name']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?community_id=<?php echo $community_id; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?community_id=<?php echo $community_id; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?community_id=<?php echo $community_id; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</main>

<footer class="footer text-center">
    <p>&copy; <?php echo date("Y"); ?> Community Hub. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
