<?php

namespace Drupal\remotedb_sso_test;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\remotedb_sso\TicketServiceInterface;
use Drupal\remotedbuser\Entity\RemotedbUserInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;

/**
 * A mocked ticket service to be used in functional tests.
 */
class MockTicketService implements TicketServiceInterface {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The password generator.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MockTicketService object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface $password_generator
   *   The password generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(TimeInterface $time, PasswordGeneratorInterface $password_generator, EntityTypeManagerInterface $entity_type_manager) {
    $this->time = $time;
    $this->passwordGenerator = $password_generator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicket(AccountInterface $account): string {
    $uid = 0;
    $remotedb_uid = $account->remotedb_uid->value ?? NULL;
    if (is_numeric($remotedb_uid) && (int) $remotedb_uid > 0) {
      $uid = (int) $remotedb_uid;
    }

    return implode('/', [
      $uid,
      $this->time->getRequestTime(),
      $this->passwordGenerator->generate(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateTicket(int|string $remotedb_uid, int $timestamp, string $hash): ?RemotedbUserInterface {
    $storage = $this->entityTypeManager->getStorage('remotedb_user');
    if (!$storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException('Expected remotedb_user storage to implement RemotedbUserStorageInterface.');
    }
    return $storage->loadBy($remotedb_uid, RemotedbUserStorageInterface::BY_ID);
  }

}
