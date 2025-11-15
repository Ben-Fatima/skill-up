<?php

$host = 'localhost';
$port = 8000;

$server = stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);

if (!$server) {
  die("Error: $errstr ($errno)\n");
}

echo "WebSocket server listening on ws://{$host}:{$port}\n";

$clients      = [];
$handshaked   = [];

while (true) {
  $read = $clients;
  $read[] = $server;
  //echo print_r($read, true);
  $write = null;
  $except = null;
  if (stream_select($read, $write, $except, null) === false) {
      break;
  }

  if (in_array($server, $read, true)) {
      $client = @stream_socket_accept($server, 0);
      if ($client) {
          stream_set_blocking($client, false);
          $clients[] = $client;
          $id = (int)$client;
          $handshaked[$id] = false;
          echo "New connection: {$id}\n";
      }
      $serverKey = array_search($server, $read, true);
      unset($read[$serverKey]);
  }

  foreach ($read as $client) {
      $id = (int)$client;
      $data = @fread($client, 2048);
      if ($data === '' || $data === false) {
          echo "Connection closed: {$id}\n";
          fclose($client);
          $idx = array_search($client, $clients, true);
          if ($idx !== false) {
              unset($clients[$idx]);
          }
          unset($handshaked[$id]);
          continue;
      }

      if (!$handshaked[$id]) {
          if (perform_handshake($client, $data)) {
              $handshaked[$id] = true;
              echo "Handshake done for {$id}\n";
          } else {
              echo "Handshake failed for {$id}\n";
              fclose($client);
              $idx = array_search($client, $clients, true);
              if ($idx !== false) {
                  unset($clients[$idx]);
              }
              unset($handshaked[$id]);
          }
          continue;
      }

      $message = decode_frame($data);
      if ($message === null || $message === '') {
          continue;
      }
      //echo "Message from {$id}: {$message}\n";

      $frame = encode_frame($message);
      foreach ($clients as $other) {
          $otherId = (int)$other;
          if (!isset($handshaked[$otherId]) || !$handshaked[$otherId]) {
              continue;
          }
          @fwrite($other, $frame);
      }
  }
}

function perform_handshake($client, string $request): bool
{
    if (!preg_match("/Sec-WebSocket-Key: (.*)\r\n/i", $request, $matches)) {
        return false;
    }

    $key = trim($matches[1]);
    $GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    $acceptKey = base64_encode(sha1($key . $GUID, true));

    $headers = "HTTP/1.1 101 Switching Protocols\r\n" .
               "Upgrade: websocket\r\n" .
               "Connection: Upgrade\r\n" .
               "Sec-WebSocket-Accept: {$acceptKey}\r\n" .
               "\r\n";

    fwrite($client, $headers);

    return true;
}

function decode_frame(string $data): ?string
{
  if (strlen($data) < 2) {
    return null;
  }

    $bytes = unpack('Cfirst/Csecond', substr($data, 0, 2));
    $opcode = $bytes['first'] & 0x0F;
    $masked = ($bytes['second'] >> 7) & 0x1;
    $length = $bytes['second'] & 0x7F;

    if ($opcode !== 1) {
        return null;
    }

    $offset = 2;

    if ($length === 126) {
        $extended = unpack('n', substr($data, $offset, 2))[1];
        $length = $extended;
        $offset += 2;
    } elseif ($length === 127) {
        return null;
    }

    if ($masked) {
        $mask = substr($data, $offset, 4);
        $offset += 4;
        $payload = substr($data, $offset, $length);

        $unmasked = '';
        for ($i = 0; $i < $length; $i++) {
            $unmasked .= $payload[$i] ^ $mask[$i % 4];
        }

        return $unmasked;
    } else {
        return substr($data, $offset, $length);
    }
}

function encode_frame(string $payload): string
{
    $length = strlen($payload);
    $firstByte = 0x80 | 0x1;

    if ($length <= 125) {
        return pack('CC', $firstByte, $length) . $payload;
    } elseif ($length <= 65535) {
        return pack('CCn', $firstByte, 126, $length) . $payload;
    } else {
        $lengthBin = pack('J', $length);
        return pack('CC', $firstByte, 127) . $lengthBin . $payload;
    }
}
