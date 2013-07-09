Stomp for PHP
=============

Usage
-----

```php
<?php
/**
 * Create connection
 */
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
