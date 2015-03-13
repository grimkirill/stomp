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
## RPC example

### server side example

```php
<?php
require_once __DIR__ . '/../tests/Bootstrap.php';

$client = new \Stomp\Client('tcp://127.0.0.1:61613', array('login' => 'admin', 'passcode' => 'password'));

$consumer = new \Stomp\Helper\Consumer($client, '/queue/testing');

$endTime = new \DateTime('+30 second');
$condition = function () use ($endTime) {
    return ((new DateTime()) < $endTime);
};

$consumer->run(function($data, $headers) {
    var_dump($data);
    print_r($headers);
    return date('c');
}, $condition);

```

### client side example

```php
<?php
require_once __DIR__ . '/../tests/Bootstrap.php';

$client = new \Stomp\Client('tcp://127.0.0.1:61613', array('login' => 'admin', 'passcode' => 'password'));

$rpc = new \Stomp\Helper\Rpc($client, '/queue/testing');

var_dump($rpc->call('Ping')); // string(25) "2015-03-13T10:22:27+00:00"

```