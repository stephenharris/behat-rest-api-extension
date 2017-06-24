<?php
namespace StephenHarris\RestApiExtension\Context\Initialiser;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

use StephenHarris\RestApiExtension\Context\RestApiAwareInterface;
use StephenHarris\RestApiExtension\Context\OAuthAwareInterface;
use StephenHarris\RestApiExtension\Server\WpRestApi;

/*
 * Common interface for Behat contexts.
 */
class RestApiAwareInitialiser implements ContextInitializer
{

  /**
   * @var array
   */
    protected $parameters = [];


  /**
   * Constructor.
   *
   * @param array                  $parameters
   */
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
                $this->client = new \GuzzleHttp\Client();
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
    }
}
