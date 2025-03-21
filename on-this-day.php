<?php
require 'functions.php';
require 'Parsedown.php';
$Parsedown = new Parsedown();

// Get today's date
$today = date('Y-m-d');

// Queries for different timeframes
function getEntriesByDate($date) {
    global $db;
    $stmt = $db->prepare("SELECT e.id, e.timestamp, e.content, e.mood, GROUP_CONCAT(t.name) AS tags 
                          FROM entries e 
                          LEFT JOIN entry_tags et ON e.id = et.entry_id 
                          LEFT JOIN tags t ON et.tag_id = t.id 
                          WHERE DATE(e.timestamp) = :date
                          GROUP BY e.id
                          ORDER BY e.timestamp DESC");
    $stmt->execute([':date' => $date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate relevant dates
$weekAgo = date('Y-m-d', strtotime('-1 week'));
$monthAgo = date('Y-m-d', strtotime('-1 month'));
$sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

// Get "On this day in previous years", excluding the current year
$year = date('Y');
$monthDay = date('m-d');
$stmt = $db->prepare("SELECT e.id, e.timestamp, e.content, e.mood, GROUP_CONCAT(t.name) AS tags 
                      FROM entries e 
                      LEFT JOIN entry_tags et ON e.id = et.entry_id 
                      LEFT JOIN tags t ON et.tag_id = t.id 
                      WHERE strftime('%m-%d', e.timestamp) = :monthDay 
                      AND strftime('%Y', e.timestamp) < :year
                      GROUP BY e.id
                      ORDER BY e.timestamp DESC");
$stmt->execute([':monthDay' => $monthDay, ':year' => $year]);
$previousYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch entries for each timeframe
$entries = [
    'A week ago' => getEntriesByDate($weekAgo),
    'A month ago' => getEntriesByDate($monthAgo),
    'Six months ago' => getEntriesByDate($sixMonthsAgo),
    'On this day in previous years' => $previousYears
];

$page_title = "On This Day";
$page_description = "See what you wrote on this day in past weeks, months, and years.";
?>

<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> 

    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
    <link rel="stylesheet" href="custom.css">
</head>
<body>
    <header>
        <h1><?php echo $page_title; ?></h1>
        <p><?php echo $page_description; ?></p>
        <nav><a href="index.php">Back to journal</a></nav>
    </header>
    <main>
        <?php foreach ($entries as $heading => $entryList): ?>
            <?php if (!empty($entryList)): // Only show the heading if there are entries ?>
                <h2><?php echo $heading; ?> you wrote...</h2>
                <?php foreach ($entryList as $entry): ?>
                    <article>
                        <h3><?php echo date('d F Y \a\t H:i', strtotime($entry['timestamp'])); ?></h3>
                        <div><?php echo $Parsedown->text($entry['content']); ?></div>
                        <p><strong>Mood:</strong> <?php echo htmlspecialchars($entry['mood']); ?></p>
                        <p><strong>Tags:</strong> 
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
                            <form method="POST" action="index.php" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                <input type="hidden" name="delete_id" value="<?php echo $entry['id']; ?>">
                                <button class="delete" type="submit">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty(array_filter($entries))): // If all sections are empty ?>
            <p>No past entries found, please come back later.</p>
        <?php endif; ?>
    </main>

</body>
</html>
