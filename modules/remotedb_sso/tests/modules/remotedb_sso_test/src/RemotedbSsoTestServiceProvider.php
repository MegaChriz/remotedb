<?php

namespace Drupal\remotedb_sso_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the SSO ticket service.
 */
class RemotedbSsoTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // Overrides 'remotedb_sso.ticket' class with the test mock.
    $definition = $container->getDefinition('remotedb_sso.ticket');
    $definition->setClass(MockTicketService::class);
    $definition->setArguments([
      new Reference('datetime.time'),
      new Reference('password_generator'),
      new Reference('entity_type.manager'),
    ]);
    $definition->setFactory(NULL);
  }

}
