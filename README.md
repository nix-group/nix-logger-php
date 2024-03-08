## Getting Started

### Installation
```bash
composer require nix-logger/nix-logger
```

### Setup
```php
use NixLogger\NixLogger;

$config = [
  // Required configuration
  'api_key' => 'your_api_key',
  // Optional configuration
  'environment' => '<your-environment>',
  'slack_channel' => '<your-slack-channel>',
];
NixLogger::init($config)
```

### Usage
```php
NixLogger::critical('some message');

NixLogger::error('some message');

NixLogger::warning('some message');

NixLogger::info('some message');

NixLogger::debug('some message');
```
