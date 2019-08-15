<?php

namespace core\Utils;

/**
 * aria2 doc: https://aria2.github.io/manual/en/html/aria2c.html#methods
 * Class Aria2Client
 * @package core\Utils
 */
class Aria2Client
{
    protected $ch;

    public function __construct(string $host)
    {
        $this->ch = curl_init($host);
    }

    public function __call($name, $arguments)
    {
        $jsonreq = $this->params($name, $arguments);

        $this->setCurlopt($jsonreq);

        return $this->exec();
    }

    public function setCurlopt(string $jsonreq)
    {
        curl_setopt_array($this->ch, [
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => false,
            CURLOPT_POSTFIELDS => $jsonreq
        ]);
    }

    /**
     * @return mixed
     */
    public function exec()
    {
        $result = curl_exec($this->ch);

        if(!$result){
            trigger_error(curl_error($this->ch));
        }

        return json_decode($result, true);
    }

    public function params($method, $args):string
    {
        $params = [
            'jsonrpc' => '2.0',
            'id' => 'id',
            'method' => 'aria2.' . $method,
            'params' => $args,
        ];

        return json_encode($params);
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

}