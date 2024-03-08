<?php

namespace NixLogger\Request;

class NixLoggerHttpRequest
{
    private $url;

    private $httpMethod;

    private $params;

    private $body;

    private $clientIp;

    private $userAgent;

    private $headers;

    private $session;

    private $cookies;

    public function setUrl($url): self
    {
        $this->url = $url;
        return $this;
    }

    public function setHttpMethod($httpMethod): self
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    public function setParams($params): self
    {
        $this->params = $params;
        return $this;
    }

    public function setBody($body): self
    {
        $this->body = $body;
        return $this;
    }

    public function setClientIp($clientIp): self
    {
        $this->clientIp = $clientIp;
        return $this;
    }


    public function setUserAgent($userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function setHeaders($headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function setSessions($session): self
    {
        $this->session = $session;
        return $this;
    }

    public function setCookies($cookies): self
    {
        $this->cookies = $cookies;
        return $this;
    }


    public function getUrl()
    {
        return $this->url;
    }

    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getClientIp()
    {
        return $this->clientIp;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getCookies()
    {
        return $this->cookies;
    }
}
