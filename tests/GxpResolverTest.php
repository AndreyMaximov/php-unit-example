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
  public function testStorageNotExists() {
    // Create a tempfile and immediately remove it.
    unlink($filename = tempnam('/tmp', __FUNCTION__));

    $resolver = new GxpResolver($filename);
    $this->assertEquals('App\GxpResolver', get_class($resolver));
  }

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
   * @covers \App\GxpResolver::__construct
   * @covers \App\GxpResolver::getData
   */
  public function testEmptyStorageFile() {
    $filename = tempnam('/tmp', __FUNCTION__);
    $resolver = new GxpResolver($filename);
    $this->assertEquals('App\GxpResolver', get_class($resolver));

    $resolver_data = $resolver->getData();
    $this->assertIsArray($resolver_data);
    $this->assertEmpty($resolver_data);
  }

  public function testGetWrites() {
    $resolver = new GxpResolver();
    $this->assertEquals(0, $resolver->getWrites());
    $resolver->flushToPermanentStorage();
    $this->assertEquals(0, $resolver->getWrites());
  }

  public function testUnknownTypeGet() {
    $resolver = new GxpResolver();
    $this->expectException(GxpUnknownTypeException::class);
    $resolver->get('test', 'unknownType', 'test');
  }

  public function testUnknownTypeSet() {
    $resolver = new GxpResolver();
    $this->expectException(GxpUnknownTypeException::class);
    $resolver->setValue('test', 'unknownType', 'test', 'test');
  }

  public function testUnknownScopeGet() {
    $resolver = new GxpResolver();
    $this->expectException(GxpUnknownScopeException::class);
    $resolver->get('test', GxpResolver::TYPE_DEFAULT, 'test');
  }

  public function testNotFoundDefault() {
    $resolver = new GxpResolver();
    $this->expectException(GxpNotFoundException::class);
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CLASS, 'test','value');
    $resolver->get(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_DEFAULT);
  }

  public function testNotFoundCategory() {
    $resolver = new GxpResolver();
    $this->expectException(GxpNotFoundException::class);
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_DEFAULT, '','value');
    $resolver->get(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CATEGORY, 'test');
  }

  public function testNotFoundClass() {
    $resolver = new GxpResolver();
    $this->expectException(GxpNotFoundException::class);
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_DEFAULT, '','value');
    $resolver->get(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CLASS, 'test');
  }

  public function testIdIsNotSetExceptionCategorySet() {
    $resolver = new GxpResolver();
    $this->expectException(GxpIdIsNotSetException::class);
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CATEGORY, '','value');
  }

  public function testIdIsNotSetExceptionClassSet() {
    $resolver = new GxpResolver();
    $this->expectException(GxpIdIsNotSetException::class);
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CLASS, '','value');
  }

  public function testIdIsNotSetExceptionCategoryGet() {
    $resolver = new GxpResolver();
    $this->expectException(GxpIdIsNotSetException::class);
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CATEGORY, 'id','value');
    $resolver->get(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CLASS);
  }

  public function testIdIsNotSetExceptionClassGet() {
    $resolver = new GxpResolver();
    $this->expectException(GxpIdIsNotSetException::class);
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CLASS, 'id','value');
    $resolver->get(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CLASS);
  }

  public function testSetDefaultValue() {
    $scope = 'test';

    $resolver = new GxpResolver();
    $resolver->setDefault($scope, 'testValue');
    $resolver_data = $resolver->getData();

    $this->assertArrayHasKey($scope, $resolver_data);
    $this->assertArrayHasKey(GxpResolver::TYPE_DEFAULT, $resolver_data[$scope]);
    $this->assertArrayHasKey('value', $resolver_data[$scope][GxpResolver::TYPE_DEFAULT]);
    $this->assertArrayHasKey('timestamp', $resolver_data[$scope][GxpResolver::TYPE_DEFAULT]);
    $this->assertEquals('testValue', $resolver_data[$scope][GxpResolver::TYPE_DEFAULT]['value']);
    $this->assertIsFloat($resolver_data[$scope][GxpResolver::TYPE_DEFAULT]['timestamp']);
  }

  public function testSetClassAndCategoryValue() {
    $tests = [
      ['scope' => 'testScope', 'type' => GxpResolver::TYPE_CATEGORY, 'id' => 'testid'],
      ['scope' => 'testScope', 'type' => GxpResolver::TYPE_CLASS, 'id' => 'testid'],
    ];

    foreach ($tests as $test) {
      $scope = $test['scope'];
      $type = $test['type'];
      $id = $test['id'];

      $resolver = new GxpResolver();
      $resolver->setValue($scope, $type, $id, 'testValue');
      $resolver_data = $resolver->getData();

      $this->assertArrayHasKey($scope, $resolver_data);
      $this->assertArrayHasKey($type, $resolver_data[$scope]);
      $this->assertArrayHasKey($id, $resolver_data[$scope][$type]);
      $this->assertArrayHasKey('value', $resolver_data[$scope][$type][$id]);
      $this->assertArrayHasKey('timestamp', $resolver_data[$scope][$type][$id]);
      $this->assertEquals('testValue', $resolver_data[$scope][$type][$id]['value']);
      $this->assertIsFloat($resolver_data[$scope][$type][$id]['timestamp']);
    }
  }

  public function testSetValueWithTimestamp() {
    $resolver = new GxpResolver();

    $time = microtime(true);
    $resolver->setValue('testScope', GxpResolver::TYPE_CLASS, 'testId', 'testValue', $time);
    $expected = [
      'testScope' => [
        GxpResolver::TYPE_CLASS => [
          'testid' => [
            'value' => 'testValue',
            'timestamp' => $time,
          ]
        ],
      ],
    ];
    $this->assertEquals($expected, $resolver->getData());
  }

  public function testFlush() {
    $filename = tempnam('/tmp', __FUNCTION__);

    $resolver = new GxpResolver($filename);
    $resolver->flush();
    $this->assertEquals([], $resolver->getData());
    $this->assertEquals(1, $resolver->getWrites());
    $this->assertEquals([], json_decode(file_get_contents($filename)));
  }

  public function testOnEmptyDataSearch() {
    $resolver = new GxpResolver();

    $scope = GxpResolver::SCOPE_GLOBAL;
    $category = 'category';
    $class = 'class';

    $result = $resolver->search($scope, $category, $class);
    $this->assertNull($result);
  }

  public function testClassMatchSearch() {
    $scope = 'scope';
    $category = 'category';
    $class = md5('class');
    $value = md5('value');

    $resolver = new GxpResolver();
    $resolver->setValue($scope, GxpResolver::TYPE_CLASS, $class, $value);
    $result = $resolver->search($scope, $category, $class);
    $this->assertEquals($result['value'], $value);
  }

  public function testCategoryMatchSearch() {
    $scope = 'scope';
    $category = md5('category');
    $class = md5('class');
    $value = md5('value');

    $resolver = new GxpResolver();
    $resolver->setValue($scope, GxpResolver::TYPE_CATEGORY, $category, $value);
    $result = $resolver->search($scope, $category, $class);
    $this->assertEquals($result['value'], $value);
  }

  public function testDefaultMatchSearch() {
    $scope = 'scope';
    $category = md5('category');
    $class = md5('class');
    $value = md5('value');

    $resolver = new GxpResolver();
    $resolver->setDefault($scope, $value);
    $result = $resolver->search($scope, $category, $class);
    $this->assertEquals($result['value'], $value);
  }

  public function testGlobalClassMatchSearch() {
    $scope = 'scope';
    $category = 'category';
    $class = md5('class');
    $value = md5('value');

    $resolver = new GxpResolver();
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CLASS, $class, $value);
    $result = $resolver->search($scope, $category, $class);
    $this->assertEquals($result['value'], $value);
  }

  public function testGlobalCategoryMatchSearch() {
    $scope = 'scope';
    $category = 'category';
    $class = md5('class');
    $value = md5('value');

    $resolver = new GxpResolver();
    $resolver->setValue(GxpResolver::SCOPE_GLOBAL, GxpResolver::TYPE_CATEGORY, $category, $value);
    $result = $resolver->search($scope, $category, $class);
    $this->assertEquals($result['value'], $value);
  }


  public function testGlobalDefaultMatchSearch() {
    $scope = 'scope';
    $category = 'category';
    $class = md5('class');
    $value = md5('value');

    $resolver = new GxpResolver();
    $resolver->setDefault(GxpResolver::SCOPE_GLOBAL, $value);
    $result = $resolver->search($scope, $category, $class);
    $this->assertEquals($result['value'], $value);
  }

  public function testFlushToPermanentStorage() {
    // Create a tempfile and immediately remove it.
    unlink($filename = tempnam('/tmp', __FUNCTION__));

    $resolver = new GxpResolver($filename);
    $this->assertEquals('App\GxpResolver', get_class($resolver));

    $resolver->flushToPermanentStorage();
    $this->assertFileExists($filename);
    $this->assertEquals('[]', file_get_contents($filename));
    $this->assertEquals(1, $resolver->getWrites());

    // If the storage file is not provided, the writes never increments.
    $resolver = new GxpResolver();
    $resolver->flushToPermanentStorage();
    $this->assertEquals(0, $resolver->getWrites());
  }

}
