<?php

namespace Drupal\remotedbuser\Entity\Query;

use Drupal\Core\Entity\Query\ConditionBase;

/**
 * Dummy condition class.
 */
class Condition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function compile($query) {}

}
