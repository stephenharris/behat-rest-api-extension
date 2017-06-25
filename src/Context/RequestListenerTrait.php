<?php
namespace StephenHarris\RestApiExtension\Context;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Provides step definitions for managing plugins and themes.
 */
trait RequestListenerTrait
{
    private $_request;

    private $_response;

    public function requestSent(Request $request, Response $response)
    {
        $this->_request = $request;
        $this->_response = $response;
    }

    protected function getCurrentRequest()
    {
        return $this->_request;
    }

    protected function getCurrentResponse()
    {
        return $this->_response;
    }
}
