<?php

namespace NixLogger\Resolvers;

use Exception;
use Throwable;
use Monolog\LogRecord;
use NixLogger\Configuration;
use NixLogger\Context\ExecutionContext;
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

    /**
     * The execution context instance.
     *
     * @var ExecutionContext|null
     */
    protected $executionContext;

    public function __construct(
        Configuration $config,
        NixLoggerHttpRequest $request,
        ?ExecutionContext $executionContext = null,
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->executionContext = $executionContext;
    }

    /**
     * @param  string  $level          Log Level
     * @param  LogRecord|Throwable|string|Stringable  $messageError   The exception or message
     */
    public function getPayload($level, $messageError, $context)
    {
        $item = new Item();
        if (
            isset($context['exception']) 
            // Throwable includes Exception and Error, Throwable: >= 7
            && $context['exception'] instanceof Throwable
        ) {
            $exception = $context['exception'];
            $message = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            $message = "Exception: {$message} in {$file}:{$line}";
            // Log::error('Sync failed', ['exception' => $e]): keep 'Sync failed' as well
            $logMessage = $this->getLogMessage($messageError);
            if ($logMessage !== '' && $logMessage !== $exception->getMessage()) {
                $message = "{$logMessage} | {$message}";
            }
            $item->setData([
                'message' => $message,
                'trace' => $this->buildTraces($exception->getTrace()),
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
                        'message' => $this->parseMessageInCaughtException($messageError),
                        'trace' => $this->parseTraceInCaughtException($messageError),
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
        if ($this->executionContext && ! $this->executionContext->isEmpty()) {
            $item->setExtraData($this->executionContext->toArray());
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
     * @param  LogRecord|array|string|mixed  $messageError
     */
    private function getLogMessage($messageError): string
    {
        if ($messageError instanceof LogRecord) {
            return (string) $messageError->message;
        }
        if (is_array($messageError) && isset($messageError['message']) && is_string($messageError['message'])) {
            return $messageError['message'];
        }

        return is_string($messageError) ? $messageError : '';
    }

    private function parseMessageInCaughtException(LogRecord $messageError)
    {
        $items = explode("\n", $messageError->message);
        if (count($items)) {
            return $items[0];
        }

        return $messageError->message;
    }

    private function parseTraceInCaughtException($messageError)
    {
        $items = explode("\n", $messageError->message);
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
        if (isset($trace['args']) && is_array($trace['args']) && count($trace['args'])) {
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
