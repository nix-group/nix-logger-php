## Changelog

### 1.4.0
- `NixLogger\Context\ExecutionContext`: what the process is running (command, job...), sent as `extraData` on every issue.
- `Client` accepts an `ExecutionContext` and an `HttpClientInterface` (both optional, backward compatible).
- `Log::error('message', ['exception' => $e])` keeps `message` in front of the exception message instead of dropping it.
- PHPUnit tests.
