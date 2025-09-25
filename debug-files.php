<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo "<h2>CineShelf Server File Debug</h2>";

// Check if data directory exists
if (!file_exists('data')) {
    echo "<p><strong>❌ No 'data' directory found!</strong></p>";
    echo "<p>Create a 'data' folder in your web root.</p>";
    exit;
}

echo "<p>✅ Data directory exists</p>";

// List all backup files
$files = glob("data/cineshelf_backup_*.json");

if (empty($files)) {
    echo "<p><strong>❌ No backup files found!</strong></p>";
    echo "<p>No files matching pattern: data/cineshelf_backup_*.json</p>";
} else {
    echo "<h3>📁 Found " . count($files) . " backup files:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Filename</th><th>Size</th><th>Modified</th><th>User Part</th><th>Actions</th></tr>";
    
    foreach ($files as $file) {
        $filename = basename($file);
        $size = round(filesize($file) / 1024, 1) . ' KB';
        $modified = date('Y-m-d H:i:s', filemtime($file));
        
        // Extract user part from filename
        $userPart = str_replace(['cineshelf_backup_', '.json'], '', $filename);
        
        echo "<tr>";
        echo "<td>{$filename}</td>";
        echo "<td>{$size}</td>";
        echo "<td>{$modified}</td>";
        echo "<td><strong>{$userPart}</strong></td>";
        echo "<td><a href='?delete={$filename}' onclick='return confirm(\"Delete {$filename}?\")'>Delete</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $fileToDelete = $_GET['delete'];
    $safeName = basename($fileToDelete); // Security: only filename, no path
    $fullPath = "data/" . $safeName;
    
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            echo "<p>✅ Deleted: {$safeName}</p>";
            echo "<script>setTimeout(() => location.reload(), 1000);</script>";
        } else {
            echo "<p>❌ Failed to delete: {$safeName}</p>";
        }
    }
}

// Show recommended filename for user
echo "<h3>🎯 Recommended Clean Filename:</h3>";
echo "<p><code>cineshelf_backup_klindakoil.json</code></p>";
echo "<p>This is what the system should create for user 'klindakoil'</p>";

// Check server logs
echo "<h3>📋 Recent Server Logs (last 20 lines):</h3>";
$logFile = error_get_last();
if ($logFile) {
    echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 200px; overflow-y: scroll;'>";
    $logs = shell_exec('tail -20 ' . ini_get('log_errors') . ' 2>/dev/null');
    echo $logs ? htmlspecialchars($logs) : 'No recent logs found';
    echo "</pre>";
}

echo "<h3>🔧 Next Steps:</h3>";
echo "<ol>";
echo "<li>Replace your backup.php with the fixed version</li>";
echo "<li>Delete any weird backup files using the table above</li>";
echo "<li>Create a fresh backup from your PC</li>";
echo "<li>Try restore on mobile</li>";
echo "</ol>";
?>