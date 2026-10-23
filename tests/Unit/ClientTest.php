<?php

namespace NixLogger\Tests\Unit;

use NixLogger\Client;
use NixLogger\Configuration;
use NixLogger\Context\ExecutionContext;
use NixLogger\Request\NixLoggerHttpRequest;
use NixLogger\Tests\Fakes\RecordingHttpClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ClientTest extends TestCase
{
    private RecordingHttpClient $http;

    private ExecutionContext $context;

    private Client $client;

    protected function setUp(): void
    {
        $config = (new Configuration('test-key'))->setRunningMode('cli');
        $this->http = new RecordingHttpClient();
        $this->context = new ExecutionContext();
        $this->client = new Client($config, new NixLoggerHttpRequest(), $this->context, $this->http);
    }

    public function test_execution_context_is_sent_as_extra_data(): void
    {
        $this->context
            ->push('command', ['name' => 'queue:work'])
            ->push('job', ['name' => 'App\Jobs\SyncStock', 'attempts' => 2]);

        $this->client->error('Stock sync failed', ['exception' => new RuntimeException('boom')]);

        $payload = $this->http->last();
        $this->assertSame('cli', $payload['runningMode']);
        $this->assertSame([
            'command' => ['name' => 'queue:work'],
            'job' => ['name' => 'App\Jobs\SyncStock', 'attempts' => 2],
        ], $payload['extraData']);
        $this->assertNotEmpty($payload['data']['trace']);
        $this->assertNull($payload['request']);
    }

    public function test_log_message_is_kept_next_to_the_exception_message(): void
    {
        $this->client->error('IF_TOOL_RUN_THREW: pdb_sku_jan_issue', ['exception' => new RuntimeException('no usable value')]);
        $this->client->error('same message', ['exception' => new RuntimeException('same message')]);

        $this->assertStringStartsWith(
            'IF_TOOL_RUN_THREW: pdb_sku_jan_issue | Exception: no usable value in ',
            $this->http->payloads[0]['data']['message']
        );
        $this->assertStringStartsWith('Exception: same message in ', $this->http->payloads[1]['data']['message']);
    }

    public function test_extra_data_is_null_without_execution_context(): void
    {
        $this->client->error('Plain error');

        $this->assertNull($this->http->last()['extraData']);
    }

    public function test_the_context_is_read_at_report_time(): void
    {
        $this->client->getExecutionContext()->push('job', ['name' => 'App\Jobs\First']);
        $this->client->error('first');
        $this->client->getExecutionContext()->forget('job');
        $this->client->error('second');

        $this->assertSame('App\Jobs\First', $this->http->payloads[0]['extraData']['job']['name']);
        $this->assertNull($this->http->payloads[1]['extraData']);
    }

    public function test_client_works_without_explicit_context(): void
    {
        $http = new RecordingHttpClient();
        $client = new Client(new Configuration('test-key'), new NixLoggerHttpRequest(), null, $http);

        $client->error('legacy construction');

        $this->assertCount(1, $http->payloads);
        $this->assertInstanceOf(ExecutionContext::class, $client->getExecutionContext());
    }
}
