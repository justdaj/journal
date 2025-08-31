<?php
require 'bootstrap.php';


// Total entries
$totalEntries = $db->query("SELECT COUNT(*) FROM entries")->fetchColumn();

// Entries this year/month/week
$thisYear = $db->query("SELECT COUNT(*) FROM entries WHERE strftime('%Y', timestamp) = strftime('%Y', 'now')")->fetchColumn();
$thisMonth = $db->query("SELECT COUNT(*) FROM entries WHERE strftime('%Y-%m', timestamp) = strftime('%Y-%m', 'now')")->fetchColumn();
$thisWeek = $db->query("SELECT COUNT(*) FROM entries WHERE strftime('%W', timestamp) = strftime('%W', 'now') AND strftime('%Y', timestamp) = strftime('%Y', 'now')")->fetchColumn();

// Mood breakdown
$moodCounts = $db->query("SELECT mood, COUNT(*) as count FROM entries GROUP BY mood ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalMoods = array_sum(array_column($moodCounts, 'count'));
$mostCommonMood = $moodCounts[0]['mood'] ?? null;
$leastCommonMood = end($moodCounts)['mood'] ?? null;

// Tag usage
$tagCounts = $db->query("SELECT t.name, COUNT(et.entry_id) as count FROM tags t JOIN entry_tags et ON t.id = et.tag_id GROUP BY t.id ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Mood per tag
$tagMoodSpreadStmt = $db->query("SELECT t.name AS tag, e.mood, COUNT(*) as count FROM tags t 
    JOIN entry_tags et ON t.id = et.tag_id 
    JOIN entries e ON et.entry_id = e.id 
    GROUP BY t.id, e.mood 
    ORDER BY t.name, count DESC");
$tagMoodSpread = [];
foreach ($tagMoodSpreadStmt as $row) {
    $tagMoodSpread[$row['tag']][] = ['mood' => $row['mood'], 'count' => $row['count']];
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
        <h2>Entry Stats</h2>
            <div class="stats-grid center">
                <div class="card">
                    <h3><?php echo $totalEntries; ?></h3>
                    <p>Total entries</p>
                </div>

                <div class="card">
                    <h3><?php echo $thisYear; ?></h3>
                    <p>Entries this year</p>
                </div>

                <div class="card">
                    <h3><?php echo $thisMonth; ?></h3>
                    <p>Entries this month</p>
                </div>

                <div class="card">
                    <h3><?php echo $thisWeek; ?></h3>
                    <p>Entries this week</p>
                </div>
            </div>

        <h2>Mood Breakdown</h2>
            <div class="stats-grid center">
                <?php foreach ($moodCounts as $mood): ?>
                    <div class="card third">
                        <h3><?php echo htmlspecialchars($mood['mood']); ?></h3>
                        <p><?php echo $mood['count']; ?> (<?php echo round($mood['count'] / $totalMoods * 100, 1); ?>%)</p>
                    </div>
                <?php endforeach; ?>
            </div>

            <br>

            <div class="stats-grid center">
                <div class="card half">
                    <h3><?php echo htmlspecialchars($mostCommonMood); ?></h3>
                    <p>Most common mood</p>
                </div>
                <div class="card half">
                    <h3><?php echo htmlspecialchars($leastCommonMood); ?></h3>
                    <p>Least common mood</p>
                </div>
            </div>

        <h2>Most Used Tags</h2>
            <ul class="counter">
                <?php foreach ($tagCounts as $tag): ?>
                    <li><?php echo htmlspecialchars($tag['name']); ?> <span>x<?php echo $tag['count']; ?></span></li>
                <?php endforeach; ?>
            </ul>

        <h2>Mood by tag</h2>
        <?php foreach ($tagMoodSpread as $tag => $moods): ?>
            <h3 class="stats-tags">#<?php echo htmlspecialchars($tag); ?></h3>
            <ul class="counter">
                <?php foreach ($moods as $entry): ?>
                    <li><?php echo htmlspecialchars($entry['mood']); ?> <span>×<?php echo $entry['count']; ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </main>

    <?php include 'footer.php'; ?>

</body>
</html>
