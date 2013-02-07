Stomp for PHP
=============

Usage
-----

```php
<?php

$client = new \Stomp\Client(array('tcp://127.0.0.1:61613'), array(
        'login' => 'admin',
        'passcode' => 'password',
        'host' => '/',
        'queue_prefix' => '/queue/',
    ));
        
$client->send('queue_destination', 'hello message');    

$client->subscribe('queue_destination');
while ($message = $client->readMessage()) {
    echo $message->getBody();
    $message->ack();
}

```
