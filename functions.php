<?php
// Initialise database connection
$db = getDb();

// Function to get or create the SQLite DB
function getDb(): PDO {
    $config = require __DIR__ . '/config.php';
    $db_path = $config['db_path'] ?? __DIR__ . '/journal.db';
    $db_exists = file_exists($db_path);

    // Ensure parent directory exists
    $dir = dirname($db_path);
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }

    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$db_exists) {
        $db->exec("CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp TEXT DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
            content TEXT NOT NULL,
            mood TEXT CHECK( mood IN (
                '🙃 Happy',
                '😞 Sad',
                '😏 Neutral',
                '😡 Angry',
                '🤪 Excited',
                '😰 Anxious'
            ))
        );

        CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL
        );

        CREATE TABLE IF NOT EXISTS entry_tags (
            entry_id INTEGER,
            tag_id INTEGER,
            PRIMARY KEY (entry_id, tag_id),
            FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        );");
    }

    return $db;
}

// Function to add an entry
function addEntry($content, $mood, $tags) {
    global $db;

    $stmt = $db->prepare("INSERT INTO entries (content, mood) VALUES (:content, :mood)");
    $stmt->execute([':content' => $content, ':mood' => $mood]);
    $entryId = $db->lastInsertId();

    foreach ($tags as $tag) {
        $stmt = $db->prepare("INSERT INTO tags (name) VALUES (:name) ON CONFLICT(name) DO NOTHING");
        $stmt->execute([':name' => $tag]);

        $stmt = $db->prepare("SELECT id FROM tags WHERE name = :name");
        $stmt->execute([':name' => $tag]);
        $tagId = $stmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO entry_tags (entry_id, tag_id) VALUES (:entry_id, :tag_id)");
        $stmt->execute([':entry_id' => $entryId, ':tag_id' => $tagId]);
    }
}

// Function to retrieve entries with tags
function getEntries($limit = 10, $offset = 0) {
    global $db;

    $stmt = $db->prepare("SELECT e.id, e.timestamp, e.content, e.mood, GROUP_CONCAT(t.name) AS tags 
                          FROM entries e 
                          LEFT JOIN entry_tags et ON e.id = et.entry_id 
                          LEFT JOIN tags t ON et.tag_id = t.id 
                          GROUP BY e.id 
                          ORDER BY e.timestamp DESC 
                          LIMIT :limit OFFSET :offset");

    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get total entry count
function getEntryCount() {
    global $db;
    return $db->query("SELECT COUNT(*) FROM entries")->fetchColumn();
}

// Function to retrieve a single entry by ID
function getEntryById($id) {
    global $db;

    $stmt = $db->prepare("SELECT e.id, e.timestamp, e.content, e.mood, GROUP_CONCAT(t.name) AS tags 
                          FROM entries e 
                          LEFT JOIN entry_tags et ON e.id = et.entry_id 
                          LEFT JOIN tags t ON et.tag_id = t.id 
                          WHERE e.id = :id GROUP BY e.id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update an entry
function updateEntry($id, $content, $mood, $tags) {
    global $db;

    $stmt = $db->prepare("UPDATE entries SET content = :content, mood = :mood WHERE id = :id");
    $stmt->execute([':content' => $content, ':mood' => $mood, ':id' => $id]);

    $stmt = $db->prepare("DELETE FROM entry_tags WHERE entry_id = :id");
    $stmt->execute([':id' => $id]);

    foreach ($tags as $tag) {
        $stmt = $db->prepare("INSERT INTO tags (name) VALUES (:name) ON CONFLICT(name) DO NOTHING");
        $stmt->execute([':name' => $tag]);

        $stmt = $db->prepare("SELECT id FROM tags WHERE name = :name");
        $stmt->execute([':name' => $tag]);
        $tagId = $stmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO entry_tags (entry_id, tag_id) VALUES (:entry_id, :tag_id)");
        $stmt->execute([':entry_id' => $id, ':tag_id' => $tagId]);
    }
}

// Function to delete an entry
function deleteEntry($id) {
    global $db;
    // Remove the entry
    $stmt = $db->prepare("DELETE FROM entries WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Remove all tags associated with the entry
    $stmt = $db->prepare("DELETE FROM entry_tags WHERE entry_id = :id");
    $stmt->execute([':id' => $id]);
    
    //Remove the tag(s) if not used elsewhere
    $stmt = $db->prepare("DELETE FROM tags as t  
        WHERE NOT EXISTS (select et.tag_id FROM entry_tags AS et WHERE et.tag_id = t.id)");
    $stmt->execute();
}

// Function to retrieve entries by tag
function getEntriesByTag($tag, $limit = 10, $offset = 0) {
    global $db;

    $stmt = $db->prepare("SELECT e.id, e.timestamp, e.content, e.mood, GROUP_CONCAT(t.name) AS tags 
                          FROM entries e 
                          JOIN entry_tags et ON e.id = et.entry_id 
                          JOIN tags t ON et.tag_id = t.id 
                          WHERE t.name = :tag 
                          GROUP BY e.id ORDER BY e.timestamp DESC 
                          LIMIT :limit OFFSET :offset");

    $stmt->bindValue(':tag', $tag, PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Tagcloud on homepage
function getTagCloud() {
    global $db;

    $stmt = $db->query("SELECT t.name, COUNT(et.entry_id) AS count 
                        FROM tags t 
                        JOIN entry_tags et ON t.id = et.tag_id 
                        GROUP BY t.name 
                        ORDER BY count DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Format timestamp to local timezone
function formatTimestampForLocal(string $timestamp): string {
    $config = require __DIR__ . '/config.php';
    $dt = new DateTime($timestamp, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone($config['timezone']));
    return $dt->format('d F Y \a\t H:i');
}
