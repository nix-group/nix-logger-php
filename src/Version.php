<?php

namespace NixLogger;

final class Version
{
    public const SDK_IDENTIFIER = 'nix-logger.php';

    public const SDK_VERSION = '1.0.0';

    public static function getSdkIdentifier()
    {
        return self::SDK_IDENTIFIER;
    }

    public static function getVersion()
    {
        return self::SDK_VERSION;
    }
}
