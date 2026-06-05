<?php
class Mailer {
    private const HOST     = 'smtp.gmail.com';
    private const PORT     = 587;
    private const USERNAME = 'ancoramonitoreo@gmail.com';
    private const PASSWORD = 'vvlvdglbfokjwtkj';

    public function send(string $to, string $subject, string $htmlBody): bool {
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]]);

        $s = @stream_socket_client(
            'tcp://' . self::HOST . ':' . self::PORT,
            $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx
        );
        if (!$s) {
            error_log("Mailer: no se pudo conectar — $errstr ($errno)");
            return false;
        }
        stream_set_timeout($s, 15);

        $this->recv($s);
        $this->cmd($s, 'EHLO localhost');
        $this->cmd($s, 'STARTTLS');
        stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $this->cmd($s, 'EHLO localhost');
        $this->cmd($s, 'AUTH LOGIN');
        $this->cmd($s, base64_encode(self::USERNAME));
        $auth = $this->cmd($s, base64_encode(self::PASSWORD));

        if (strpos($auth, '235') !== 0) {
            error_log("Mailer: autenticación fallida — $auth");
            fclose($s);
            return false;
        }

        $this->cmd($s, 'MAIL FROM:<' . self::USERNAME . '>');
        $this->cmd($s, 'RCPT TO:<' . $to . '>');
        $this->cmd($s, 'DATA');

        $msg  = 'From: Ancora Monitoreo <' . self::USERNAME . ">\r\n";
        $msg .= "To: {$to}\r\n";
        $msg .= "Subject: {$subject}\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $msg .= $htmlBody . "\r\n.";

        fwrite($s, $msg . "\r\n");
        $this->recv($s);

        fwrite($s, "QUIT\r\n");
        fclose($s);
        return true;
    }

    private function cmd($s, string $data): string {
        fwrite($s, $data . "\r\n");
        return $this->recv($s);
    }

    private function recv($s): string {
        $out = '';
        while ($line = fgets($s, 515)) {
            $out .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $out;
    }
}
