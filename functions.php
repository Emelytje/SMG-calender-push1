<?php
require_once __DIR__ . '/config.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentUserName(): ?string {
    return $_SESSION['username'] ?? null;
}

function currentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function isAdmin(): bool {
    return currentUserRole() === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        echo "Geen toegang.";
        exit;
    }
}


function loginUser(PDO $pdo, string $username, string $password): bool {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    $dbPass = $user['password'] ?? '';
    $ok = false;

    if (preg_match('/^\$2[ayb]\$/', $dbPass)) {
        if (password_verify($password, $dbPass)) {
            $ok = true;
        }
    } else {
        if ($password === $dbPass) {
            $ok = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $upd->execute([$newHash, $user['id']]);
            $user['password'] = $newHash;
        }
    }

    if (!$ok) {
        return false;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    return true;
}
