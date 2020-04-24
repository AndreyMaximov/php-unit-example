<?php

use App\Exception\GxpIdIsNotSetException;
use App\Exception\GxpNotFoundException;
use App\Exception\GxpUnknownScopeException;
use App\Exception\GxpUnknownTypeException;
use PHPUnit\Framework\TestCase;

use App\GxpResolver;

/**
 * Class GxpResolverTest
 *
 * @covers \App\GxpResolver
 */
class GxpResolverTest extends TestCase {

  /**
   * @covers \App\GxpResolver::__construct
   */
  public function test__construct() {
    $filename = tempnam('/tmp', __FUNCTION__);
    file_put_contents($filename, json_encode([]));

    $resolver = new GxpResolver($filename);
    $this->assertEquals('App\GxpResolver', get_class($resolver));

    $resolver_data = $resolver->getData();
    $this->assertIsArray($resolver_data);
    $this->assertEmpty($resolver_data);
  }

  /**
   * @covers \App\GxpResolver::getWrites
   */
  public function testGetWrites() {
    $resolver = new GxpResolver();
    $this->assertEquals(0, $resolver->getWrites());
    $resolver->flushToPermanentStorage();
    $this->assertEquals(0, $resolver->getWrites());
  }

  /**
   * @covers \App\GxpResolver::__construct
   */
  public function testStorageNotExists() {
    // Create a tempfile and immediately remove it.
    unlink($filename = tempnam('/tmp', __FUNCTION__));

    $resolver = new GxpResolver($filename);
    $this->assertEquals('App\GxpResolver', get_class($resolver));
  }

}
