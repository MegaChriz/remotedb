<?php

namespace Drupal\remotedbuser\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\user\UserInterface;

/**
 * Defines the remote user entity type.
 *
 * @ContentEntityType(
 *   id = "remotedb_user",
 *   label = @Translation("Remote user"),
 *   label_collection = @Translation("Remote users"),
 *   label_singular = @Translation("remote user"),
 *   label_plural = @Translation("remote users"),
 *   label_count = @PluralTranslation(
 *     singular = "@count remote user",
 *     plural = "@count remote users",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\remotedbuser\Entity\RemotedbUserStorage",
 *   },
 *   static_cache = FALSE,
 *   persistent_cache = FALSE,
 * )
 */
class RemotedbUser extends ContentEntityBase implements RemotedbUserInterface {

  /**
   * {@inheritdoc}
   */
  public function id(): string|int|null {
    return $this->uid;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The entity values as an array.
   */
  public function toArray(): array {
    $values = $this->values;

    // Don't send attached account along.
    unset($values['account']);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function toAccount(): UserInterface {
    $storage = $this->entityTypeManager()->getStorage($this->entityTypeId);
    if (!$storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException('Expected remotedb_user storage to implement RemotedbUserStorageInterface.');
    }
    return $storage->toAccount($this);
  }

}
