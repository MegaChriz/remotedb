<?php

namespace Drupal\remotedb_test\Entity;

use Drupal\remotedb\Entity\Remotedb;

/**
 * A mocked remote database.
 *
 * @todo determine if this class is still needed.
 */
class MockRemotedb extends Remotedb {

  /**
   * The callback to use for function calls.
   *
   * @var callable
   */
  protected $callback;

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return 'http://www.example.com';
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
  public function getHeaders() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($header, $value) {}

  /**
   * {@inheritdoc}
   */
  public function save() {}

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
  public function sendRequest($method, array $params = []) {
    if (isset($this->callback)) {
      $callback_args = [$method, $params];
      return call_user_func_array($this->callback, $callback_args);
    }
  }

}
