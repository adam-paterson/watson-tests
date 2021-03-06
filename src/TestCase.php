<?php

namespace IBM\Watson\Tests;

use Guzzle\Common\Event;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Guzzle\Http\Message\RequestInterface as GuzzleRequestInterface;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Guzzle\Plugin\Mock\MockPlugin;

/**
 * Class TestCase
 * @package IBM\Watson\Common
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $mockHttpRequests = [];
    /**
     * @var
     */
    private $mockRequest;
    /**
     * @var
     */
    private $httpClient;
    /**
     * @var
     */
    private $httpRequest;

    /**
     * Mark a request as being mocked
     *
     * @param GuzzleRequestInterface $request
     * @return $this
     */
    public function addMockedHttpRequest(GuzzleRequestInterface $request)
    {
        $this->mockHttpRequests[] = $request;

        return $this;
    }

    /**
     * Get all of the mocked requests
     *
     * @return array
     */
    public function getMockedRequests()
    {
        return $this->mockHttpRequests;
    }

    public function getMockRequest()
    {
        if (null === $this->mockRequest) {
            $this->mockRequest = m::mock('\IBM\Watson\Common\Message\RequestInterface');
        }

       return $this->mockRequest;
    }

    /**
     * Get a mock response for a client by mock file name
     *
     * @param string $path Relative path to the mock response file
     * @return Response
     */
    public function getMockHttpResponse($path, $code = 200)
    {
        $ref = new \ReflectionObject($this);
        $dir = dirname($ref->getFileName());
        // if mock file doesn't exist, check parent directory
        if (!file_exists($dir . '/Mock/' . $path) && file_exists($dir . '/../Mock/' . $path)) {
            return new Response($code, [], file_get_contents($dir . '/../Mock/' . $path));
        }
        return new Response($code, [], file_get_contents($dir . '/Mock/' . $path));
    }

    public function setMockHttpResponse($paths)
    {
        $this->mockHttpRequests = [];
        $that = $this;
        $mock = new MockPlugin(null, true);
        $this->getHttpClient()->getEventDispatcher()->removeSubscriber($mock);
        $mock->getEventDispatcher()->addListener('mock.request', function(Event $event) use ($that) {
            $that->addMockedHttpRequest($event['request']);
        });

        foreach ((array) $paths as $path) {
            $mock->addResponse($this->getMockHttpResponse($path));
        }

        $this->getHttpClient()->getEventDispatcher()->addSubscriber($mock);

        return $mock;
    }

    public function getMockHttpClientWithHistoryAndResponses(&$container, $responses)
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $history = Middleware::history($container);
        $stack->push($history);

        return new Client(['handler' => $stack]);
    }

    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new HttpClient;
        }

        return $this->httpClient;
    }

    public function getHttpRequest()
    {
        if (null === $this->httpRequest) {
            $this->httpRequest = new HttpRequest;
        }

        return $this->httpRequest;
    }
}
