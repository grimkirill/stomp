<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kirill
 * Date: 10.01.13
 * Time: 16:33
 * To change this template use File | Settings | File Templates.
 */

namespace Stomp;

use Stomp\Exception\ConnectionException;
use Stomp\Exception\StompException;
use Stomp\Frame;


class Connection
{
    const READ_TIMEOUT_DEFAULT_USEC = 0; // 0 microseconds
    const READ_TIMEOUT_DEFAULT_SEC  = 5; // 5 seconds
    const CONNECTION_TIMEOUT       = 10; // 10 seconds

    protected $_timeout = 0;
    /**
     * Connection options
     * @var array
     */
    protected $_options;

    /**
     * tcp/tls/ssl socket
     * @var resource
     */
    protected $_socket = false;

    /**
     * Connection to Stomp server
     * @param $uri
     * @param array $options
     * @return bool
     * @throws Exception\ConnectionException
     */
    public function connect($uri, array $options = array())
    {
        $uri = (array)$uri;
        $uri = array_values($uri);

        if (!isset($options['timeout_sec'])) {
            $options['timeout_sec'] = self::READ_TIMEOUT_DEFAULT_SEC;
        }
        if (! isset($options['timeout_usec'])) {
            $options['timeout_usec'] = self::READ_TIMEOUT_DEFAULT_USEC;
        }

        if (! isset($options['timeout_connect'])) {
            $options['timeout_connect'] = self::CONNECTION_TIMEOUT;
        }

        $randomize = false;
        if (isset($options['randomize'])) {
            $randomize = $options['randomize'];
        }

        if ($randomize) {
            shuffle($uri);
        }

        $i = 0;
        $e = null;

        $opts = array(
            'ssl' => array(
                'ciphers' => 'RC4-SHA'
            )
        );

        $context = stream_context_create($opts);

        while (($this->_socket === false) && $i < count($uri)) {
            $host = $uri[$i];
            $i ++;
            $this->_socket = @stream_socket_client($host, $errno, $errstr, $options['timeout_connect'],  STREAM_CLIENT_CONNECT, $context);

            if ($this->_socket === false) {
                $last_e = $e;
                $e = new Exception\ConnectionException("Unable to connect to $host; error = $errstr ( errno = $errno )", $errno, $last_e);
            }
        }

        if ($this->_socket === false) {
            throw $e;
        }

        $this->_options = $options;

        $this->setTimeout();
        stream_set_blocking($this->_socket, 1);


        return true;
    }

    public function setTimeout($sec = 0) {
        if ($sec == 0) {
            $sec = $this->_options['timeout_sec'];
        }
        if ($this->_timeout != $sec) {
            $this->_timeout = $sec;
            stream_set_timeout($this->_socket, $sec, $this->_options['timeout_usec']);
        }
    }

    public function close()
    {

    }

    /**
     * Check connection
     * @return bool
     * @throws Exception\ConnectionException
     */
    public function isConnected()
    {
        if (!is_resource($this->_socket)) {
            throw new Exception\ConnectionException('Not connected to Stomp server');
        }
        return true;
    }

    /**
     * Tests the socket to see if there is data for us
     *
     * @return boolean
     */
    public function canRead()
    {
        $read   = array($this->_socket);
        $write  = null;
        $except = null;

        return stream_select($read, $write, $except, 0, 10000) == 1;
    }


    /**
     * Check if the connection has timed out
     * @throws Exception\ConnectionException
     */
    protected function _checkSocketReadTimeout()
    {
        if (!is_resource($this->_socket)) {
            return;
        }
        $info = stream_get_meta_data($this->_socket);
        $timed_out = $info['timed_out'];
        if ($timed_out) {
            $this->close();
            throw new Exception\ConnectionException(
                "Read timed out after {$this->_options['timeout_sec']} seconds"
            );
        }
    }

    /**
     * Write frame to connection
     * @param Frame $frame
     * @return bool
     * @throws Exception\ConnectionException
     */
    public function write(Frame $frame)
    {
        $data = $frame->getFrame();
        $this->isConnected();

        $bytes = fwrite($this->_socket, $data, strlen($data));
        if ($bytes === false || $bytes == 0) {
            throw new Exception\ConnectionException('No bytes written');
        }

        return true;
    }

    /**
     * @return bool|Frame
     * @throws Exception\StompException
     */
    public function readFrame($timeout = 0)
    {
        $frame = $this->read($timeout);

        if ($frame && $frame->getCommand() == Frame::COMMAND_ERROR) {
            $headers = $frame->getHeaders();
            if (isset($headers['message'])) {
                $message = $headers['message'];
            } else {
                $message = $frame->getCommand();
                foreach ($headers->getHeaders() AS $key => $value) {
                    $message .= ' ' . $key . ' ' . $value . ';';
                }
            }

            if ($frame->getBody()) {
                $message .= ' ' . $frame->getBody();
            }

            throw new StompException($message);
        }

        return $frame;
    }

    /**
     * Read frame
     * @return bool|Frame
     */
    public function read($timeout = 0)
    {
        $this->setTimeout($timeout);

        $this->isConnected();

        // as per protocol COMMAND is 1st \n terminated then headers also \n terminated
        // COMMAND and header block are seperated by a blank line.

        $headers_rows = array();
        // read command and headers
        while (($line = @fgets($this->_socket)) !== false) {
            $headers_rows[] = $line;
            if (rtrim($line) === '') break;
        }

        if (count($headers_rows) > 0) {
            $command = trim($headers_rows[0]);
            unset($headers_rows[0]);

            $headers = Frame::extractHeaders($headers_rows);

            $response = '';

            if (!isset($headers[Frame::CONTENT_LENGTH])) {
                // read till we hit the end of frame marker
                do {
                    $chunk = @fgets($this->_socket);
                    if ( $chunk === false || strlen($chunk) === 0) {
                        $this->_checkSocketReadTimeout();
                        break;
                    }
                    if (substr($chunk, -2) === Frame::END_OF_FRAME) {
                        // add the chunk above to the result before returning
                        $response .= $chunk;
                        break;
                    }
                    $response .= $chunk;
                } while (feof($this->_socket) === false);
            } else {
                // we have a content-length header set
                $contentLength = $headers[Frame::CONTENT_LENGTH] + 2;
                $current_pos = ftell($this->_socket);
                $chunk = '';

                for ($read_to = $current_pos + $contentLength;
                     $read_to > $current_pos;
                     $current_pos = ftell($this->_socket)
                ) {
                    $chunk = fread($this->_socket, $read_to - $current_pos);
                    if ($chunk === false || strlen($chunk) === 0) {
                        $this->_checkSocketReadTimeout();
                        break;
                    }
                    $response .= $chunk;
                    // Break if the connection ended prematurely
                    if (feof($this->_socket)) {
                        break;
                    }
                }
            }

            return new Frame($command, substr($response, 0, -2), $headers);

        } else {
            return false;
        }

    }

}
