<?php

declare(strict_types=1);

namespace PivotPHP\Core\Core;

/**
 * Marker interface for the application instance passed to extensions.
 *
 * Components like ExtensionManager only need to hold and pass along a
 * reference to "the application" — they never call a specific method on
 * it directly, extensions do. Typing against this interface instead of
 * the concrete Application class avoids a hard dependency on Application's
 * full surface (1000+ lines) for code that doesn't need it.
 */
interface ApplicationInterface
{
}
