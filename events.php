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

// Handle event creation
$errors = [];
$title = $description = $start_time = $end_time = $location = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $location = trim($_POST['location'] ?? '');

    if (empty($title)) {
        $errors[] = 'Title is required.';
    }
    if (empty($description)) {
        $errors[] = 'Description is required.';
    }
    if (empty($start_time)) {
        $errors[] = 'Start time is required.';
    }
    if (empty($end_time)) {
        $errors[] = 'End time is required.';
    }
    if (empty($location)) {
        $errors[] = 'Location is required.';
    }
    if (strtotime($start_time) >= strtotime($end_time)) {
        $errors[] = 'End time must be after start time.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO events (community_id, user_id, title, description, start_time, end_time, location) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if ($stmt->execute([$community_id, $user_id, $title, $description, $start_time, $end_time, $location])) {
            header("Location: events.php?community_id=$community_id");
            exit;
        } else {
            $errors[] = 'Failed to create event. Please try again.';
        }
    }
}

// Fetch events for the community
$stmt = $pdo->prepare('SELECT e.*, u.name as user_name FROM events e JOIN users u ON e.user_id = u.id WHERE e.community_id = ? ORDER BY e.start_time DESC');
$stmt->execute([$community_id]);
$events = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($community['name']); ?> Events - Community Hub</title>
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
                <li class="nav-item"><a class="nav-link" href="proposals.php?community_id=<?php echo $community_id; ?>">Proposals</a></li>
                <li class="nav-item"><a class="nav-link" href="events.php?community_id=<?php echo $community_id; ?>">Events</a></li>
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
        <div class="text-center mb-5">
            <h1 class="display-4 text-white">Events in <?php echo htmlspecialchars($community['name']); ?></h1>
            <p class="lead text-white-50">Stay up-to-date with the latest happenings.</p>
        </div>
        
        <div class="form-container mb-5">
            <h2 class="card-title text-white">Create a New Event</h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="events.php?community_id=<?php echo $community_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label text-white-50">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label text-white-50">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_time" class="form-label text-white-50">Start Time</label>
                        <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="<?php echo htmlspecialchars($start_time); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_time" class="form-label text-white-50">End Time</label>
                        <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="<?php echo htmlspecialchars($end_time); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label text-white-50">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create Event</button>
            </form>
        </div>

        <h2 class="text-white mb-4">Upcoming Events</h2>
        <div class="row">
            <?php if (empty($events)): ?>
                <div class="col">
                    <div class="form-container text-center">
                        <p class="lead text-white-50">No events yet. Be the first to create one!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-lg-6">
                        <div class="event-card">
                            <a href="event.php?id=<?php echo $event['id']; ?>">
                                <div class="event-card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <span class="badge bg-primary"><?php echo date('M j', strtotime($event['start_time'])); ?></span>
                                </div>
                                <div class="event-card-body">
                                    <p class="mb-2"><i class="fas fa-map-marker-alt me-2 text-white-50"></i><?php echo htmlspecialchars($event['location']); ?></p>
                                    <p class="mb-0"><i class="far fa-clock me-2 text-white-50"></i><?php echo date('g:i a', strtotime($event['start_time'])); ?> - <?php echo date('g:i a', strtotime($event['end_time'])); ?></p>
                                </div>
                                <div class="event-card-footer">
                                    <small>Created by: <?php echo htmlspecialchars($event['user_name']); ?></small>
                                </div>
                            </a>
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