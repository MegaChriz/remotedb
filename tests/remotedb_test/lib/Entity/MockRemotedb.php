<?php

/**
 * @file
 * Contains \Drupal\remotedb_test\Entity\MockRemotedb.
 */

namespace Drupal\remotedb_test\Entity;

use Drupal\remotedb\Entity\RemotedbInterface;

class MockRemotedb implements RemotedbInterface {
  /**
   * The callback to use for function calls.
   *
   * @var callable $callback
   */
  protected $callback;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values = array(), $entityType = NULL) { }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return 'mock';
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return 'Mock';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return 'http://www.example.com';
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader($header) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($header, $value) { }

  /**
   * {@inheritdoc}
   */
  public function save() { }

  /**
   * Set the callback to use for method calls.
   *
   * @param callable $callback
   *   A callable to use for method calls.
   */
  public function setCallback($callback) {
    $this->callback = $callback;
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest($method, array $params = array()) {
    if (isset($this->callback)) {
      $callback_args = array($method, $params);
      return call_user_func_array($this->callback, $callback_args);
    }
  }
}
