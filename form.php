<?php
require __DIR__ . '/config.php';

// DEBUG: Always show errors on Wasmer
error_reporting(E_ALL);
ini_set('display_errors', '1');

function sanitizeInput(string $data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function connectDatabase(): PDO
{
    if (!class_exists('PDO')) {
        throw new RuntimeException('PDO is not available.');
    }

    $dsn = getenv('DB_DSN') ?: ($GLOBALS['dbDsn'] ?? null);
    
    if (!$dsn) {
        throw new RuntimeException('DB_DSN not configured.');
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (str_starts_with(strtolower($dsn), 'sqlite:')) {
        return new PDO($dsn, null, null, $options);
    }

    return new PDO(
        $dsn, 
        getenv('DB_USER') ?: $GLOBALS['dbUser'], 
        getenv('DB_PASS') ?: $GLOBALS['dbPass'], 
        $options
    );
}

function ensureContactTable(PDO $pdo): void
{
    $driver = strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS contact_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                comment TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

$name = $email = $comment = '';
$nameErr = $emailErr = $messageErr = '';
$success = false;
$dbError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($name === '') {
        $nameErr = 'Name ist erforderlich.';
    }

    if ($email === '') {
        $emailErr = 'E-Mail ist erforderlich.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = 'Bitte gib eine gültige E-Mail-Adresse ein.';
    }

    if ($comment === '') {
        $messageErr = 'Bitte schreibe eine Nachricht.';
    }

    if ($nameErr === '' && $emailErr === '' && $messageErr === '') {
        try {
            $pdo = connectDatabase();
            ensureContactTable($pdo);

            $driver = strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
            if ($driver === 'sqlite') {
                $stmt = $pdo->prepare(
                    'INSERT INTO contact_messages (name, email, comment, created_at) VALUES (:name, :email, :comment, CURRENT_TIMESTAMP)'
                );
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO contact_messages (name, email, comment, created_at) VALUES (:name, :email, :comment, NOW())'
                );
            }

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':comment' => $comment,
            ]);
            $success = true;
        } catch (Throwable $e) {
            $dbError = 'DB-Fehler: ' . $e->getMessage() . ' (Driver: ' . $driver ?? 'unknown' . ')';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontakt</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1 class="title">Kontaktieren sie mich hier</h1>

    <?php if ($success) : ?>
        <div class="kontaktieren">
            <div class="contact-form">
                <p>Vielen Dank, <?php echo sanitizeInput($name); ?>!</p>
                <p>Deine Nachricht wurde erfolgreich gespeichert.</p>
                <p>Wir antworten dir an: <?php echo sanitizeInput($email); ?></p>
            </div>
        </div>
    <?php else : ?>
        <div class="kontaktieren">
            <?php if ($dbError !== '') : ?>
                <p class="error" style="white-space: pre-wrap;"><?php echo sanitizeInput($dbError); ?></p>
            <?php endif; ?>

            <form class="contact-form" method="post" action="form.php">
                <div class="form-row">
                    <label for="name">Name:</label>
                    <input id="name" type="text" name="name" value="<?php echo sanitizeInput($name); ?>" required>
                    <?php if ($nameErr !== '') : ?><span class="error">* <?php echo sanitizeInput($nameErr); ?></span><?php endif; ?>
                </div>

                <div class="form-row">
                    <label for="email">E-mail:</label>
                    <input id="email" type="email" name="email" value="<?php echo sanitizeInput($email); ?>" required>
                    <?php if ($emailErr !== '') : ?><span class="error">* <?php echo sanitizeInput($emailErr); ?></span><?php endif; ?>
                </div>

                <div class="form-row comment-row">
                    <label for="comment">Comment:</label>
                    <textarea id="comment" name="comment" rows="5" cols="40" required><?php echo sanitizeInput($comment); ?></textarea>
                    <?php if ($messageErr !== '') : ?><span class="error">* <?php echo sanitizeInput($messageErr); ?></span><?php endif; ?>
                </div>

                <div class="form-row submit-row">
                    <input type="submit" name="submit" value="Submit">
                </div>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
