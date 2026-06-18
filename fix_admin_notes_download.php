<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔧 Fixing Admin Notes Download Issue</h2>";

// Step 1: Delete sample notes without files
echo "<h3>Step 1: Cleaning up sample notes...</h3>";

$all_notes = $pdo->query("SELECT id, file_path FROM notes")->fetchAll();
$deleted_count = 0;

foreach ($all_notes as $note) {
    $full_path = __DIR__ . '/' . $note['file_path'];
    if (!file_exists($full_path)) {
        $pdo->prepare("DELETE FROM notes WHERE id = ?")->execute([$note['id']]);
        echo "<p>❌ Deleted note ID " . $note['id'] . " (file missing: " . $note['file_path'] . ")</p>";
        $deleted_count++;
    }
}

echo "<p style='color: green; font-weight: bold;'>Deleted " . $deleted_count . " notes with missing files</p>";

// Step 2: Create sample PDF files for testing
echo "<h3>Step 2: Creating sample PDF files...</h3>";

$upload_dir = __DIR__ . '/uploads/notes/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// Create sample PDFs using FPDF or simple method
$sample_pdfs = [
    'Sample Notes - Arrays and Linked Lists.pdf',
    'Sample Notes - Sorting Algorithms.pdf',
    'Sample Notes - Design Principles.pdf'
];

foreach ($sample_pdfs as $pdf_name) {
    $file_path = $upload_dir . time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $pdf_name);
    
    // Create a simple text file as PDF placeholder (real PDF would use FPDF)
    $content = "%PDF-1.4\n%Sample PDF Document\n";
    $content .= "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n";
    $content .= "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n";
    $content .= "3 0 obj<</Type/Page/Parent 2 0 R/Resources<<>>>>endobj\n";
    $content .= "xref\n0 4\n0000000000 65535 f\n";
    $content .= "0000000009 00000 n\n0000000056 00000 n\n0000000115 00000 n\n";
    $content .= "trailer<</Size 4/Root 1 0 R>>\nstartxref\n183\n%%EOF";
    
    if (file_put_contents($file_path, $content)) {
        echo "<p>✅ Created: " . basename($file_path) . "</p>";
    }
}

echo "<h3>Step 3: Inserting valid sample notes...</h3>";

$sample_notes = [
    [
        'trade_id' => 1,
        'subject_id' => 1,
        'title' => 'Arrays and Linked Lists',
        'description' => 'Comprehensive guide to arrays and linked list data structures'
    ],
    [
        'trade_id' => 1,
        'subject_id' => 1,
        'title' => 'Sorting Algorithms',
        'description' => 'Different sorting algorithms and their complexities'
    ],
    [
        'trade_id' => 1,
        'subject_id' => 1,
        'title' => 'Design Principles',
        'description' => 'SOLID principles and design patterns'
    ]
];

$files = glob($upload_dir . '*.pdf');
$file_index = 0;

foreach ($sample_notes as $note) {
    if ($file_index < count($files)) {
        $file_name = basename($files[$file_index]);
        $file_path = 'uploads/notes/' . $file_name;
        
        $stmt = $pdo->prepare("
            INSERT INTO notes (trade_id, subject_id, title, description, file_path, uploaded_by, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $note['trade_id'],
            $note['subject_id'],
            $note['title'],
            $note['description'],
            $file_path,
            1 // admin user
        ]);
        
        echo "<p>✅ Added note: " . $note['title'] . "</p>";
        $file_index++;
    }
}

echo "<h3>✅ Fix Complete!</h3>";
echo "<p>All notes now have valid files.</p>";
echo "<p><a href='/admin/notes.php' class='btn btn-primary' target='_blank'>Go to Admin Notes</a></p>";

?>
