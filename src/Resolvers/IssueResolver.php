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
     * @param  LogRecord|Throwable|string|Stringable  $messageError   The exception or message
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
                'trace' => $context['exception']->getTrace(),
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
}
