<?php

namespace NixLogger;

use InvalidArgumentException;

class Configuration
{
    /**
     * @var string
     */
    private $endpoint = 'aHR0cHM6Ly9hcGkubml4LWxvZ2dlci5uam5kZXguY29tL2FwaS92MQ==';

    /**
     * @var string
     */
    private $sdkIdentifier = 'nix-logger.php';

    /**
     * @var string
     */
    private $version = '1.0.0';

    /**
     * @var string
     */
    private $loggerReportLevel = 'critical,error,warning';

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * The application data.
     *
     * @var string[]
     */
    protected $appData = [];

    /**
     * The device data.
     *
     * @var string[]
     */
    protected $deviceData = [];

    /**
     * The meta data.
     *
     * @var array[]
     */
    protected $metaData = [];

    protected $timeZone;

    protected $runningMode;

    protected $slackChannel;

    /**
     * Create a new config instance.
     *
     * @param  string  $apiKey your api key
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($apiKey)
    {
        if (!is_string($apiKey)) {
            throw new InvalidArgumentException('Invalid API key');
        }

        $this->apiKey = $apiKey;
        $this->mergeDeviceData(['runtimeVersions' => ['php' => phpversion()]]);
        $this->mergeDeviceData([
            'os' => [
                'name' => php_uname('s'),
                'version' => php_uname('r'),
                'build' => php_uname('v'),
                'kernel_version' => php_uname('a'),
                'machine_type' => php_uname('m'),
            ],
        ]);
        $this->timeZone = date_default_timezone_get();
    }

    /**
     * Get the API Key.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setEnvironment($environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function setLoggerReportLevel($loggerReportLevel): self
    {
        $this->loggerReportLevel = $loggerReportLevel;

        return $this;
    }

    public function getLoggerReportLevel()
    {
        return is_string($this->loggerReportLevel) ? explode(',', $this->loggerReportLevel) : $this->loggerReportLevel;
    }

    public function setSdkIdentifier($sdkIdentifier)
    {
        $this->sdkIdentifier = $sdkIdentifier;

        return $this;
    }

    public function getSdkIdentifier()
    {
        return $this->sdkIdentifier;
    }

    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setRootPath($rootPath): self
    {
        $this->rootPath = $rootPath;

        return $this;
    }

    public function getRootPath()
    {
        return $this->rootPath;
    }

    public function setTimeZone($timeZone): self
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    public function getTimeZone()
    {
        return $this->timeZone;
    }

    public function setRunningMode($runningMode): self
    {
        $this->runningMode = $runningMode;

        return $this;
    }

    public function getRunningMode()
    {
        return $this->runningMode;
    }

    /**
     * Get event notification endpoint.
     *
     * @return string
     */
    public function endpoint()
    {
        return base64_decode($this->endpoint);
    }

    /**
     * Get event notification endpoint.
     *
     * @return string
     */
    public function buildPostItemUri()
    {
        return $this->endpoint() . '/issues';
    }

    /**
     * Get event notification endpoint.
     *
     * @return string
     */
    public function buildHeaders()
    {
        return [
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . $this->apiKey,
        ];
    }

    /**
     * Set your app's semantic version, eg "1.2.3".
     *
     * @param  string|null  $appVersion the app's version
     * @return $this
     */
    public function setAppVersion($appVersion)
    {
        $this->appData['version'] = $appVersion;

        return $this;
    }

    /**
     * Set your release stage, eg "production" or "development".
     *
     * @param  string|null  $releaseStage the app's current release stage
     * @return $this
     */
    public function setReleaseStage($releaseStage)
    {
        $this->appData['releaseStage'] = $releaseStage;

        return $this;
    }

    /**
     * Set the type of application executing the code.
     *
     * This is usually used to represent if you are running plain PHP code
     * "php", via a framework, eg "laravel", or executing through delayed
     * worker code, eg "resque".
     *
     * @param  string|null  $type the current type
     * @return $this
     */
    public function setAppType($type)
    {
        $this->appData['type'] = $type;

        return $this;
    }

    /**
     * Get the application data.
     *
     * @return array
     */
    public function getAppData()
    {
        return array_merge([], array_filter($this->appData));
    }

    /**
     * Set the hostname.
     *
     * @param  string|null  $hostname the hostname
     * @return $this
     */
    public function setHostname($hostname)
    {
        $this->deviceData['hostname'] = $hostname;

        return $this;
    }

    /**
     * Adds new data fields to the device data collection.
     *
     * @param  array  $data an associative array containing the new data to be added
     * @return $this
     */
    public function mergeDeviceData($data)
    {
        $this->deviceData = array_merge_recursive($this->deviceData, $data);

        return $this;
    }

    /**
     * Get the device data.
     *
     * @return array
     */
    public function getDeviceData()
    {
        return array_merge($this->getHostname(), array_filter($this->deviceData));
    }

    /**
     * Get the hostname if possible.
     *
     * @return array
     */
    protected function getHostname()
    {
        $disabled = explode(',', ini_get('disable_functions'));

        if (function_exists('php_uname') && !in_array('php_uname', $disabled, true)) {
            return ['hostname' => php_uname('n')];
        }

        if (function_exists('gethostname') && !in_array('gethostname', $disabled, true)) {
            return ['hostname' => gethostname()];
        }

        return [];
    }

    /**
     * Set custom metadata to send to NixLogger.
     *
     * You can use this to add custom tabs of data to each error on your
     * NixLogger dashboard.
     *
     * @param  array[]  $metaData an array of arrays of custom data
     * @param  bool  $merge    should we merge the meta data
     * @return $this
     */
    public function setMetaData(array $metaData, $merge = true)
    {
        $this->metaData = $merge ? array_merge_recursive($this->metaData, $metaData) : $metaData;

        return $this;
    }

    /**
     * Get the custom metadata to send to NixLogger.
     *
     * @return array[]
     */
    public function getMetaData()
    {
        return $this->metaData;
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
