<?php

namespace NixLogger\Resolvers;

use Exception;
use Monolog\LogRecord;
use NixLogger\Configuration;
use NixLogger\Entities\Item;
use NixLogger\Request\NixLoggerHttpRequest;
use NixLogger\Utils\Helper;

class IssueResolver
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

    public function __construct(Configuration $config, NixLoggerHttpRequest $request)
    {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * @param  string  $level          Log Level
     * @param  LogRecord|array|Throwable|string|Stringable  $messageError   The exception or message
     */
    public function getPayload($level, $messageError, $context)
    {
        $item = new Item();
        if (isset($context['exception']) && $context['exception'] instanceof Exception) {
            $message = $context['exception']->getMessage();
            $file = $context['exception']->getFile();
            $line = $context['exception']->getLine();
            $message = "Exception: {$message} in {$file}:{$line}";
            $item->setData([
                'message' => $message,
                'trace' => $this->buildTraces($context['exception']->getTrace()),
                'type' => 'LogRecord@UnCaughtException',
            ]);
        } else {
            if (gettype($messageError) === 'string') {
                $item->setData([
                    'message' => $messageError,
                    'type' => gettype($messageError),
                ]);
            } else {
                if ($messageError instanceof LogRecord) {
                    $item->setData([
                        'message' => $this->parseMessageInCaughtException($messageError->message),
                        'trace' => $this->parseTraceInCaughtException($messageError->message),
                        'type' => 'LogRecord@CaughtException',
                    ]);
                } else {
                    if (is_array($messageError) && isset($messageError['message'])) {
                        $item->setData([
                            'message' => $this->parseMessageInCaughtException($messageError['message']),
                            'trace' => $this->parseTraceInCaughtException($messageError['message']),
                            'type' => 'LogRecord@CaughtException',
                        ]);
                    } else {
                        $item->setData([
                            'message' => Helper::encode($messageError),
                            'type' => gettype($messageError),
                        ]);
                    }
                }
            }
        }

        $item->setLevel($level);
        $item->setContext($context);
        $item->setRootPath($this->config->getRootPath());
        $item->setEnvironment($this->config->getEnvironment());
        $item->setTimeZone($this->config->getTimeZone());
        $item->setRunningMode($this->config->getRunningMode());
        if ($this->config->getRunningMode() === 'web') {
            $item->setRequest(
                [
                    'url' => $this->request->getUrl(),
                    'httpMethod' => $this->request->getHttpMethod(),
                    'params' => $this->request->getParams(),
                    'body' => $this->request->getBody(),
                    'clientIp' => $this->request->getClientIp(),
                    'userAgent' => $this->request->getUserAgent(),
                    'headers' => $this->request->getHeaders(),
                    'session' => $this->request->getSession(),
                    'cookies' => $this->request->getCookies(),
                ],
            );
        }
        $item->setDeviceData($this->config->getDeviceData());
        $item->setSdk([
            'identifier' => $this->config->getSdkIdentifier(),
            'version' => $this->config->getVersion(),
        ]);
        if ($this->config->getSlackChannel()) {
            $item->setSlackChannel($this->config->getSlackChannel());
        }

        return $item;
    }

    /**
     * string $message
     */
    private function parseMessageInCaughtException($message)
    {
        $items = explode("\n", $message);
        if (count($items)) {
            return $items[0];
        }
        return $message;
    }

    /**
     * string $message
     */
    private function parseTraceInCaughtException($message)
    {
        $items = explode("\n", $message);
        if (count($items) <= 2) {
            return [];
        }

        return array_slice($items, 2);
    }

    private function buildTraces($traces)
    {
        $frameTraces = [];
        foreach ($traces as $key => $trace) {
            $frameTraces[] = $this->buildTraceItem($trace);
        }
        return $frameTraces;
    }

    private function buildTraceItem($trace)
    {
        if ($trace['args'] && is_array($trace['args']) && count($trace['args'])) {
            $trace['args'] = $this->buildTraceArgs($trace['args']);
        }
        return $trace;
    }

    private function buildTraceArgs($args)
    {
        $result = [];
        foreach ($args as $key => $arg) {
            $result[] = $this->buildTraceArgsItem($arg);
        }
        return $result;
    }

    private function buildTraceArgsItem($argsItem)
    {
        if (is_string($argsItem)) return $argsItem;
        if (is_numeric($argsItem)) return $argsItem;
        if (is_null($argsItem)) return $argsItem;
        if (is_bool($argsItem)) return $argsItem;

        if (is_object($argsItem)) {
            return get_class($argsItem);
        }

        return 'undefined';
    }
}
