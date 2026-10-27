<?php
namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Sends HTML email through SMTP (when configured in admin settings) or PHP mail().
 */
final class Mailer
{
    private static string $lastError = '';

    public static function lastError(): string
    {
        return self::$lastError;
    }

    public static function send(string $to, string $subject, string $html): bool
    {
        self::$lastError = '';
        $fromAddress = Settings::get('mail_from_address');
        $fromName = Settings::get('mail_from_name', Settings::get('site_name'));
        $text = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>|<\/p>/i', "\n", $html)), ENT_QUOTES, 'UTF-8'));

        $boundary = 'b_' . bin2hex(random_bytes(12));
        $domain = substr(strrchr($fromAddress, '@') ?: '@localhost', 1);
        $headers = [
            'Date: ' . date('r'),
            'From: ' . self::encode($fromName) . " <{$fromAddress}>",
            'Reply-To: ' . Settings::get('support_email', $fromAddress),
            'Message-ID: <' . bin2hex(random_bytes(16)) . "@{$domain}>",
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
        ];
        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text))
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--{$boundary}--\r\n";

        try {
            if (Settings::get('mail_driver') === 'smtp' && Settings::get('smtp_host') !== '') {
                return self::smtp($fromAddress, $to, self::encode($subject), $headers, $body);
            }
            $ok = mail($to, self::encode($subject), $body, implode("\r\n", $headers), '-f' . $fromAddress);
            if (!$ok) {
                self::$lastError = 'PHP mail() returned false. Configure SMTP in Settings.';
            }
            return $ok;
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            error_log('Mailer: ' . $e->getMessage());
            return false;
        }
    }

    private static function encode(string $value): string
    {
        return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
    }

    private static function smtp(string $from, string $to, string $subject, array $headers, string $body): bool
    {
        $host = Settings::get('smtp_host');
        $port = (int) Settings::get('smtp_port', 587);
        $secure = Settings::get('smtp_secure', 'tls');
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true, 'peer_name' => $host]]);
        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new \RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, 15);

        $expect = static function (array $codes) use ($fp): string {
            $response = '';
            while (($line = fgets($fp, 515)) !== false) {
                $response .= $line;
                if (strlen($line) < 4 || $line[3] !== '-') {
                    break;
                }
            }
            if (!in_array((int) substr($response, 0, 3), $codes, true)) {
                throw new \RuntimeException('SMTP error: ' . trim($response));
            }
            return $response;
        };
        $cmd = static function (string $line, array $codes) use ($fp, $expect): string {
            fwrite($fp, $line . "\r\n");
            return $expect($codes);
        };

        $ehloHost = parse_url(Config::get('app.url'), PHP_URL_HOST) ?: 'localhost';
        $expect([220]);
        $cmd("EHLO {$ehloHost}", [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0))) {
                throw new \RuntimeException('SMTP STARTTLS negotiation failed');
            }
            $cmd("EHLO {$ehloHost}", [250]);
        }
        if (Settings::get('smtp_user') !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode(Settings::get('smtp_user')), [334]);
            $cmd(base64_encode(Settings::get('smtp_pass')), [235]);
        }
        $cmd("MAIL FROM:<{$from}>", [250]);
        $cmd("RCPT TO:<{$to}>", [250, 251]);
        $cmd('DATA', [354]);

        $message = implode("\r\n", array_merge($headers, ["To: <{$to}>", "Subject: {$subject}"])) . "\r\n\r\n" . $body;
        $message = preg_replace('/^\./m', '..', str_replace(["\r\n", "\n"], ["\n", "\r\n"], $message));
        $cmd($message . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($fp);
        return true;
    }
}
