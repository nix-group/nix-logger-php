<?php

namespace NixLogger;

use Monolog\LogRecord;
use NixLogger\HttpClient\HttpClient;
use NixLogger\HttpClient\Request;
use NixLogger\Request\NixLoggerHttpRequest;
use NixLogger\Resolvers\IssueResolver;
use NixLogger\Serializer\PayloadSerializer;
use NixLogger\LogLevel;

class Client
{
    /**
     * The config instance.
     *
     * @var Configuration
     */
    protected $config;

    /**
     * The request instance.
     *
     * @var NixLoggerHttpRequest
     */
    protected $request;

    /**
     * The config instance.
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Create New NixLogger Client
     */
    public function __construct(
        Configuration $config,
        NixLoggerHttpRequest $request,
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->httpClient = new HttpClient($this->config);
    }

    /**
     * @var LogRecord|Throwable|string|Stringable,
     */
    public function report(
        string $level,
        $message,
        array $context = [],
    ) {
        if (! $this->config->getApiKey()) {
            return;
        }
        if (! $this->shouldReport($level)) {
            return;
        }

        $issueResolver = new IssueResolver($this->config, $this->request);
        $payload = $issueResolver->getPayload($level, $message, $context);
        $request = new Request();
        $request->setPayload(PayloadSerializer::serialize($payload));
        $this->httpClient->sendRequest($request);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->report($level, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * \Monolog\LogRecord | array: $record
     * Laravel 9: array: $record
     * Laravel >=10: LogRecord: $record
     */
    // public function reportUncaught(LogRecord $record): void
    public function reportUncaught($record): void
    {
        if (is_array($record)) {
            $logLevel = strtolower($record['level_name']);
        } else {
            $logLevel = strtolower($record->level->name);
        }
        try {
            $this->report($logLevel, $record, $record['context'] ?? []);
        } catch (\Exception $e) {
            //
        }
    }

    /**
     * Returns the client config
     *
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns true if the NixLogger is enabled.
     */
    public function enabled(): bool
    {
        return (bool) ($this->getConfig()->getApiKey());
    }

    /**
     * Check if should report to the NixLogger
     */
    public function shouldReport(string $level): bool
    {
        return in_array($level, $this->config->getLoggerReportLevel());
    }
}
