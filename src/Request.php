<?php
/**
 * This file is part of the Purl package.
 * Copyright (C) 2016 pengzhile <pengzhile@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Purl;

class Request
{
    const USER_AGENT = 'Purl/1.0';

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    /**
     * @var string
     */
    protected $content;

    /**
     * @var bool
     */
    protected $http;
    /**
     * @var bool
     */
    protected $https;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var array
     */
    protected $posts = array();

    /**
     * @var array
     */
    protected $headers = array(
        'Cache-Control' => 'no-cache',
        'Pragma' => 'no-cache',
        'Accept' => '*/*',
        'User-Agent' => self::USER_AGENT,
    );

    /**
     * Request constructor.
     * @param string $method
     * @param UrlInfo $urlInfo
     * @param array $headers
     * @param array $posts
     */
    public function __construct($method, UrlInfo $urlInfo, array $headers = null, array $posts = null)
    {
        $this->method = $method;

        $this->scheme = $urlInfo->getScheme();
        $this->http = 'http' === $this->scheme;
        $this->https = 'https' === $this->scheme;

        $this->host = $urlInfo->getHost();
        $this->port = $urlInfo->getPort();
        $this->uri = $urlInfo->getPath() . $urlInfo->getQuery();

        $posts && $this->posts = $posts;
        if ($headers) {
            $this->headers = $headers + $this->headers;
        }
    }

    /**
     * @return bool
     */
    public function isHttp()
    {
        return $this->http;
    }

    /**
     * @return bool
     */
    public function isHttps()
    {
        return $this->https;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return array
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPost($key, $default = null)
    {
        return isset($this->posts[$key]) ? $this->posts[$key] : $default;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getHeader($key, $default = null)
    {
        return isset($this->headers[$key]) ? $this->headers[$key] : $default;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        if (null === $this->content) {
            $this->content = $this->build();
        }

        return $this->content;
    }

    protected function build()
    {
        $port = $this->http && 80 === $this->port || 443 === $this->port ? '' : ':' . $this->port;
        $header = 'Host: ' . $this->host . $port . "\r\nConnection: keep-alive\r\n";

        if (self::METHOD_POST === $this->method) {
            $header .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
        }

        if ($this->posts) {
            $data = http_build_query($this->posts);
            $header .= 'Content-Length: ' . strlen($data) . "\r\n";
        } else {
            $data = '';
        }

        $header .= self::buildHeader($this->headers);

        return "{$this->method} {$this->uri} HTTP/1.1\r\n{$header}\r\n{$data}";
    }

    protected static function buildHeader(array $headers)
    {
        $header = '';
        $fixedHeaders = array('host', 'connection', 'content-length', 'content-type');
        foreach ($headers as $k => $v) {
            if (in_array(strtolower($k), $fixedHeaders)) {
                continue;
            }

            if (null === $v) {
                continue;
            }

            $header .= $k . ': ' . $v . "\r\n";
        }

        return $header;
    }
}
