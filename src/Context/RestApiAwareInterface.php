<?php
namespace StephenHarris\RestApiExtension\Context;

interface RestApiAwareInterface
{
    public function setRestParameters($parameters);

    public function setClient(\GuzzleHttp\Client $client);
}
