<?php

namespace Drupal\Tests\remotedb_sso\Functional;

use Drupal\Tests\remotedbuser\Functional\RemotedbUserBrowserTestBase;

/**
 * Base class for remotedb_sso tests.
 */
abstract class RemotedbSsoBrowserTestBase extends RemotedbUserBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedb',
    'remotedb_test',
    'remotedbuser',
    'remotedbuser_test',
    'remotedb_sso',
    'remotedb_sso_test',
  ];

  /**
   * The SSO url generator.
   *
   * @var \Drupal\remotedb_sso\UrlInterface
   */
  protected $urlGenerator;

  /**
   * The SSO ticket service.
   *
   * @var \Drupal\remotedb_sso\TicketInterface
   */
  protected $ticketService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a dummy remote database and set this one as the the one used by remotedbuser
    // module.
    $remotedb = $this->createRemotedb();
    \Drupal::configFactory()->getEditable('remotedbuser.settings')
      ->set('remotedb', $remotedb->id())
      ->save();

    $this->urlGenerator = $this->container->get('remotedb_sso.url');
    $this->ticketService = $this->container->get('remotedb_sso.ticket');
  }

}
