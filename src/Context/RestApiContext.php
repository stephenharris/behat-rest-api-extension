<?php
namespace StephenHarris\RestApiExtension\Context;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

use Behat\Behat\Context\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Assert\Assertion;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides step definitions for managing plugins and themes.
 */
class RestApiContext implements Context, RestApiAwareInterface
{
    private $parameters;

    private $client;

    public function setClient(\GuzzleHttp\Client $client)
    {
        $this->client = $client;
    }

    public function setRestParameters($parameters)
    {
        $this->root = $parameters['endpoint'];
        $this->parameters = $parameters;
    }

    /**
     * @var string
     */
    private $auth;

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var \GuzzleHttp\Message\RequestInterface|RequestInterface
     */
    private $request;

    /**
     * @var \GuzzleHttp\Message\ResponseInterface|ResponseInterface
     */
    private $response;

    private $placeHolders = array();


    /**
     * Adds Basic Authentication header to next request.
     *
     * @param string $username
     * @param string $password
     *
     * @Given /^I authenticate via basic authentication as "([^"]*)" with password "([^"]*)"$/
     */
    public function iAuthenticateUsingBasicAUthentication($username, $password)
    {
            $this->auth = [$username, $password];
    }

    /**
     * Adds Basic Authentication header to next request.
     *
     * @param string $username
     * @param string $password
     *
         * @Given /^I authenticate as "([^"]*)" with application password "([^"]*)"$/
     */
    public function iAuthenticateUsingApplicationPassword($username, $password)
    {
            $this->auth = [$username, $password];
    }

    /**
     * Sets a HTTP Header.
     *
     * @param string $name  header name
     * @param string $value header value
     *
     * @Given /^I set header "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetHeaderWithValue($name, $value)
    {
        $this->addHeader($name, $value);
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method request method
     * @param string $url    relative url
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)"$/
     */
    public function iSendARequest($method, $url)
    {
        $url = $this->prepareUrl($url);
        $this->request = new Request($method, $url, $this->headers);
        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with field values from Table.
     *
     * @param string    $method request method
     * @param string    $url    relative url
     * @param TableNode $post   table of post values
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with values:$/
     */
    public function iSendARequestWithValues($method, $url, TableNode $post)
    {
        $url = $this->prepareUrl($url);
        $fields = array();

        foreach ($post->getRowsHash() as $key => $val) {
            $fields[$key] = $this->replacePlaceHolder($val);
        }

        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $this->request = new Request($method, $url, $this->headers, http_build_query($fields, '', '&'));

        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with raw body from PyString.
     *
     * @param string       $method request method
     * @param string       $url    relative url
     * @param PyStringNode $string request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with body:$/
     */
    public function iSendARequestWithBody($method, $url, PyStringNode $string)
    {
        $url = $this->prepareUrl($url);
        $string = $this->replacePlaceHolder(trim($string));
        $this->request = new Request($method, $url, $this->headers, $string);
        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with form data from PyString.
     *
     * @param string       $method request method
     * @param string       $url    relative url
     * @param PyStringNode $body   request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with form data:$/
     */
    public function iSendARequestWithFormData($method, $url, PyStringNode $body)
    {
        $url = $this->prepareUrl($url);
        $body = $this->replacePlaceHolder(trim($body));

        $fields = array();
        parse_str(implode('&', explode("\n", $body)), $fields);

        $this->request = new Request(
            $method,
            $url,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query($fields, null, '&')
        );
        $this->sendRequest();
    }

    /**
     * Checks that response has specific status code.
     *
     * @param string $code status code
     *
     * @Then /^(?:the )?response code should be (\d+)$/
     */
    public function theResponseCodeShouldBe($code)
    {
        $expected = intval($code);
        $actual = intval($this->response->getStatusCode());
        Assertion::same($actual,$expected);
    }

    /**
     * Checks that response body contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should contain "([^"]*)"$/
     */
    public function theResponseShouldContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/i';
        $actual = (string) $this->response->getBody();
        Assertion::regex($actual,$expectedRegexp);
    }

    /**
     * Checks that response body doesn't contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should not contain "([^"]*)"$/
     */
    public function theResponseShouldNotContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/';
        $actual = (string) $this->response->getBody();
				try {
					Assertion::regex($actual,$expectedRegexp);
					throw new \Exception("Response contains given text");
				} catch (\AssertionFailedException $e){
					//Do nothing.
				}
    }

    /**
     * Checks that response body contains JSON from PyString.
     *
     * Do not check that the response body /only/ contains the JSON from PyString,
     *
     * @param PyStringNode $jsonString
     *
     * @throws \RuntimeException
     *
     * @Then /^(?:the )?response should contain json:$/
     */
    public function theResponseShouldContainJson(PyStringNode $jsonString)
    {
        $etalon = json_decode($this->replacePlaceHolder($jsonString->getRaw()), true);
        $actual = json_decode($this->response->getBody(), true);

        if (null === $etalon) {
            throw new \RuntimeException(
                "Can not convert etalon to json:\n" . $this->replacePlaceHolder($jsonString->getRaw())
            );
        }

        if (null === $actual) {
            throw new \RuntimeException(
                "Can not convert actual to json:\n" . $this->replacePlaceHolder((string) $this->response->getBody())
            );
        }

        Assertion::greaterOrEqualThan(count($actual), count($etalon));
        foreach ($etalon as $key => $needle) {
            Assertion::keyExists($actual, $key);
            Assertion::eq($actual[$key],$etalon[$key]);
        }
    }

    /**
     * Prints last response body.
     *
     * @Then print response
     */
    public function printResponse()
    {
        $request = $this->request;
        $response = $this->response;

        echo sprintf(
            "%s %s => %d:\n%s",
            $request->getMethod(),
            (string) ($request instanceof RequestInterface ? $request->getUri() : $request->getUrl()),
            $response->getStatusCode(),
            (string) $response->getBody()
        );
    }

    /**
     * Prepare URL by replacing placeholders and trimming slashes.
     *
     * @param string $url
     *
     * @return string
     */
    private function prepareUrl($url)
    {
        return ltrim($this->replacePlaceHolder($url), '/');
    }

    /**
     * Sets place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     *
     * @param string $key   token name
     * @param string $value replace value
     */
    public function setPlaceHolder($key, $value)
    {
        $this->placeHolders[$key] = $value;
    }

    /**
     * Replaces placeholders in provided text.
     *
     * @param string $string
     *
     * @return string
     */
    protected function replacePlaceHolder($string)
    {
        foreach ($this->placeHolders as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Returns headers, that will be used to send requests.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds header
     *
     * @param string $name
     * @param string $value
     */
    protected function addHeader($name, $value)
    {
        if (isset($this->headers[$name])) {
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = array($this->headers[$name]);
            }

            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes a header identified by $headerName
     *
     * @param string $headerName
     */
    protected function removeHeader($headerName)
    {
        if (array_key_exists($headerName, $this->headers)) {
            unset($this->headers[$headerName]);
        }
    }

    private function sendRequest()
    {
        try {
            $this->response = $this->getClient()->send($this->request, [
              'auth' => $this->auth
            ]);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (null === $this->response) {
                throw $e;
            }
        }
    }

    private function getClient()
    {
        if (null === $this->client) {
            throw new \RuntimeException('Client has not been set in WebApiContext');
        }

        return $this->client;
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method request method
     * @param string $url    relative url
     *
     * @Given /^(?:I )?authenticate via oauth 1$/
     */
    public function iAuthenticateViaOauth1()
    {
        $this->auth = 'oauth';
        $middleware = new Oauth1([
            'consumer_key'    => $this->parameters['oauth1']['client_key'],
            'consumer_secret' => $this->parameters['oauth1']['client_secret'],
            'token'           => $this->parameters['oauth1']['token_key'],
            'token_secret'    => $this->parameters['oauth1']['token_secret']
        ]);
        $this->getClient()->getConfig('handler')->push($middleware);
    }
}
