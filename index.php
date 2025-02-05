<?php
header("Access-Control-Allow-Origin: *"); // Allow all domains
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: text/html");

$jsonFile = 'data.json';
$jsonData = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['word'], $_POST['clue'], $_POST['id']) && $_POST['id'] !== '') {
        // Update existing entry
        foreach ($jsonData as &$entry) {
            if ($entry['id'] == $_POST['id']) {
                $entry['word'] = $_POST['word'];
                $entry['clue'] = $_POST['clue'];
                break;
            }
        }
    } elseif (isset($_POST['word'], $_POST['clue'])) {
        $word = strtoupper(trim($_POST['word']));
        $clue = trim($_POST['clue']);
        
        // Check for duplicate words
        foreach ($jsonData as $entry) {
            if (strcasecmp($entry['word'], $word) === 0) {
                header("Location: index.php?error=duplicate");
                exit;
            }
        }
        
        // Add new entry
        $newId = $jsonData ? max(array_column($jsonData, 'id')) + 1 : 1;
        $jsonData[] = ["id" => $newId, "word" => $word, "clue" => $clue];
    }
    
    file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit;
}

if (isset($_GET['delete'])) {
    $jsonData = array_values(array_filter($jsonData, fn($item) => $item['id'] != $_GET['delete']));
    file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ô Chữ Kambria</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function validateWord(word) {
            return word
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "") // Remove diacritics
                .replace(/\s+/g, "") // Remove spaces
                .toUpperCase(); // Convert to uppercase
        }
        function handleInput(event) {
            event.target.value = validateWord(event.target.value);
        }
        function editEntry(id, word, clue) {
            document.getElementById('id').value = id;
            document.getElementById('word').value = word;
            document.getElementById('clue').value = clue;
            document.getElementById('submit-btn').textContent = 'Update Word';
        }
    </script>
</head>
<body class="container mt-4">
    <h2 class="mb-4">Quản trị Ô Chữ Kambria</h2>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
        <div class="alert alert-danger">Từ này bị trùng!</div>
    <?php endif; ?>
    <form method="POST" class="mb-3">
        <input type="hidden" name="id" id="id">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="word" id="word" class="form-control" placeholder="Word" required oninput="handleInput(event)">
            </div>
            <div class="col-md-4">
                <input type="text" name="clue" id="clue" class="form-control" placeholder="Clue" required>
            </div>
            <div class="col-md-4">
                <button type="submit" id="submit-btn" class="btn btn-primary">Add Word</button>
            </div>
        </div>
    </form>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Word</th>
                <th>Clue</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jsonData as $entry): ?>
                <tr>
                    <td><?= $entry['id'] ?></td>
                    <td><?= htmlspecialchars($entry['word']) ?></td>
                    <td><?= htmlspecialchars($entry['clue']) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editEntry('<?= $entry['id'] ?>', '<?= htmlspecialchars($entry['word']) ?>', '<?= htmlspecialchars($entry['clue']) ?>')">Edit</button>
                        <a href="?delete=<?= $entry['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this word?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
