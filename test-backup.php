<?php
// test-backup.php - Place this in your website root to test/debug backups
?>
<!DOCTYPE html>
<html>
<head>
    <title>CineShelf Backup System Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .file { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .code { background: #333; color: #0f0; padding: 10px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        button { padding: 5px 10px; margin: 0 5px; cursor: pointer; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🎬 CineShelf Backup System Test</h1>
    
    <?php
    // Check if data directory exists
    echo "<h2>📁 Directory Check</h2>";
    if (file_exists('data')) {
        echo "<p class='success'>✅ Data directory exists</p>";
        
        // Check permissions
        if (is_writable('data')) {
            echo "<p class='success'>✅ Data directory is writable</p>";
        } else {
            echo "<p class='error'>❌ Data directory is NOT writable - fix permissions!</p>";
            echo "<p>Run: <code>chmod 755 data</code></p>";
        }
    } else {
        echo "<p class='error'>❌ Data directory does not exist</p>";
        echo "<p>Creating directory...</p>";
        if (mkdir('data', 0755, true)) {
            echo "<p class='success'>✅ Directory created successfully!</p>";
        } else {
            echo "<p class='error'>❌ Could not create directory - check server permissions</p>";
        }
    }
    
    // Check backup.php and restore.php
    echo "<h2>📄 Required Files Check</h2>";
    $required_files = ['backup.php', 'restore.php'];
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "<p class='success'>✅ {$file} exists</p>";
        } else {
            echo "<p class='error'>❌ {$file} is missing - please upload it!</p>";
        }
    }
    
    // List all backup files
    echo "<h2>💾 Existing Backups</h2>";
    $backups = glob("data/cineshelf_backup_*.json");
    
    if (empty($backups)) {
        echo "<p>No backups found yet.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>User</th><th>Filename</th><th>Size</th><th>Last Modified</th><th>Actions</th></tr>";
        
        foreach ($backups as $backup) {
            $filename = basename($backup);
            $user = str_replace(['cineshelf_backup_', '.json'], '', $filename);
            $size = round(filesize($backup) / 1024, 2) . ' KB';
            $modified = date('Y-m-d H:i:s', filemtime($backup));
            
            echo "<tr>";
            echo "<td><strong>{$user}</strong></td>";
            echo "<td>{$filename}</td>";
            echo "<td>{$size}</td>";
            echo "<td>{$modified}</td>";
            echo "<td>";
            echo "<button onclick=\"viewBackup('{$user}')\">View</button>";
            echo "<button onclick=\"if(confirm('Delete backup for {$user}?')) deleteBackup('{$user}')\">Delete</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Handle actions
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $user = isset($_GET['user']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['user']) : '';
        
        if ($action === 'delete' && $user) {
            $file = "data/cineshelf_backup_{$user}.json";
            if (file_exists($file)) {
                if (unlink($file)) {
                    echo "<p class='success'>✅ Deleted backup for user: {$user}</p>";
                    echo "<script>setTimeout(() => location.href='test-backup.php', 1000);</script>";
                }
            }
        }
        
        if ($action === 'view' && $user) {
            $file = "data/cineshelf_backup_{$user}.json";
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
                echo "<h3>Backup for user: {$user}</h3>";
                echo "<div class='file'>";
                echo "<p><strong>Items:</strong> " . count($data['copies'] ?? []) . "</p>";
                echo "<p><strong>Movies:</strong> " . count($data['movies'] ?? []) . "</p>";
                echo "<p><strong>Backup Time:</strong> " . ($data['_backup_metadata']['backup_time'] ?? 'Unknown') . "</p>";
                echo "<details><summary>View Raw Data</summary>";
                echo "<pre class='code'>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                echo "</details>";
                echo "</div>";
            }
        }
    }
    ?>
    
    <h2>🧪 Test Backup/Restore</h2>
    <div class="warning">
        <strong>⚠️ Testing Instructions:</strong><br>
        1. Open your CineShelf app on your main device<br>
        2. Click "Backup to Server" in the Data tab<br>
        3. Refresh this page - you should see your backup appear<br>
        4. On another device, click "Restore from Server" to sync<br>
    </div>
    
    <h2>🔧 Cleanup Old Files</h2>
    <p>If you have old backup files in other locations, you should delete them:</p>
    <ul>
        <?php
        // Check for old files that should be removed
        $old_locations = [
            'app/backup.php',
            'app/restore.php',
            'bkp/backup.php', 
            'bkp/restore.php',
            'data/backup.php',
            'data/restore.php',
            'api/backup.php',
            'api/restore.php'
        ];
        
        $found_old = false;
        foreach ($old_locations as $old) {
            if (file_exists($old)) {
                echo "<li class='error'>Delete: {$old}</li>";
                $found_old = true;
            }
        }
        
        if (!$found_old) {
            echo "<li class='success'>✅ No old backup files found - system is clean!</li>";
        }
        ?>
    </ul>
    
    <script>
    function viewBackup(user) {
        location.href = '?action=view&user=' + user;
    }
    
    function deleteBackup(user) {
        location.href = '?action=delete&user=' + user;
    }
    </script>
</body>
</html>