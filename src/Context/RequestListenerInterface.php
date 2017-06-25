<?php
namespace StephenHarris\RestApiExtension\Context;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Provides step definitions for managing plugins and themes.
 */
interface RequestListenerInterface
{
    public function requestSent(Request $request, Response $response);
}
