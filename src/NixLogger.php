<?php

namespace NixLogger;

use NixLogger\Request\NixLoggerHttpRequest;
use Psr\Log\LogLevel;

class NixLogger
{
    public static $config;

    public static $client;

    public static $request;

    public static function init($config)
    {
        self::configure($config);
        self::updateRequest($config);
        self::$client = new Client(self::$config, self::$request);
    }

    public static function configure($config)
    {
        $loggerConfig = new Configuration($config['api_key'] ?? '');
        if (isset($config['slack_channel'])) {
            $loggerConfig->setSlackChannel($config['slack_channel']);
        }
        self::$config = $loggerConfig;
    }

    public static function updateRequest($config)
    {
        $request = new NixLoggerHttpRequest();

        self::$request = $request;
    }

    public static function critical($message, array $context = []): void
    {
        self::$client->log(LogLevel::CRITICAL, $message, $context);
    }

    public static function error($message, array $context = []): void
    {
        self::$client->log(LogLevel::ERROR, $message, $context);
    }

    public static function warning($message, array $context = []): void
    {
        self::$client->log(LogLevel::WARNING, $message, $context);
    }

    public static function info($message, array $context = []): void
    {
        self::$client->log(LogLevel::INFO, $message, $context);
    }

    public static function debug($message, array $context = []): void
    {
        self::$client->log(LogLevel::DEBUG, $message, $context);
    }
}
