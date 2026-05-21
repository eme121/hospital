<?php
/**
 * Simple SMTP Class for PHP
 * Source: Adapted for Hope Haven Hospital
 */
class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $timeout;
    private $socket;
    private $log = [];

    public function __construct($host, $port, $user, $pass, $timeout = 30) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->timeout = $timeout;
    }

    private function getResponse() {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        $this->log[] = "S: " . $response;
        return $response;
    }

    private function sendCommand($command) {
        $this->log[] = "C: " . $command;
        fputs($this->socket, $command . "\r\n");
        return $this->getResponse();
    }

    public function send($to, $subject, $body, $from_email, $from_name) {
        $errno = $errstr = null;
        $this->socket = fsockopen(($this->port == 465 ? 'ssl://' : 'tcp://') . $this->host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) return false;

        $this->getResponse();
        $this->sendCommand("EHLO " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : gethostname()));

        if ($this->port == 587) {
            $this->sendCommand("STARTTLS");
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand("EHLO " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : gethostname()));
        }

        $this->sendCommand("AUTH LOGIN");
        $this->sendCommand(base64_encode($this->user));
        $this->sendCommand(base64_encode($this->pass));

        $this->sendCommand("MAIL FROM: <$from_email>");
        $this->sendCommand("RCPT TO: <$to>");
        $this->sendCommand("DATA");

        $headers = "From: \"$from_name\" <$from_email>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";

        $this->sendCommand($headers . $body . "\r\n.");
        $this->sendCommand("QUIT");

        fclose($this->socket);
        return true;
    }

    public function getLog() {
        return $this->log;
    }
}
?>