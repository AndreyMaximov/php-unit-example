<?php

namespace App;

use App\Exception\GxpIdIsNotSetException;
use App\Exception\GxpNotFoundException;
use App\Exception\GxpUnknownScopeException;
use App\Exception\GxpUnknownTypeException;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use function GuzzleHttp\json_decode as guzzle_json_decode;

class GxpResolver implements EventEmitterInterface {

  use EventEmitterTrait;

  const TYPE_DEFAULT = '_default';
  const TYPE_CLASS = '_class';
  const TYPE_CATEGORY = '_category';
  const SCOPE_GLOBAL = 'GLOBAL';

  private $data = [];

  public $writes = 0;

  private $permStorageFilename = '';

  /**
   * App constructor.
   *
   * @param string $perm_storage_filename
   *   The path to permanent storage file.
   */
  public function __construct($perm_storage_filename = null) {
    $this->permStorageFilename = $perm_storage_filename;
    $this->loadFromPermanentStorage();
  }

  /**
   * Return the internal data structure.
   *
   * @return array
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Returns the writes count.
   *
   * @return int
   */
  public function getWrites() {
    return $this->writes;
  }

  /**
   * Empties the data.
   */
  public function flush() {
    $this->data = [];
    $this->flushToPermanentStorage();
  }

  /**
   * Backs the data to disk, emits an event.
   *
   * @emits stored
   */
  public function flushToPermanentStorage() {
    if (!$this->permStorageFilename) {
      return;
    }
    $log_data = json_encode($this->data, JSON_PRETTY_PRINT);
    file_put_contents($this->permStorageFilename, $log_data);
    $this->writes++;
    $this->emit('stored', [$this->data]);
  }

  /**
   * Load the previously logged data.
   */
  private function loadFromPermanentStorage() {
    if (!file_exists($this->permStorageFilename)) {
      return;
    }
    if ($contents = file_get_contents($this->permStorageFilename)) {
      $this->data = guzzle_json_decode($contents, TRUE);
    }
  }

  /**
   * Returns allowed types.
   *
   * @return array
   *   The allowed types.
   */
  public static function getTypes() {
    return [self::TYPE_DEFAULT, self::TYPE_CATEGORY, self::TYPE_CLASS];
  }

  /**
   * Search in the index.
   *
   * @param string $scope
   *   The scope to search in.
   * @param string $category
   *   The category to fall back to.
   * @param string $class
   *   The class to search for.
   *
   * @return mixed
   */
  public function search($scope, $category, $class) {
    try {
      $result = $this->get($scope, self::TYPE_CLASS, $class);
      $result += ['scope' => $scope, 'type' => self::TYPE_CLASS];
      return $result;
    }
    catch (GxpNotFoundException $e) {
      // Intentionally empty.
    }
    catch (GxpUnknownScopeException $e) {
      // Intentionally empty.
    }

    try {
      $result = $this->get($scope, self::TYPE_CATEGORY, $category);
      $result += ['scope' => $scope, 'type' => self::TYPE_CATEGORY];
      return $result;
    }
    catch (GxpNotFoundException $e) {
      // Intentionally empty.
    }
    catch (GxpUnknownScopeException $e) {
      // Intentionally empty.
    }

    try {
      $result = $this->get($scope, self::TYPE_DEFAULT);
      $result += ['scope' => $scope, 'type' => self::TYPE_DEFAULT];
      return $result;
    }
    catch (GxpNotFoundException $e) {
      // Intentionally empty.
    }
    catch (GxpUnknownScopeException $e) {
      // Intentionally empty.
    }

    if ($scope != self::SCOPE_GLOBAL) {
      return $this->search(self::SCOPE_GLOBAL, $category, $class);
    }

    return NULL;
  }

  /**
   * Get stored data.
   *
   * @param string $scope
   *
   * @param string $type
   * @param string|null $id
   *
   * @return mixed
   */
  public function get($scope, $type, $id = NULL) {
    if (!in_array($type, self::getTypes())) {
      throw new GxpUnknownTypeException("Type '$type' is not allowed");
    }

    if (!array_key_exists($scope, $this->data)) {
      throw new GxpUnknownScopeException("Scope '$scope' is unknown");
    }

    if ($type == self::TYPE_DEFAULT) {
      if (!array_key_exists($type, $this->data[$scope])) {
        throw new GxpNotFoundException("Data not found fo $type in scope '$scope'");
      }
      $data = $this->data[$scope][$type];
    }
    else {
      if (!isset($id)) {
        throw new GxpIdIsNotSetException('The ID must be provided');
      }

      if (!isset($scope, $this->data[$scope][$type][$id])) {
        throw new GxpNotFoundException("Data not found fo $type:$id in scope '$scope'");
      }
      $data = $this->data[$scope][$type][$id];
    }

    //$data['age'] = microtime(TRUE) - $data['timestamp'];

    return $data;
  }

  /**
   * Sets the value.
   *
   * @param string $scope
   *   The scope.
   * @param string $type
   *   The type.
   * @param string $id
   *   The ID.
   * @param string $value
   *   The value to be set
   * @param null $timestamp
   *   Optional. The timestamp to be set.
   *
   * @return mixed
   */
  public function setValue($scope, $type, $id, $value, $timestamp = NULL) {
    if (!in_array($type, self::getTypes())) {
      throw new GxpUnknownTypeException("Type '$type' is not allowed");
    }

    $this->verifyScopeExists($scope);
    if ($type == self::TYPE_DEFAULT) {
      $this->data[$scope][$type] = [
        'timestamp' => $timestamp ?: microtime(TRUE),
        'value' => $value,
      ];
    }
    else{
      if (!$id) {
        throw new GxpIdIsNotSetException("ID must be set for $type entries");
      }
      if (!array_key_exists($type, $this->data[$scope])) {
        $this->data[$scope][$type] = [];
      }

      $id = mb_strtolower($id);
      $this->data[$scope][$type][$id] = [
        'timestamp' => $timestamp ?: microtime(TRUE),
        'value' => $value,
      ];
    }

    return $this->get($scope, $type, $id);
  }

  /**
   * A shortcut for setValue with default type.
   *
   * @param $scope
   *   The scope.
   * @param $value
   *   The value to be set.
   * @param mixed|null $timestamp
   *   The timestamp.
   *
   * @return mixed
   */
  public function setDefault($scope, $value, $timestamp = NULL) {
    return $this->setValue($scope, self::TYPE_DEFAULT, '', $value, $timestamp);
  }

  /**
   * Creates a scope if needed.
   *
   * @param string $scope
   *   The scope.
   */
  private function verifyScopeExists($scope) {
    if (!array_key_exists($scope, $this->data)) {
      $this->data[$scope] = [];
    }
  }

}
