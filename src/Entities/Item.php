<?php

namespace NixLogger\Entities;

class Item
{
    /**
     * @var float|null The date and time of when this event was generated
     */
    private $timestamp;

    /**
     * @var string The level
     */
    private $level;

    /**
     * @var string|null The error message
     */
    private $message;

    /**
     * @var string|null The environment where this event generated (e.g. production)
     */
    private $environment;

    private $context;

    private $rootPath;

    private $timeZone;

    private $runningMode;

    private $request;

    private $deviceData;

    private $data;

    private $extraData;

    private $sdk;
    
    private $slackChannel;

    public function __construct()
    {
        $this->timestamp = microtime(true);
    }

    /**
     * Gets the timestamp of when this event was generated.
     */
    public function getTimestamp(): ?float
    {
        return $this->timestamp;
    }

    /**
     * Sets the timestamp of when the Event was created.
     */
    public function setTimestamp(?float $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function setEnvironment($environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;

        return $this;
    }

    public function getRootPath()
    {
        return $this->rootPath;
    }

    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    public function getTimeZone()
    {
        return $this->timeZone;
    }

    public function setRunningMode($runningMode)
    {
        $this->runningMode = $runningMode;

        return $this;
    }

    public function getRunningMode()
    {
        return $this->runningMode;
    }

    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setDeviceData($deviceData)
    {
        $this->deviceData = $deviceData;

        return $this;
    }

    public function getDeviceData()
    {
        return $this->deviceData;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setExtraData($extraData)
    {
        $this->extraData = $extraData;

        return $this;
    }

    public function getExtraData()
    {
        return $this->extraData;
    }

    public function setSdk($sdk)
    {
        $this->sdk = $sdk;

        return $this;
    }

    public function getSdk()
    {
        return $this->sdk;
    }

    /**
     * Set Slack channel
     *
     * @param  string  $slackChannel slackChannel
     * 
     * @return $this
     */
    public function setSlackChannel(string $slackChannel)
    {
        $this->slackChannel = $slackChannel;

        return $this;
    }

    /**
     * Get the custom metadata to send to NixLogger.
     *
     * @return string
     */
    public function getSlackChannel()
    {
        return $this->slackChannel;
    }
}
