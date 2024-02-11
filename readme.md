# Welcome to SampQuery

Library to work with the native **sa-mp** Query. This is based on David Weston's script, despite being based on his general script, this library has many improvements and optimization, providing better use and greater scalability.

**Short code**

```php

private $socket;

private $server;

 
public  function  __construct($server, $port =  7777)

{

$this->server = [$server, $port];

$this->socket =  $this->initializeSocket();

  

if (!$this->socket) {

$this->server['online'] =  false;

return;

}

  

$this->sendInitialPacket();

$this->server['online'] =  $this->verifyServer();

}
```


## How to use it

**Install**
```
composer require jhonatanjavierdev/sampquery

```



**Use it**
```php

<?php

$serverIP = 'ip'; $serverPort = 7777;
$sampQuery = new  SampQuery($serverIP, $serverPort);


```
