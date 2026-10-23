<?php

namespace NixLogger\Context;

/**
 * Holds what the process is currently executing (console command, queued job,
 * scheduled task...) so every reported issue can carry it, the same way an
 * HTTP issue carries its request.
 *
 * Each type is a stack because executions nest: `queue:work` runs a job,
 * a command calls `Artisan::call()`, a job dispatches a sync job...
 */
class ExecutionContext
{
    /**
     * @var array<string, array<int, array>>
     */
    private $stacks = [];

    public function push(string $type, array $data): self
    {
        $this->stacks[$type][] = $data;

        return $this;
    }

    /**
     * Remove the innermost entry of a type. When $name is given, remove the
     * innermost entry with that name (and everything nested above it, which
     * was left behind by executions that threw before finishing).
     */
    public function pop(string $type, ?string $name = null): self
    {
        if (empty($this->stacks[$type])) {
            return $this;
        }

        if ($name === null) {
            array_pop($this->stacks[$type]);
        } else {
            for ($i = count($this->stacks[$type]) - 1; $i >= 0; $i--) {
                if (($this->stacks[$type][$i]['name'] ?? null) === $name) {
                    array_splice($this->stacks[$type], $i);
                    break;
                }
            }
        }

        if (empty($this->stacks[$type])) {
            unset($this->stacks[$type]);
        }

        return $this;
    }

    public function forget(?string $type = null): self
    {
        if ($type === null) {
            $this->stacks = [];
        } else {
            unset($this->stacks[$type]);
        }

        return $this;
    }

    public function current(string $type): ?array
    {
        if (empty($this->stacks[$type])) {
            return null;
        }

        return $this->stacks[$type][count($this->stacks[$type]) - 1];
    }

    public function isEmpty(): bool
    {
        return empty($this->stacks);
    }

    /**
     * The innermost entry of each type, plus `{type}Stack` (outer → inner names)
     * when executions of that type are nested.
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->stacks as $type => $stack) {
            $result[$type] = $stack[count($stack) - 1];
            if (count($stack) > 1) {
                $result[$type.'Stack'] = array_map(function ($entry) {
                    return $entry['name'] ?? null;
                }, $stack);
            }
        }

        return $result;
    }
}
