<?php
/**
 * Simple HTTP server.
 */

namespace App;

use App\Exception\GxpNotFoundException;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use FastRoute\RouteCollector;
use React\EventLoop\LoopInterface;
use React\Http\Response;
use React\Http\Server;
use React\Promise\Promise;
use React\Socket\Server as SocketServer;
use Psr\Http\Message\ServerRequestInterface;
use function GuzzleHttp\json_decode as guzzle_json_decode;

/**
 * Class DataProxy
 *
 * @package App
 */
class GxpResolverApi implements EventEmitterInterface {

  use EventEmitterTrait;

  const DEFAULT_RESPONSE_BODY = 'ok';
  const APPLICATION_JSON_CONTENT_TYPE = 'application/json';
  const DEFAULT_RESPONSE_CONTENT_TYPE = 'text/plain';
  const HTTP_STATUS_OK = 200;
  const HTTP_STATUS_ERROR = 500;
  const LISTEN_PORT = '0.0.0.0:8080';
  const LOG_INTERVAL = 60;
  const STATS_INTERVAL = 60;

  /**
   * The main loop.
   *
   * @var \React\EventLoop\LoopInterface
   */
  private $loop;

  /**
   * The HTTP server.
   *
   * @var \React\Http\Server
   */
  private $server;

  /**
   * The route collection.
   *
   * @var \FastRoute\RouteCollector
   */
  private $route_collection;

  protected $requests = 0;

  /**
   * The GXP Resolver.
   *
   * @var \App\GxpResolver
   */
  private $resolver;

  /**
   * @var \React\Socket\Server;
   */
  private $socket;

  /**
   * App constructor.
   *
   * @param \React\EventLoop\LoopInterface $loop
   * @param \FastRoute\RouteCollector $routes
   * @param \App\GxpResolver $resolver
   */
  public function __construct(LoopInterface $loop, RouteCollector $routes, GxpResolver $resolver) {
    $this->loop = $loop;
    $this->route_collection = $routes;
    $this->resolver = $resolver;
    $this->listen();
    $this->loop->addPeriodicTimer(static::STATS_INTERVAL, [$this, 'reportStats']);
    $this->loop->addPeriodicTimer(static::LOG_INTERVAL, [$this, 'flushToPermanentStorage']);
  }

  public function __destruct() {
    $this->socket->close();
  }

  /**
   * Starts the server.
   */
  private function listen() {
    $this->route_collection->get('/', [$this, 'handleRequest']);
    $this->route_collection->get('/health', [$this, 'handleRequest']);
    $this->route_collection->get('/stats', [$this, 'statsRequestHandler']);
    $this->route_collection->get('/get/scope/{scope:.+}/cat/{cat:.+}/class/{class:.+}', [$this, 'getDataHandler']);
    $this->route_collection->get('/api/v1/dump', [$this, 'dataRequestHandler']);
    $this->route_collection->get('/api/v1/search/scope/{scope:.+}/cat/{cat:.+}/class/{class:.+}', [$this, 'searchDataHandler']);
    $this->route_collection->get('/api/v1/flush', [$this, 'flushDataHandler']);
    $this->route_collection->post('/api/v1/search/scope/{scope:.+}', [$this, 'searchMultiDataHandler']);
    $this->route_collection->post('/api/v1/add', [$this, 'setDataHandler']);

    $this->socket = new SocketServer(self::LISTEN_PORT, $this->loop);
    $server = new Server(new DataProxyRouter($this->route_collection));
    $server->listen($this->socket);
  }

  /**
   * Handles requests.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *   The request object.
   *
   * @return \React\Http\Response|\React\Promise\Promise
   *   The response object.
   */
  public function handleRequest(ServerRequestInterface $request) {
    $this->requests++;
    return new Response(static::HTTP_STATUS_OK,
      ['Content-Type' => static::DEFAULT_RESPONSE_CONTENT_TYPE],
      self::DEFAULT_RESPONSE_BODY);
  }

  /**
   * Handles flush requests.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *   The request object.
   *
   * @return \React\Http\Response|\React\Promise\Promise
   *   The response object.
   */
  public function flushDataHandler(ServerRequestInterface $request) {
    $this->requests++;
    $this->resolver->flush();
    $this->reportStats();
    return new Response(static::HTTP_STATUS_OK,
      ['Content-Type' => static::DEFAULT_RESPONSE_CONTENT_TYPE],
      self::DEFAULT_RESPONSE_BODY);
  }

  /**
   * Handles stats requests.
   *
   * @return \React\Promise\Promise
   */
  public function statsRequestHandler(ServerRequestInterface $request) {
    $this->requests++;
    return new Promise(function ($resolve, $reject) {
      // Setting it to the future tick as it of a lower priority task.
      $this->loop->futureTick(function() use ($resolve) {
        $resolve(static::jsonResponse([
          'requests' => $this->requests,
          'writes' => $this->resolver->getWrites(),
          'memory_usage' => memory_get_usage(),
          'memore_usage_real' => memory_get_usage(TRUE),
        ]));
      });
    });
  }

  /**
   * Handles data requests.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *   The request.
   *
   * @return \React\Promise\Promise
   */
  public function dataRequestHandler(ServerRequestInterface $request) {
    $this->requests++;
    return new Promise(function ($resolve, $reject) {
      // Setting it to the future tick as it of a lower priority task.
      $this->loop->futureTick(function() use ($resolve) {
        $resolve(static::jsonResponse($this->resolver->getData()));
      });
    });
  }

  /**
   * Handles data requests.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *   The request.
   * @param string $scope
   *   The scope
   * @param $category
   *   The category
   * @param $class
   *   The class
   *
   * @return \React\Promise\Promise
   */
  public function getDataHandler(ServerRequestInterface $request, $scope, $category, $class) {
    $this->requests++;
    // App::_log(sprintf("GET $scope : $category : $class"));
    return new Promise(function ($resolve, $reject) use ($scope, $category, $class) {
      try {
        $data = $this->resolver->get($scope, GxpResolver::TYPE_CLASS, $class);
        $resolve(static::jsonResponse($data));
      }
      catch (GxpNotFoundException $e) {
        $resolve(DataProxyRouter::notFoundResponse());
      }
      catch (\Exception $e) {
        $resolve(DataProxyRouter::notFoundResponse());
      }
    });
  }

  /**
   * Handles search requests.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *   The request.
   * @param string $scope
   *   The scope
   * @param $category
   *   The category
   * @param $class
   *   The class
   *
   * @return \React\Http\Response
   */
  public function searchDataHandler(ServerRequestInterface $request, $scope, $category, $class) {
    $this->requests++;
    try {
      $data = $this->resolver->search($scope, $category, $class);
      return static::jsonResponse($data);
    }
    catch (GxpNotFoundException $e) {
      return DataProxyRouter::notFoundResponse();
    }
  }

  /**
   * Handles search requests.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *   The request.
   * @param string $scope
   *   The scope
   *
   * @return \React\Promise\Promise
   */
  public function searchMultiDataHandler(ServerRequestInterface $request, $scope) {
    $this->requests++;
    return new Promise(function ($resolve, $reject) use ($request, $scope) {
      try {
        $request_data = guzzle_json_decode($request->getBody()->getContents(), true);
        $response_data = [];
        foreach ($request_data as $key => $item) {
          $response_data[$key] = $this->resolver->search($scope, $item['category'], $item['class']);
        }
        $resolve(static::jsonResponse($response_data));
      }
      catch (\Exception $e) {
        print_r($e->getMessage());
        $resolve(static::jsonErrorResponse($e->getMessage()));
      }
    });
  }

  /**
   * Handles data requests.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *   The request.
   *
   * @return \React\Promise\Promise
   */
  public function setDataHandler(ServerRequestInterface $request) {
    $this->requests++;
    return new Promise(function ($resolve, $reject) use ($request) {
      try {
        $data = guzzle_json_decode($request->getBody()->getContents());
        $this->resolver->setValue($data->scope, $data->type, $data->name, $data->picture);
        $resolve(static::jsonResponse(
          $this->resolver->get($data->scope, $data->type, $data->name)));
      }
      catch (\Exception $e) {
        print_r($e->getMessage());
        $resolve(static::jsonErrorResponse($e->getMessage()));
      }
    });
  }

  /**
   * Emits stats event.
   */
  public function reportStats() {
    static $last_reported = 0;
    $this->emit('stats', [
      $this->requests,
      $this->requests - $last_reported,
      $this->resolver->getWrites(),
    ]);
    $last_reported = $this->requests;
  }

  /**
   * Builds a success HTTP response.
   *
   * @param mixed $data
   *   The data to be sent in the body of the response.
   *
   * @return \React\Http\Response
   */
  private static function jsonResponse($data):Response {
    return new Response(
      static::HTTP_STATUS_OK,
      ['Content-Type' => static::APPLICATION_JSON_CONTENT_TYPE],
      json_encode($data)
    );
  }

  /**
   * Builds an error HTTP response.
   *
   * @param string $message
   *   The message to be the body of the response.
   *
   * @return \React\Http\Response
   */
  private static function jsonErrorResponse($message):Response {
    return new Response(
      static::HTTP_STATUS_ERROR,
      ['Content-Type' => static::DEFAULT_RESPONSE_CONTENT_TYPE],
      $message
    );
  }

  public function flushToPermanentStorage() {
    $this->resolver->flushToPermanentStorage();
  }

}
