<?php
namespace StephenHarris\RestApiExtension\Context\Initialiser;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

use StephenHarris\RestApiExtension\Context\RestApiAwareInterface;
use StephenHarris\RestApiExtension\Context\RequestListenerInterface;

use GuzzleHttp\Middleware;

/*
 * Common interface for Behat contexts.
 */
class RestApiAwareInitialiser implements ContextInitializer
{

  /**
   * @var array
   */
    protected $parameters = [];

    private $requestResponseListeners = [];

  /**
   * Constructor.
   *
   * @param array $parameters
   */
    public function __construct($parameters)
    {
        $this->parameters = $parameters;

        $handler = \GuzzleHttp\HandlerStack::create();

        $handler->push(
          function (callable $handler) {
            return function ($request, array $options) use ($handler) {
              return $handler($request, $options)->then(
                function ($response) use ($request) {
                  $this->notifyListeners($request, $response);
                  return $response;
                }
              );
            };
          }
        );

        $this->client = new \GuzzleHttp\Client([
          'verify' => false,
          'handler' => $handler
        ]);
    }

  /**
   * Prepare everything that the Context needs.
   *
   * @param Context $context
   */
    public function initializeContext(Context $context)
    {
        if ($context instanceof RestApiAwareInterface) {
          $context->setClient($this->client);
          $context->setRestParameters($this->parameters);
        }

        if ($context instanceof RequestListenerInterface) {
          $this->requestResponseListeners[] = $context;
        }
    }

    private function notifyListeners($request, $response)
    {
        if ($this->requestResponseListeners) {
            foreach ($this->requestResponseListeners as $listener) {
                $listener->requestSent($request, $response);
            }
        }
    }
}
