<?php

namespace NixLogger\HttpClient;

use NixLogger\Configuration;

/**
 * @internal
 */
class HttpClient implements HttpClientInterface
{
    /**
     * @var \NixLogger\Configuration
     */
    protected $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    public function sendRequest(Request $request): Response
    {
        $requestEndpoint = $this->config->buildPostItemUri();
        $requestHeaders = $this->config->buildHeaders();
        $requestPayload = $request->getPayload();

        if (! \extension_loaded('curl')) {
            throw new \RuntimeException('The cURL PHP extension must be enabled to use the HttpClient.');
        }

        $curlHandle = curl_init();

        $supportGzip = false;
        if (\extension_loaded('zlib') && $supportGzip) {
            $requestPayload = gzcompress($requestPayload, -1, \ZLIB_ENCODING_GZIP);
            $requestHeaders[] = 'Content-Encoding: gzip';
        }

        $responseHeaders = [];

        curl_setopt($curlHandle, \CURLOPT_URL, $requestEndpoint);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $requestHeaders);

        // curl_setopt($curlHandle, \CURLOPT_USERAGENT, $this->sdkIdentifier . '/' . $this->sdkVersion);
        curl_setopt($curlHandle, \CURLOPT_TIMEOUT, 5);
        curl_setopt($curlHandle, \CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curlHandle, \CURLOPT_ENCODING, '');
        curl_setopt($curlHandle, \CURLOPT_POST, 1);
        curl_setopt($curlHandle, \CURLOPT_POSTFIELDS, $requestPayload);
        curl_setopt($curlHandle, \CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curlHandle, \CURLOPT_HEADERFUNCTION, $responseHeaderCallback);
        curl_setopt($curlHandle, \CURLOPT_HTTP_VERSION, \CURL_HTTP_VERSION_1_1);

        $httpSslVerifyPeer = null;
        if ($httpSslVerifyPeer !== null) {
            curl_setopt($curlHandle, \CURLOPT_SSL_VERIFYPEER, true);
        }

        $httpProxy = null;
        if ($httpProxy !== null) {
            curl_setopt($curlHandle, \CURLOPT_PROXY, $httpProxy);
            curl_setopt($curlHandle, \CURLOPT_HEADEROPT, \CURLHEADER_SEPARATE);
        }

        $httpProxyAuthentication = null;
        if ($httpProxyAuthentication !== null) {
            curl_setopt($curlHandle, \CURLOPT_PROXYUSERPWD, $httpProxyAuthentication);
        }

        $body = curl_exec($curlHandle);

        if ($body === false) {
            $errorCode = curl_errno($curlHandle);
            $error = curl_error($curlHandle);
            curl_close($curlHandle);

            $message = 'cURL Error ('.$errorCode.') '.$error;

            return new Response(0, [], $message);
        }

        $statusCode = curl_getinfo($curlHandle, \CURLINFO_HTTP_CODE);

        curl_close($curlHandle);

        return new Response($statusCode, $responseHeaders, '');
    }
}
