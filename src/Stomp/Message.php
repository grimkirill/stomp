<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kirill
 * Date: 11.01.13
 * Time: 16:30
 * To change this template use File | Settings | File Templates.
 */

namespace Stomp;


class Message
{
    protected $_connection;
    protected $_frame;

    public function __construct(Connection $connection, Frame $frame)
    {
        $this->_connection = $connection;
        $this->_frame = $frame;
    }

    public function getBody()
    {
        return $this->_frame->getBody();
    }

    public function getHeaders()
    {
        return $this->_frame->getHeaders();
    }

    public function ack()
    {
        return $this->_response(Frame::COMMAND_ACK);
    }

    public function nack()
    {
        return $this->_response(Frame::COMMAND_NACK);
    }

    protected function _response($action)
    {
        $headers = $this->getHeaders();
        $frame = new Frame($action, '', array(
            'subscription' => $headers['subscription'],
            'message-id'   => $headers['message-id'],
        ));

        return $this->_connection->write($frame);
    }
}
