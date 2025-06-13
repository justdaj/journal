<?php
$config = require 'config.php';
$page_title = "Search Journal";
$page_description = "Find entries containing keywords.";
date_default_timezone_set($config['timezone']);
require 'functions.php';
require 'Parsedown.php';
$Parsedown = new Parsedown();

$db = getDb();
$searchResults = [];
$query = $_GET['q'] ?? '';

if (!empty($query)) {
    $stmt = $db->prepare("SELECT e.id, e.timestamp, e.content, e.mood, GROUP_CONCAT(t.name) AS tags
                          FROM entries e
                          LEFT JOIN entry_tags et ON e.id = et.entry_id
                          LEFT JOIN tags t ON et.tag_id = t.id
                          WHERE e.content LIKE :query OR t.name LIKE :query
                          GROUP BY e.id
                          ORDER BY e.timestamp DESC");
    $stmt->execute([':query' => "%$query%"]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!doctype html>
<html lang="en-GB">

<?php include 'head.php'; ?>

<body>
    <header>
        <h1><?php echo $page_title; ?></h1>
        <p><?php echo $page_description; ?></p>
        <?php include 'nav.php'; ?>
    </header>

    <main>
        <p>If you can't find what you're looking for by checking tags, you can use the form below to search for anything you like.</p>

        <form method="GET" action="search.php" class="search-form">
            <label class="hidden" for="search">Search your journal:</label>
            <input 
                type="text" 
                id="search" 
                name="q" 
                value="<?php echo htmlspecialchars($q); ?>" 
                placeholder="e.g. kids, work, holiday..." 
                required>
            <button type="submit">Search</button>
        </form>

        <?php if (!empty($query)): ?>
            <h2>Results for "<?php echo htmlspecialchars($query); ?>"</h2>
            <?php if (empty($searchResults)): ?>
                <p>No results found.</p>
            <?php else: ?>
                <?php foreach ($searchResults as $entry): ?>
                    <article>
                        <h3><?php echo formatTimestampForLocal($entry['timestamp']); ?></h3>
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
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
