
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

if (!isset($_GET['id'])) {
    header('Location: communities.php');
    exit;
}

$event_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$pdo = db();

// Fetch event details
$stmt = $pdo->prepare('SELECT e.*, u.name as user_name, c.name as community_name FROM events e JOIN users u ON e.user_id = u.id JOIN communities c ON e.community_id = c.id WHERE e.id = ?');
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: communities.php');
    exit;
}

// Handle RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsvp'])) {
    $rsvp = $_POST['rsvp'];
    if (in_array($rsvp, ['attending', 'not_attending'])) {
        try {
            // Check if user has already RSVPed
            $stmt = $pdo->prepare('SELECT id FROM event_rsvps WHERE user_id = ? AND event_id = ?');
            $stmt->execute([$user_id, $event_id]);
            if ($stmt->fetch()) {
                // Update existing RSVP
                $stmt = $pdo->prepare('UPDATE event_rsvps SET rsvp = ? WHERE user_id = ? AND event_id = ?');
                $stmt->execute([$rsvp, $user_id, $event_id]);
            } else {
                // Insert new RSVP
                $stmt = $pdo->prepare('INSERT INTO event_rsvps (event_id, user_id, rsvp) VALUES (?, ?, ?)');
                $stmt->execute([$event_id, $user_id, $rsvp]);
            }
            header('Location: event.php?id=' . $event_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch RSVPs
$stmt = $pdo->prepare('SELECT rsvp, COUNT(*) as count FROM event_rsvps WHERE event_id = ? GROUP BY rsvp');
$stmt->execute([$event_id]);
$rsvps = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$attending = $rsvps['attending'] ?? 0;
$not_attending = $rsvps['not_attending'] ?? 0;

// Check if the current user has RSVPed
$stmt = $pdo->prepare('SELECT rsvp FROM event_rsvps WHERE user_id = ? AND event_id = ?');
$stmt->execute([$user_id, $event_id]);
$user_rsvp = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - Community Hub</title>
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
    <section class="container mt-5">
        <div class="card card-neon mb-4">
            <div class="card-header">
                <h1 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h1>
                 <small>in <a href="discussions.php?community_id=<?php echo $event['community_id']; ?>"><?php echo htmlspecialchars($event['community_name']); ?></a> by <?php echo htmlspecialchars($event['user_name']); ?> on <?php echo date('M j, Y, g:i a', strtotime($event['created_at'])); ?></small>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <p><strong>Starts:</strong> <?php echo date('M j, Y, g:i a', strtotime($event['start_time'])); ?></p>
                <p><strong>Ends:</strong> <?php echo date('M j, Y, g:i a', strtotime($event['end_time'])); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
            </div>
        </div>

        <h2>RSVPs</h2>
        <div class="d-flex justify-content-around mb-4">
            <div class="text-center">
                <h3>Attending</h3>
                <p class="display-4"><?php echo $attending; ?></p>
            </div>
            <div class="text-center">
                <h3>Not Attending</h3>
                <p class="display-4"><?php echo $not_attending; ?></p>
            </div>
        </div>

        <div class="card card-neon">
            <div class="card-body text-center">
                <h2 class="card-title">RSVP</h2>
                 <form action="event.php?id=<?php echo $event_id; ?>" method="POST" class="d-inline">
                    <input type="hidden" name="rsvp" value="attending">
                    <button type="submit" class="btn btn-success btn-lg me-2 <?php echo ($user_rsvp === 'attending') ? 'active' : ''; ?>">Attending</button>
                </form>
                <form action="event.php?id=<?php echo $event_id; ?>" method="POST" class="d-inline">
                    <input type="hidden" name="rsvp" value="not_attending">
                    <button type="submit" class="btn btn-danger btn-lg <?php echo ($user_rsvp === 'not_attending') ? 'active' : ''; ?>">Not Attending</button>
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
