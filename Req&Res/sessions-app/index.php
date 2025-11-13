<?php
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

function redirect(string $to): void {
  header("Location: $to", true, 302);
  exit;
}

switch ($method) {
  case 'GET':
    switch ($path) {
      case '/login':
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
        <html lang="en">
        <body>
          <form method="POST" action="/login">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <br>
            <button type="submit">Login</button>
          </form>
        </body>
        </html>';
        exit;

      case '/':
        if (isset($_SESSION['username'])) {
          $name = htmlspecialchars($_SESSION['username']);
          header('Content-Type: text/html; charset=utf-8');
          echo 'Welcome, ' . $name . '!<br>';
          echo '<form method="POST" action="/logout">
                  <button type="submit">Logout</button>
                </form>';
          exit;
        } else {
          redirect('/login');
        }

      default:
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo 'Not found. <a href="/login">Go to login</a>.';
        exit;
    }

  case 'POST':
    switch ($path) {
      case '/login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username === 'tamo' && $password === 'pass') {
          $_SESSION['username'] = $username;
          redirect('/');
        } else {
          header('Content-Type: text/html; charset=utf-8');
          echo 'Invalid credentials. <a href="/login">Try again</a>.';
          exit;
        }

      case '/logout':
        $_SESSION = [];
        session_destroy();
        redirect('/login');

      default:
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo 'Not found.';
        exit;
    }

  default:
    exit;
}
