<?php

namespace Drupal\Tests\remotedb\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\remotedb\Traits\RemotedbCreationTrait;

/**
 * Provides a base class for Remotedb functional tests.
 */
abstract class RemotedbBrowserTestBase extends BrowserTestBase {

  use RemotedbCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedb',
    'remotedb_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // For easier debugging, enable error logging on screen.
    \Drupal::configFactory()->getEditable('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();
  }

  /**
   * Reloads the given entity from the storage and returns it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be reloaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity) {
    $controller = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $controller->resetCache([$entity->id()]);
    return $controller->load($entity->id());
  }

}
