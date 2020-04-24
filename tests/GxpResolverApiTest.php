<?php

declare(strict_types=1);

use App\GxpResolver;
use FastRoute\RouteCollector;
use PHPUnit\Framework\TestCase;

use App\GxpResolverApi;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class GxpResolverTest
 *
 * @covers \App\GxpResolverApi
 */
class GxpResolverApiTest extends TestCase {

  protected function getTestObject():GxpResolverApi {
    $loop = $this->createStub(\React\EventLoop\LoopInterface::class);
    $routes = $this->createStub(RouteCollector::class);
    $resolver = $this->createStub(GxpResolver::class);

    return new GxpResolverApi($loop, $routes, $resolver);
  }

  public function testConstruct() {
    $api = $this->getTestObject();
    $this->assertEquals('App\GxpResolverApi', get_class($api));
    unset($api);
  }

  public function testReportStats() {
    $api = $this->getTestObject();
    $api->reportStats();
    $this->assertEquals('App\GxpResolverApi', get_class($api));
    unset($api);
  }

  public function testHandleRequest() {
    $request = $this->createStub(ServerRequestInterface::class);
    $api = $this->getTestObject();
    $response = $api->handleRequest($request);
    $this->assertEquals('ok', $response->getBody()->getContents());
    $this->assertEquals(['text/plain'], $response->getHeader('Content-Type'));
    $this->assertEquals(200, $response->getStatusCode());
    unset($api);
  }

  public function testFlushDataHandler() {
    $request = $this->createStub(ServerRequestInterface::class);
    $api = $this->getTestObject();
    $response = $api->flushDataHandler($request);
    $this->assertEquals('ok', $response->getBody()->getContents());
    $this->assertEquals(['text/plain'], $response->getHeader('Content-Type'));
    $this->assertEquals(200, $response->getStatusCode());
    unset($api);
  }

  public function testStatsRequestHandler() {
    $request = $this->createStub(ServerRequestInterface::class);
    $loop = $this->createStub(\React\EventLoop\LoopInterface::class);
    $routes = $this->createStub(RouteCollector::class);
    $resolver = $this->createStub(GxpResolver::class);
    $api = new GxpResolverApi($loop, $routes, $resolver);

    $response = $api->statsRequestHandler($request);
    //print_r($response);

    $this->assertEquals('App\GxpResolverApi', get_class($api));
    unset($api);
  }
}
