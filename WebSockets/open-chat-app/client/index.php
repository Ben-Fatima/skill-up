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

      case '/chat.js':
        header('Content-Type: application/javascript; charset=utf-8');
        echo <<<JS

          const messagesDiv = document.getElementById('messages');
          const form = document.getElementById('chat-form');
          const input = document.getElementById('message-input');

          const socket = new WebSocket('ws://localhost:8000');

          socket.addEventListener('open', () => {
            console.log('WebSocket connected');
          });

          socket.addEventListener('close', () => {
            console.log('WebSocket disconnected');
          });

          socket.addEventListener('error', (event) => {
            console.error('WebSocket error:', event);
          });

          socket.addEventListener('message', (event) => {
            console.log('New message from server:', event.data);
          
            try {
              const msg = JSON.parse(event.data);
              addMessageToChat(msg.username, msg.content, msg.sent_at);
            } catch (e) {
              console.error('Invalid JSON from server', e);
            }
          });

          function addMessageToChat(username, content, sentAt) {
            const p = document.createElement('p');
            const time = sentAt ? ' [' + sentAt + ']' : '';
            p.textContent = username + time + ': ' + content;
            messagesDiv.appendChild(p);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
          }

          form.addEventListener('submit', (event) => {
            event.preventDefault();
            const text = input.value.trim();
            if (!text || socket.readyState !== WebSocket.OPEN) {
              return;
            }
          
            const message = {
              event: 'NEW_MESSAGE',
              username: window.CHAT_USERNAME,
              content: text,
              sent_at: new Date().toISOString()
            };
          
            socket.send(JSON.stringify(message));
            input.value = '';
          });
          JS;
        exit; 

      case '/':
        if (isset($_SESSION['username'])) {
          $name = htmlspecialchars($_SESSION['username']);
          header('Content-Type: text/html; charset=utf-8');
          echo '<!DOCTYPE html>
          <html lang="en">
          <head>
            <meta charset="UTF-8">
            <title>Simple Chat</title>
          </head>
          <body>
            <h1>Chat – Logged in as ' . $name . '</h1>

            <form method="POST" action="/logout" style="margin-bottom: 1rem;">
              <button type="submit">Logout</button>
            </form>

            <div id="messages" style="border:1px solid #ccc; padding:10px; height:300px; overflow-y:auto; margin-bottom:1rem;">
            </div>
            <form id="chat-form">
              <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
              <button type="submit" onclick="submit">Send</button>
            </form>

            <script>
              window.CHAT_USERNAME = ' . json_encode($name) . ';
            </script>
            <script src="/chat.js"></script>
          </body>
          </html>';
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
