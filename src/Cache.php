<?php

/**
   * Redis Cache
   *
   *
   * @package    Redis Cache for framework
   * @author     Erdal ALTIN <erdal80@hotmail.com>
   * Copyright 2022

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 */

namespace damalis\RedisCache;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Predis\ClientInterface;

class Cache
{
    protected $client;
    protected $settings;
    protected $response;

    /**
     * Example middleware invokable class
     *
     * @param  ServerRequest  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $this->response = $handler->handle($request);

        //Only Cache GET Requests
        if($request->getMethod() !== "GET")	return $this->response;

        //Configure Cache Key
        $key = $request->getUri()->getPath();
        if (!empty($request->getUri()->getQuery())){
            $key .= '?' . $request->getUri()->getQuery();
        }

        //If cache exists return response
        if ($this->exists($key)) {

            $this->get($key);
            $body = $this->response->getBody();

            return $this->response;
        }

        //As long as response is good, save to cache
        if ($this->response->getStatusCode() == 200) {

            $cacheObject = [
                "body" => (string) $this->response->getBody(),
                "headers"=> $this->response->getHeaders()
            ];
            $this->set($cacheObject, $key);
        }

        return $this->response;
    }

    public function __construct(ClientInterface $client, array $settings = [])
    {
        $this->client = $client;
        $this->settings = $settings;
    }

    /**
       *
       * to cache object
       *
       * @param mixed $cacheObject
       * @param serialize $cacheString
       * {@inheritdoc}
       */
    public function set($cacheObject, $key)
    {
        $cacheString = serialize($cacheObject);
        $this->client->set($key, $cacheString);
        if (array_key_exists('timeout', $this->settings)){
            $this->expire($key, $this->settings['timeout']);
        }
        $this->response = $this->response->withHeader("X-Cache-Status", "No-Cache");

        return $this->response;
    }
	
    /**
       *
       * to call cached object
       *
       * @param unserialize $cacheObject
       * @param serialize $cacheString
       * {@inheritdoc}
       */
    public function get($key)
    {
        $cacheString  = $this->client->get($key);
        $cacheObject = unserialize($cacheString);
        foreach($cacheObject['headers'] as $header => $value){
            $this->response = $this->response->withHeader($header, $value);
        }

        $ttl = $this->client->ttl($key);
        $this->response = $this->response->withHeader("X-Cache-Status", "Cache");
        $this->response = $this->response->withHeader("Cache-TTL", $ttl);

        return $this->response;
    }

    /**
       * {@inheritdoc}
       */
    public function exists($key)
    {
        return $this->client->exists($key);
    }
	
    /**
       * {@inheritdoc}
       */
    public function expire($key)
    {
        return $this->client->expire($key, $this->settings['timeout']);
    }

    /**
       * {@inheritdoc}
       */
    public function del($key)
    {
        return $this->client->del($key);
    }

    /**
       * {@inheritdoc}
       */
    public function check()
    {
        if ( $this->client->ping() ) echo "PONG";
    }
	
    /**
       * {@inheritdoc}
       */
    protected function flush()
    {
        return $this->client->flushdb();
    }
}
	 