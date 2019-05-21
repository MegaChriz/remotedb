<?php

namespace Drupal\remotedbuser\Entity;

use Drupal\Core\Entity\ContentEntityBase;

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
 * )
 */
class RemotedbUser extends ContentEntityBase implements RemotedbUserInterface {

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = $this->values;

    // Don't send attached account along.
    unset($values['account']);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function toAccount() {
    return $this->entityTypeManager()->getStorage($this->entityTypeId)->toAccount($this);
  }

}
