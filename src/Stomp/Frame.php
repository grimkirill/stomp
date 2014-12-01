<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kirill
 * Date: 10.01.13
 * Time: 17:52
 * To change this template use File | Settings | File Templates.
 */

namespace Stomp;

class Frame
{
    const END_OF_FRAME   = "\x00\n";
    const EOL            = "\n";

    const CONTENT_LENGTH = 'content-length';

    const COMMAND_CONNECT     = 'CONNECT';
    const COMMAND_CONNECTED   = 'CONNECTED';
    const COMMAND_ERROR       = 'ERROR';
    const COMMAND_SEND        = 'SEND';
    const COMMAND_SUBSCRIBE   = 'SUBSCRIBE';
    const COMMAND_UNSUBSCRIBE = 'UNSUBSCRIBE';
    const COMMAND_ACK         = 'ACK';
    const COMMAND_NACK        = 'NACK';
    const COMMAND_BEGIN       = 'BEGIN';
    const COMMAND_COMMIT      = 'COMMIT';
    const COMMAND_ABORT       = 'ABORT';
    const COMMAND_DISCONNECT  = 'DISCONNECT';
    const COMMAND_MESSAGE     = 'MESSAGE';
    const COMMAND_RECEIPT     = 'RECEIPT';

    /**
     * Headers for the frame
     * @var array
     */
    protected $_headers = array();

    /**
     * The command for the frame
     * @var string
     */
    protected $_command = null;

    /**
     * The body of the frame
     * @var string
     */
    protected $_body = null;

    public function setCommand($command)
    {
        $this->_command = strval($command);
    }

    public function getCommand()
    {
        return $this->_command;
    }

    public function setBody($body)
    {
        $this->_body = strval($body);
    }

    public function getBody()
    {
        return $this->_body;
    }

    public function setHeaders($headers)
    {
        $this->_headers = (array) $headers;
    }

    public function addHeader($header, $value)
    {
        $this->_headers[$header] = $value;
    }

    public function getHeaders()
    {
        return $this->_headers;
    }

    public function __construct($command, $body, $headers)
    {
        $this->setCommand($command);
        $this->setHeaders($headers);
        $this->setBody($body);
    }

    public function getFrame()
    {
        // Command
        $frame = $this->getCommand().self::EOL;

        // Headers
        foreach ($this->getHeaders() as $key => $value) {
            $frame .= $key.':'.$value.self::EOL;
        }

        // Seperator blank line required by protocol
        $frame .= self::EOL;

        // add the body if any
        if ($this->getBody()) {
            $frame .= $this->getBody();
        }

        $frame .= self::END_OF_FRAME;

        return $frame;
    }

    public static function extractHeaders($headers_raw)
    {
        if (is_array($headers_raw) && count($headers_raw)) {
            $headers = array();
            foreach ($headers_raw as $header_raw) {
                if (preg_match("|([\w-]+):\s*(.+)|", $header_raw, $m)) {
                    if (isset($headers[$m[1]])) {
                        if (is_array($headers[$m[1]])) {
                            $headers[$m[1]][] = $m[2];
                        } else {
                            $headers[$m[1]] = array($headers[$m[1]], $m[2]);
                        }
                    } else {
                        $headers[$m[1]] = $m[2];
                    }
                }
            }

            return $headers;
        } else {
            return array();
        }
    }
}
