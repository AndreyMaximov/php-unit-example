<?php

namespace App;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use React\EventLoop\Factory as ReactLoopFactory;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ServiceProvider.
 *
 * @package App
 */
class ServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(Container $container) {
    $container['react.loop'] = function ($c) {
      return ReactLoopFactory::create();
    };

    $container['data.gxp_resolver'] = function ($c) {
      return new GxpResolver($c['config']['resolver']['perm_storage']);
    };

    $container['data.api'] = function ($c) {
      return new GxpResolverApi($c['react.loop'], $c['data.api.route_collection'], $c['data.gxp_resolver']);
    };

    $container['data.api.route_collection'] = function ($c) {
      return new RouteCollector(new StdRouteParser(), new GroupCountBased());
    };

    $container['config'] = function ($c) {
      $config['docroot'] = DOCROOT;
      $config = Yaml::parseFile(DOCROOT . '/config.yml');

      return $config;
    };
  }

}
