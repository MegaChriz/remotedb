<?php

declare(strict_types=1);

namespace Drupal\remotedbuser\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Entity hook implementations for remotedbuser.
 */
class RemotedbuserEntityHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() !== 'user') {
      return;
    }
    $fields['remotedb_uid'] = BaseFieldDefinition::create('integer')
      ->setName('remotedb_uid')
      ->setTargetEntityTypeId('user')
      ->setProvider('remotedbuser')
      ->setLabel($this->t('Remote database UID'))
      ->setDescription($this->t('The ID of the user in the remote database.'))
      ->setDefaultValue(0);
  }

}
