<?php

declare(strict_types=1);

/**
 * Minimal SMTP server fixture for integration tests.
 *
 * Listens on 127.0.0.1, prints READY on stdout once listening, answers a
 * minimal SMTP dialog, captures the DATA payload, and prints it as JSON on
 * stdout when the client disconnects.
 */

$port = (int) ($argv[1] ?? 0);

$errno  = null;
$errstr = null;
$server = stream_socket_server("tcp://127.0.0.1:{$port}", $errno, $errstr);
if (false === $server) {
    fwrite(STDERR, data: ($errstr ?? '') . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, data: 'READY' . PHP_EOL);

$client = stream_socket_accept($server, timeout: 30);
if (false === $client) {
    exit(1);
}

fwrite($client, data: "220 fake-smtp ESMTP\r\n");

$data = '';
$line = fgets($client);

while (false !== $line) {
    $line = rtrim(
        string    : $line,
        characters: "\r\n",
    );

    if (str_starts_with($line, 'EHLO') || str_starts_with($line, 'HELO')) {
        fwrite($client, data: "250-fake-smtp\r\n250 OK\r\n");
        $line = fgets($client);
        continue;
    }

    if (str_starts_with($line, 'MAIL FROM') || str_starts_with($line, 'RCPT TO')) {
        fwrite($client, data: "250 OK\r\n");
        $line = fgets($client);
        continue;
    }

    if ('DATA' === $line) {
        fwrite($client, data: "354 End with <CRLF>.<CRLF>\r\n");

        $dataLine = fgets($client);
        while (false !== $dataLine) {
            if (".\r\n" === $dataLine) {
                break;
            }

            $data     .= $dataLine;
            $dataLine = fgets($client);
        }

        fwrite($client, data: "250 OK\r\n");
        $line = fgets($client);
        continue;
    }

    if ('QUIT' === $line) {
        fwrite($client, data: "221 Bye\r\n");
        break;
    }

    fwrite($client, data: "250 OK\r\n");
    $line = fgets($client);
}

fclose($client);

$encoded = json_encode(['data' => $data]);
fwrite(STDOUT, data: (is_string($encoded) ? $encoded : '{}') . PHP_EOL);
