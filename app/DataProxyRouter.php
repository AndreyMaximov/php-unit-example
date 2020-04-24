<?php

namespace App;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

/**
 * Class Router
 *
 * @package App
 */
final class DataProxyRouter {

  /**
   * The dispatcher.
   *
   * @var \FastRoute\Dispatcher\GroupCountBased
   */
  private $dispatcher;

  public function __construct(RouteCollector $routes) {
    $this->dispatcher = new GroupCountBased($routes->getData());
  }

  public function __invoke(ServerRequestInterface $request) {
    $route_info = $this->dispatcher
      ->dispatch($request->getMethod(), $request->getUri()->getPath());

    switch ($route_info[0]) {
      case Dispatcher::NOT_FOUND:
        return static::notFoundResponse();
        break;

      case Dispatcher::METHOD_NOT_ALLOWED:
        return static::notAllowedResponse();
        break;

      case Dispatcher::FOUND:
        $params = $route_info[2];
        return $route_info[1]($request, ... array_values($params));
        //return $route_info[1]($request, $params);
        break;
    }

    throw new LogicException('Something wrong with routing');
  }

  public static function notFoundResponse() {
    return new Response(404,
      ['Content-Type' => 'text/plain'],
      'Not found');
  }

  public static function notAllowedResponse() {
    return new Response(405,
      ['Content-Type' => 'text/plain'],
      'Method not allowed');
  }

}
