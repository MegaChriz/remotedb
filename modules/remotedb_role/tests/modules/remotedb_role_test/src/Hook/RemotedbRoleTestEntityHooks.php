<?php

declare(strict_types=1);

namespace Drupal\remotedb_role_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Entity hook implementations for remotedb_role_test.
 */
class RemotedbRoleTestEntityHooks {

  /**
   * State API service.
   */
  protected readonly StateInterface $state;

  /**
   * Constructs entity hook implementations.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state API service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for 'remotedb'.
   */
  #[Hook('remotedb_load')]
  public function remotedbLoad(array $entities): void {
    foreach ($entities as $remotedb) {
      if (is_object($remotedb) && method_exists($remotedb, 'setCallback')) {
        $remotedb->setCallback([$this, 'remotedbCallback']);
      }
    }
  }

  /**
   * Handles mocked remote database calls for tests.
   *
   * @param string $method
   *   The method being called.
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   Returns different values depending on the method call.
   */
  public function remotedbCallback(string $method, array $params): mixed {
    switch ($method) {
      case 'dbsubscription.retrieve':
        return $this->state->get('remotedb_role_subscriptions', []);
    }

    return NULL;
  }

}
