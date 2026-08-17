<?php

declare(strict_types=1);

/**
 * Minimal SMTP server fixture for integration tests.
 *
 * Listens on 127.0.0.1, prints READY on stdout once listening, answers a
 * minimal SMTP dialog, captures the DATA payload, and prints it as JSON on
 * stdout when the client disconnects.
 */

use function fclose;
use function fgets;
use function fwrite;
use function json_encode;
use function rtrim;
use function str_starts_with;
use function stream_socket_accept;
use function stream_socket_server;

$port = (int) ($argv[1] ?? 0);

$server = stream_socket_server('tcp://127.0.0.1:' . $port, $errno, $errstr);
if ($server === false) {
    fwrite(STDERR, $errstr . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'READY' . PHP_EOL);

$client = stream_socket_accept($server, timeout: 30);
if ($client === false) {
    exit(1);
}

fwrite($client, "220 fake-smtp ESMTP\r\n");

$data = '';

while (($line = fgets($client)) !== false) {
    $line = rtrim($line, "\r\n");

    if (str_starts_with($line, 'EHLO') || str_starts_with($line, 'HELO')) {
        fwrite($client, "250-fake-smtp\r\n250 OK\r\n");
        continue;
    }

    if (str_starts_with($line, 'MAIL FROM') || str_starts_with($line, 'RCPT TO')) {
        fwrite($client, "250 OK\r\n");
        continue;
    }

    if ($line === 'DATA') {
        fwrite($client, "354 End with <CRLF>.<CRLF>\r\n");

        while (($dataLine = fgets($client)) !== false) {
            if ($dataLine === ".\r\n") {
                break;
            }

            $data .= $dataLine;
        }

        fwrite($client, "250 OK\r\n");
        continue;
    }

    if ($line === 'QUIT') {
        fwrite($client, "221 Bye\r\n");
        break;
    }

    fwrite($client, "250 OK\r\n");
}

fclose($client);
fwrite(STDOUT, (json_encode(['data' => $data]) ?: '{}') . PHP_EOL);
