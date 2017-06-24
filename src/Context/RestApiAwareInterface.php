<?php
namespace StephenHarris\RestApiExtension\Context;

/**
 * Provides step definitions for managing plugins and themes.
 */
interface RestApiAwareInterface
{
    public function setRestParameters($parameters);

    public function setClient(\GuzzleHttp\Client $client);
}
