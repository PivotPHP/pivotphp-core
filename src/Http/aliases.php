<?php

/**
 * Class Aliases for Backward Compatibility
 *
 * This file provides class aliases to maintain backward compatibility
 * after the Adapter Pattern refactoring (v2.1.0).
 *
 * The old hybrid Request/Response classes have been replaced with:
 * - ExpressRequest: Express.js-style facade over PSR-7 ServerRequest
 * - ExpressResponse: Express.js-style facade over PSR-7 Response
 *
 * These aliases allow existing code to continue working without modification.
 *
 * Migration Path:
 * - v2.1.0: Aliases introduced, old names work seamlessly
 * - v2.2.0: Deprecation warnings added (trigger_error E_USER_DEPRECATED)
 * - v3.0.0: Aliases removed, migration required
 *
 * @package PivotPHP\Core\Http
 * @since 2.1.0
 */

// Request class alias
if (!class_exists(\PivotPHP\Core\Http\Request::class, false)) {
    class_alias(
        \PivotPHP\Core\Http\ExpressRequest::class,
        \PivotPHP\Core\Http\Request::class
    );
}

// Response class alias
if (!class_exists(\PivotPHP\Core\Http\Response::class, false)) {
    class_alias(
        \PivotPHP\Core\Http\ExpressResponse::class,
        \PivotPHP\Core\Http\Response::class
    );
}

/**
 * Note: HeaderRequest class is no longer needed as header functionality
 * is now integrated into ExpressRequest. Legacy code using HeaderRequest
 * should migrate to using $request->header() and $request->setHeaders() methods.
 *
 * If absolutely necessary, create a custom HeaderRequest wrapper:
 *
 * class HeaderRequest {
 *     private $request;
 *     public function __construct($request) {
 *         $this->request = $request;
 *     }
 *     public function get($name) {
 *         return $this->request->header($name);
 *     }
 * }
 */
