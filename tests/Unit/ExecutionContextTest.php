<?php

namespace NixLogger\Tests\Unit;

use NixLogger\Context\ExecutionContext;
use PHPUnit\Framework\TestCase;

class ExecutionContextTest extends TestCase
{
    public function test_it_is_empty_by_default(): void
    {
        $context = new ExecutionContext();

        $this->assertTrue($context->isEmpty());
        $this->assertSame([], $context->toArray());
        $this->assertNull($context->current('job'));
    }

    public function test_it_exposes_the_innermost_entry_of_each_type(): void
    {
        $context = (new ExecutionContext())
            ->push('command', ['name' => 'queue:work'])
            ->push('job', ['name' => 'App\Jobs\SyncStock', 'attempts' => 1]);

        $this->assertSame([
            'command' => ['name' => 'queue:work'],
            'job' => ['name' => 'App\Jobs\SyncStock', 'attempts' => 1],
        ], $context->toArray());
    }

    public function test_nested_entries_expose_their_stack(): void
    {
        $context = (new ExecutionContext())
            ->push('command', ['name' => 'batch:daily'])
            ->push('command', ['name' => 'batch:sku-issue']);

        $this->assertSame([
            'command' => ['name' => 'batch:sku-issue'],
            'commandStack' => ['batch:daily', 'batch:sku-issue'],
        ], $context->toArray());

        $context->pop('command');

        $this->assertSame(['command' => ['name' => 'batch:daily']], $context->toArray());
    }

    public function test_pop_by_name_drops_entries_left_behind_above_it(): void
    {
        // batch:inner threw, so it never popped itself; batch:outer caught it and finished
        $context = (new ExecutionContext())
            ->push('command', ['name' => 'queue:work'])
            ->push('command', ['name' => 'batch:outer'])
            ->push('command', ['name' => 'batch:inner']);

        $context->pop('command', 'batch:outer');

        $this->assertSame(['command' => ['name' => 'queue:work']], $context->toArray());
    }

    public function test_pop_by_unknown_name_keeps_the_stack(): void
    {
        $context = (new ExecutionContext())->push('command', ['name' => 'batch:daily']);

        $context->pop('command', 'batch:other')->pop('job');

        $this->assertSame(['command' => ['name' => 'batch:daily']], $context->toArray());
    }

    public function test_forget_one_type_or_everything(): void
    {
        $context = (new ExecutionContext())
            ->push('command', ['name' => 'queue:work'])
            ->push('job', ['name' => 'App\Jobs\SyncStock']);

        $context->forget('job');
        $this->assertSame(['command' => ['name' => 'queue:work']], $context->toArray());

        $context->forget();
        $this->assertTrue($context->isEmpty());
    }
}
