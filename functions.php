<?php
// Database connection
$db = new PDO('sqlite:journal.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables if they don't exist
$db->exec("CREATE TABLE IF NOT EXISTS entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp TEXT DEFAULT (strftime('%Y-%m-%d %H:%M:%S', 'now')),
    content TEXT NOT NULL,
    mood TEXT CHECK( mood IN ('🙃 Happy', '😞 Sad', '😏 Neutral', '😡 Angry', '🤪 Excited') )
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

// Function to add an entry
function addEntry($content, $mood, $tags) {
    global $db;
    
    // Insert entry
    $stmt = $db->prepare("INSERT INTO entries (content, mood) VALUES (:content, :mood)");
    $stmt->execute([':content' => $content, ':mood' => $mood]);
    $entryId = $db->lastInsertId();
    
    // Process tags
    foreach ($tags as $tag) {
        // Insert tag if it doesn't exist
        $stmt = $db->prepare("INSERT INTO tags (name) VALUES (:name) ON CONFLICT(name) DO NOTHING");
        $stmt->execute([':name' => $tag]);
        
        // Get tag ID
        $stmt = $db->prepare("SELECT id FROM tags WHERE name = :name");
        $stmt->execute([':name' => $tag]);
        $tagId = $stmt->fetchColumn();
        
        // Link tag to entry
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
    
    // Bind values correctly as integers
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
    
    // Update entry content and mood
    $stmt = $db->prepare("UPDATE entries SET content = :content, mood = :mood WHERE id = :id");
    $stmt->execute([':content' => $content, ':mood' => $mood, ':id' => $id]);
    
    // Remove existing tags
    $stmt = $db->prepare("DELETE FROM entry_tags WHERE entry_id = :id");
    $stmt->execute([':id' => $id]);
    
    // Process new tags
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
    
    $stmt = $db->prepare("DELETE FROM entries WHERE id = :id");
    $stmt->execute([':id' => $id]);
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
