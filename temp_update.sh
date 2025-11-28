#!/bin/bash

FILES=("discussions.php" "discussion.php" "edit_discussion.php" "edit_reply.php" "event.php" "events.php" "profile.php" "proposal.php" "proposals.php" "messages.php" "conversation.php")

PHP_SNIPPET_TO_REPLACE="if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}"

PHP_SNIPPET_REPLACEMENT="if (!isset($_SESSION['user_id'])) {
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
}"

HTML_SNIPPET_TO_REPLACE='<li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>'

HTML_SNIPPET_REPLACEMENT='<li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                    <?php if ($user_role === "leader"): ?>
                        <li class="nav-item"><a class="nav-link" href="manage_communities.php">Manage Communities</a></li>
                    <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>'

for FILE in "${FILES[@]}"
do
    # Use a temporary file to avoid issues with sed -i
    sed "s|$PHP_SNIPPET_TO_REPLACE|$PHP_SNIPPET_REPLACEMENT|" "$FILE" > "${FILE}.tmp"
    mv "${FILE}.tmp" "$FILE"

    sed "s|$HTML_SNIPPET_TO_REPLACE|$HTML_SNIPPET_REPLACEMENT|" "$FILE" > "${FILE}.tmp"
    mv "${FILE}.tmp" "$FILE"
done

# Special cases with active class

HTML_SNIPPET_TO_REPLACE_ACTIVE_COMMUNITIES='<li class="nav-item"><a class="nav-link active" aria-current="page" href="communities.php">Communities</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>'

HTML_SNIPPET_REPLACEMENT_ACTIVE_COMMUNITIES='<li class="nav-item"><a class="nav-link active" aria-current="page" href="communities.php">Communities</a></li>
                    <?php if ($user_role === "leader"): ?>
                        <li class="nav-item"><a class="nav-link" href="manage_communities.php">Manage Communities</a></li>
                    <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>'

sed "s|$HTML_SNIPPET_TO_REPLACE_ACTIVE_COMMUNITIES|$HTML_SNIPPET_REPLACEMENT_ACTIVE_COMMUNITIES|" "communities.php" > "communities.php.tmp"
mv "communities.php.tmp" "communities.php"


HTML_SNIPPET_TO_REPLACE_ACTIVE_PROFILE='<li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="profile.php">Profile</a></li>'

HTML_SNIPPET_REPLACEMENT_ACTIVE_PROFILE='<li class="nav-item"><a class="nav-link" href="communities.php">Communities</a></li>
                    <?php if ($user_role === "leader"): ?>
                        <li class="nav-item"><a class="nav-link" href="manage_communities.php">Manage Communities</a></li>
                    <?php endif; ?>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="profile.php">Profile</a></li>'

sed "s|$HTML_SNIPPET_TO_REPLACE_ACTIVE_PROFILE|$HTML_SNIPPET_REPLACEMENT_ACTIVE_PROFILE|" "profile.php" > "profile.php.tmp"
mv "profile.php.tmp" "profile.php"
