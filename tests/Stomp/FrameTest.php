<?php

/*
 * This file is part of the Stomp package.
 *
 * (c) Kirill Skatov <kirill@noadmin.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StompTest;


class FrameTest extends \PHPUnit_Framework_TestCase
{
    public function testParseHeaders()
    {
        $this->assertEquals(
            array('version' => '1.0'),
            \Stomp\Frame::extractHeaders(array('version:1.0'))
        );

        $this->assertEquals(
            array('server' => 'apache-apollo/1.5'),
            \Stomp\Frame::extractHeaders(array('server:apache-apollo/1.5'))
        );

        $this->assertEquals(
            array('destination' => '/queue/a', 'content-type' => 'text/plain'),
            \Stomp\Frame::extractHeaders(array('destination:/queue/a', 'content-type:text/plain'))
        );

    }
}
