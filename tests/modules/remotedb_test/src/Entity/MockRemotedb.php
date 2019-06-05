<?php

namespace Drupal\remotedb_test\Entity;

use Drupal\remotedb\Entity\Remotedb;

/**
 * A mocked remote database.
 */
class MockRemotedb extends Remotedb {

  /**
   * The callback to use for function calls.
   *
   * @var callable
   */
  protected $callback;

  /**
   * Sets the callback to use for method calls.
   *
   * @param callable $callback
   *   A callable to use for method calls.
   */
  public function setCallback(callable $callback) {
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
