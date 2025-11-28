
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
                <li class="nav-item"><a class="nav-link" href="events.php?community_id=<?php echo $event['community_id']; ?>">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="proposals.php?community_id=<?php echo $event['community_id']; ?>">Proposals</a></li>
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
        <div class="event-details-container">
            <div class="event-details-header">
                <h1 class="display-4 text-white"><?php echo htmlspecialchars($event['title']); ?></h1>
                <p class="lead text-white-50">
                    in <a href="discussions.php?community_id=<?php echo $event['community_id']; ?>" class="text-white-50"><?php echo htmlspecialchars($event['community_name']); ?></a> by <?php echo htmlspecialchars($event['user_name']); ?>
                </p>
            </div>
            <div class="event-details-body text-white">
                <p class="fs-5"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <hr class="border-light">
                <div class="row mt-4 fs-5">
                    <div class="col-md-4">
                        <p><i class="fas fa-map-marker-alt me-2 text-primary"></i><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><i class="far fa-clock me-2 text-primary"></i><strong>Starts:</strong> <?php echo date('M j, Y, g:i a', strtotime($event['start_time'])); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><i class="far fa-clock me-2 text-primary"></i><strong>Ends:</strong> <?php echo date('M j, Y, g:i a', strtotime($event['end_time'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rsvp-section">
            <h2 class="text-white mb-4">RSVPs</h2>
            <div class="row justify-content-center">
                <div class="col-md-4 text-center">
                    <p class="rsvp-label">Attending</p>
                    <p class="rsvp-count text-success"><?php echo $attending; ?></p>
                </div>
                <div class="col-md-4 text-center">
                    <p class="rsvp-label">Not Attending</p>
                    <p class="rsvp-count text-danger"><?php echo $not_attending; ?></p>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-white mb-3">Will you be there?</h3>
                <form action="event.php?id=<?php echo $event_id; ?>" method="POST" class="d-inline">
                    <input type="hidden" name="rsvp" value="attending">
                    <button type="submit" class="btn btn-success btn-lg me-2 <?php echo ($user_rsvp === 'attending') ? 'active' : ''; ?>"><i class="fas fa-check me-2"></i>Attending</button>
                </form>
                <form action="event.php?id=<?php echo $event_id; ?>" method="POST" class="d-inline">
                    <input type="hidden" name="rsvp" value="not_attending">
                    <button type="submit" class="btn btn-danger btn-lg <?php echo ($user_rsvp === 'not_attending') ? 'active' : ''; ?>"><i class="fas fa-times me-2"></i>Not Attending</button>
                </form>
            </div>
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
