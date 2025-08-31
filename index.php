<?php
require 'bootstrap.php';

$success_message = '';

// Handle create or update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entry'])) {
    $id      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $content = trim($_POST['content'] ?? '');
    $mood    = $_POST['mood'] ?? '😏 Neutral';
    $tags    = !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];

    if ($content === '') {
        $success_message = '<p>Content cannot be empty.</p>';
    } else {
        if ($id > 0) {
            updateEntry($id, $content, $mood, $tags);
            header('Location: index.php?notice=' . urlencode('Entry updated.'));
        } else {
            addEntry($content, $mood, $tags);
            header('Location: index.php?notice=' . urlencode('Entry saved.'));
        }
        exit;
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch entries for the current page
$entries = getEntries($limit, $offset);

// Get total pages
$totalEntries = getEntryCount();
$totalPages = ceil($totalEntries / $limit);

?>

<!doctype html>
<html lang="en-GB">

<?php include 'head.php'; ?>

<body>
    <header>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <p><?php echo htmlspecialchars($page_description); ?></p>
        
        <?php include 'nav.php'; ?>
    </header>
    <main>
        <form method="POST" action="index.php" id="entry-form">
            <!-- hidden id, blank for new entries, filled when editing -->
            <input type="hidden" name="id" id="entry-id" value="">

            <label class="hidden" for="content">Journal Entry</label>
            <textarea id="content" rows="6" name="content" required autofocus placeholder="Today I..."></textarea>
            
            <div class="inline">
                <div>
                    <label for="mood">Mood</label>
                    <select id="mood" name="mood">
                        <option value="🙃 Happy">🙃 Happy</option>
                        <option value="😞 Sad">😞 Sad</option>
                        <option value="😏 Neutral" selected>😏 Neutral</option>
                        <option value="😡 Angry">😡 Angry</option>
                        <option value="🤪 Excited">🤪 Excited</option>
                        <option value="😰 Anxious">😰 Anxious</option>
                    </select>
                </div>

                <div>
                    <label for="tags">Tag(s)</label>
                    <input type="text" id="tags" name="tags" placeholder="work, personal">
                </div>
            </div>
            
            <button type="submit" name="save_entry" id="save-button">Save entry</button>
            <?php echo $success_message; ?>
            <?php if (!empty($_GET['notice'])): ?>
                <p><?php echo htmlspecialchars($_GET['notice']); ?></p>
            <?php endif; ?>
        </form>


        <hr>

        <?php if (empty($entries)): ?>
            <p>No entries found.</p>
        <?php else: ?>
            <?php foreach ($entries as $entry): ?>
                <article>
                <h2><?php echo formatTimestampForLocal($entry['timestamp']); ?></h2>
                    <div><?php echo $Parsedown->text($entry['content']); ?></div>
                    <p><strong>Mood:</strong> <?php echo htmlspecialchars($entry['mood']); ?></p>
                    <p><strong>Tag(s):</strong> 
                        <?php 
                        if (!empty($entry['tags'])) {
                            $tags = explode(',', $entry['tags']);
                            $tagLinks = array_map(fn($tag) => '<a href="tag.php?tag=' . urlencode(trim($tag)) . '">' . htmlspecialchars(trim($tag)) . '</a>', $tags);
                            echo implode(', ', $tagLinks);
                        } else {
                            echo 'None';
                        }
                        ?>
                    </p>
                    <div class="delete-container">
                        <button
                            type="button"
                            class="edit"
                            onclick="startEdit(this)"
                            data-id="<?php echo (int)$entry['id']; ?>"
                            data-content="<?php echo htmlspecialchars($entry['content'], ENT_QUOTES); ?>"
                            data-mood="<?php echo htmlspecialchars($entry['mood'], ENT_QUOTES); ?>"
                            data-tags="<?php echo htmlspecialchars($entry['tags'] ?? '', ENT_QUOTES); ?>">
                            Edit
                        </button>
                        <form method="POST" action="index.php" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                            <input type="hidden" name="delete_id" value="<?php echo $entry['id']; ?>">
                            <button class="delete" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <nav>
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php endif; ?>
        </nav>

        <?php $tags = getTagCloud();
        if (!empty($tags)): ?>
            <section class="tag-cloud">
                <h2>Tag Cloud</h2>
                <p>
                    <?php foreach ($tags as $tag): 
                        $size = 12 + ($tag['count'] * 2); // Adjust font size based on usage
                        ?>
                        <a href="tag.php?tag=<?php echo urlencode($tag['name']); ?>" 
                        style="font-size: <?php echo $size; ?>px;">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </p>
            </section>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
    <script src="scripts.js"></script>
</body>
</html>
