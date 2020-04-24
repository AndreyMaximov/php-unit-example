<?php

namespace App;

use Pimple\Container;
use React\HttpClient\Response as ReactResponse;
use stdClass;


/**
 * Class App
 *
 * @package App
 */
class App {

  const AUTORELOADING_INTERVAL = 5;

  /**
   * @var \Pimple\Container $container
   *
   * The Dependency Injection container.
   */
  private $container;

  /**
   * The main loop.
   *
   * @var \React\EventLoop\LoopInterface
   */
  private $loop;

  /**
   * The Data Proxy.
   *
   * @var \App\GxpResolverApi
   */
  private $api;

  // Auto-reloading timestamps.
  private $filemtime = [];

  /**
   * App constructor.
   */
  public function __construct() {
    $this->container = new Container();
    $this->container->register(new ServiceProvider());

    $this->loop = $this->container['react.loop'];
    $this->api = $this->container['data.api'];

    $this->setDataProxyEventHandlers();
    $this->loop->addPeriodicTimer(static::AUTORELOADING_INTERVAL, [$this, 'handleAutoReloading']);
  }

  /**
   * Main app logic.
   */
  public function run() {
    $this->loop->run();
    $this->postLoop();
  }

  /**
   * Post loop logic.
   */
  private function postLoop() {
    $this->api->flushToPermanentStorage();
    // $this->logger->info('Exit');
  }

  /**
   * Sets Data Proxy event handlers.
   */
  private function setDataProxyEventHandlers() {
    $this->api
      ->on('update', function ($updates) {
        foreach ($updates as $sensor_id => $value) {
          $this->sensors[$sensor_id] = $value;
        }
        // To be removed.
        $this->_log('updates: ' . json_encode($updates));
      })
      ->on('stats', function ($requests_total, $requests_now, $writes) {
        $message = sprintf('Total: %d, now %d, writes %d, memory usage: %d / %d',
          $requests_total,
          $requests_now,
          $writes,
          memory_get_usage(),
          memory_get_usage(TRUE));
        $this->_log($message);
      })
      ->on('stored', function ($data) {

      });
  }

  /**
   * Handles  auto-reloading.
   */
  public function handleAutoReloading() {
    $files = get_included_files();
    foreach ($files as $file) {
      if (!file_exists($file)) {
        $this->_log("The $file file has disappeared. Stopping...");
        $this->loop->stop();
      }
      if (empty($this->filemtime[$file])) {
        // Loaded for the first time.
        $this->filemtime[$file] = filemtime($file);
        continue;
      }
      clearstatcache(FALSE, $file);
      if ($this->filemtime[$file] != filemtime($file)) {
        $this->_log("The $file file has changed. Stopping...");
        $this->loop->stop();
      }
    }
  }

  /**
   * Echoes a string prefixed by a timestamp.
   *
   * @param string $data
   *   The string to be echoed.
   */
  public static function _log($data) {
    echo sprintf("%s\t%s", date('c'), $data) . PHP_EOL;
  }

}
