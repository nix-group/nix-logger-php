<?php

namespace NixLogger\Tests\Fakes;

use NixLogger\HttpClient\HttpClientInterface;
use NixLogger\HttpClient\Request;
use NixLogger\HttpClient\Response;

/**
 * Keeps the payloads instead of sending them to the NixLogger API.
 */
class RecordingHttpClient implements HttpClientInterface
{
    /**
     * @var array<int, array>
     */
    public $payloads = [];

    public function sendRequest(Request $request): Response
    {
        $this->payloads[] = json_decode($request->getPayload(), true);

        return new Response(200, [], '');
    }

    public function last(): ?array
    {
        return end($this->payloads) ?: null;
    }
}
