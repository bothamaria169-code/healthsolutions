<?php
declare(strict_types=1);

/**
 * A small IMAP reader for the info@ mailbox (Admin → Mailbox). It talks IMAP
 * over SSL directly, so it works on hosts without PHP's imap extension, and
 * logs in with the same mailbox and password the site sends mail with.
 */
final class Imap
{
    /** @var resource|null */
    private $sock = null;
    private int $tag = 0;
    public string $error = '';

    public static function ready(): bool
    {
        return SMTP_USER !== '' && SMTP_PASS !== '';
    }

    public function connect(): bool
    {
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $this->sock = @stream_socket_client('ssl://' . SMTP_HOST . ':993', $no, $msg, 15, STREAM_CLIENT_CONNECT, $ctx) ?: null;
        if (!$this->sock) {
            $this->error = 'Could not reach the mail server: ' . $msg;
            return false;
        }
        stream_set_timeout($this->sock, 30);
        fgets($this->sock);
        $quote = fn(string $s) => '"' . addcslashes($s, '"\\') . '"';
        if (!$this->cmd('LOGIN ' . $quote(SMTP_USER) . ' ' . $quote(SMTP_PASS))['ok']) {
            $this->error = 'The mail server did not accept the mailbox login.';
            return false;
        }
        return $this->cmd('SELECT INBOX')['ok'];
    }

    public function close(): void
    {
        if ($this->sock) {
            $this->cmd('LOGOUT');
            fclose($this->sock);
            $this->sock = null;
        }
    }

    /** Sends one command; returns the raw response with literals inlined. */
    private function cmd(string $command): array
    {
        $tag = 'A' . (++$this->tag);
        fwrite($this->sock, $tag . ' ' . $command . "\r\n");
        $data = '';
        while (($line = fgets($this->sock)) !== false) {
            if (preg_match('/\{(\d+)\}\r\n$/', $line, $m)) {
                $data .= $line;
                $left = (int) $m[1];
                while ($left > 0 && !feof($this->sock)) {
                    $chunk = fread($this->sock, min(8192, $left));
                    if ($chunk === false || $chunk === '') { break; }
                    $data .= $chunk;
                    $left -= strlen($chunk);
                }
                continue;
            }
            if (str_starts_with($line, $tag . ' ')) {
                return ['ok' => str_starts_with($line, $tag . ' OK'), 'data' => $data];
            }
            $data .= $line;
        }
        return ['ok' => false, 'data' => $data];
    }

    /** The newest $limit messages: uid, from, subject, date, seen. */
    public function latest(int $limit = 40): array
    {
        $search = $this->cmd('UID SEARCH ALL');
        preg_match('/\* SEARCH([\d ]*)/', $search['data'], $m);
        $uids = array_slice(array_filter(explode(' ', trim($m[1] ?? ''))), -$limit);
        if (!$uids) {
            return [];
        }
        $res = $this->cmd('UID FETCH ' . implode(',', $uids) . ' (UID FLAGS BODY.PEEK[HEADER.FIELDS (FROM SUBJECT DATE)])');
        $out = [];
        foreach (preg_split('/^\* \d+ FETCH /m', $res['data']) as $chunk) {
            if (!preg_match('/UID (\d+)/', $chunk, $u)) { continue; }
            $h = self::headers($chunk);
            $out[] = ['uid' => (int) $u[1], 'from' => $h['from'] ?? '', 'subject' => $h['subject'] ?? '(no subject)',
                      'date' => $h['date'] ?? '', 'seen' => str_contains($chunk, '\\Seen')];
        }
        usort($out, fn($a, $b) => $b['uid'] <=> $a['uid']);
        return $out;
    }

    /** One full message, parsed; marks it read. */
    public function message(int $uid): ?array
    {
        $res = $this->cmd("UID FETCH {$uid} (BODY.PEEK[])");
        if (!preg_match('/\{(\d+)\}\r\n/', $res['data'], $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $raw = substr($res['data'], $m[0][1] + strlen($m[0][0]), (int) $m[1][0]);
        $this->cmd("UID STORE {$uid} +FLAGS (\\Seen)");
        [$head, $body] = self::split($raw);
        $h = self::headers($head);
        $parts = ['text' => '', 'html' => '', 'files' => []];
        self::walk($head, $body, $parts);
        return ['uid' => $uid, 'from' => $h['from'] ?? '', 'reply_to' => $h['reply-to'] ?? '', 'to' => $h['to'] ?? '',
                'subject' => $h['subject'] ?? '(no subject)', 'date' => $h['date'] ?? '', 'message_id' => $h['message-id'] ?? '',
                'references' => $h['references'] ?? ''] + $parts;
    }

    // ── MIME ─────────────────────────────────────────────────────────────

    private static function split(string $raw): array
    {
        $pos = strpos($raw, "\r\n\r\n");
        return $pos === false ? [$raw, ''] : [substr($raw, 0, $pos), substr($raw, $pos + 4)];
    }

    private static function headers(string $head): array
    {
        $head = preg_replace("/\r?\n[ \t]+/", ' ', $head);
        $out = [];
        foreach (preg_split("/\r?\n/", $head) as $line) {
            if (preg_match('/^([A-Za-z-]+):\s*(.*)$/', $line, $m)) {
                $out[strtolower($m[1])] = trim((string) iconv_mime_decode($m[2], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8'));
            }
        }
        return $out;
    }

    private static function param(string $value, string $name): string
    {
        return preg_match('/' . $name . '\*?="?([^";]+)"?/i', $value, $m) ? trim($m[1]) : '';
    }

    private static function walk(string $head, string $body, array &$parts): void
    {
        $h    = self::headers($head);
        $type = strtolower(strtok($h['content-type'] ?? 'text/plain', ';'));
        if (str_starts_with($type, 'multipart/')) {
            $boundary = self::param($h['content-type'], 'boundary');
            foreach ($boundary !== '' ? array_slice(explode('--' . $boundary, $body), 1) : [] as $section) {
                if (str_starts_with($section, '--')) { break; }
                [$sh, $sb] = self::split(ltrim($section, "\r\n"));
                self::walk($sh, $sb, $parts);
            }
            return;
        }
        $enc = strtolower($h['content-transfer-encoding'] ?? '');
        $data = $enc === 'base64' ? (string) base64_decode($body) : ($enc === 'quoted-printable' ? quoted_printable_decode($body) : $body);
        $file = self::param($h['content-disposition'] ?? '', 'filename') ?: self::param($h['content-type'] ?? '', 'name');
        if ($file !== '') {
            $parts['files'][] = $file;
            return;
        }
        $charset = self::param($h['content-type'] ?? '', 'charset') ?: 'UTF-8';
        $data = @mb_convert_encoding($data, 'UTF-8', $charset) ?: $data;
        if ($type === 'text/html' && $parts['html'] === '') {
            $parts['html'] = $data;
        } elseif ($type === 'text/plain' && $parts['text'] === '') {
            $parts['text'] = $data;
        }
    }
}
