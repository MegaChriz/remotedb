<?php

namespace Drupal\remotedb\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for remote database add and edit forms.
 */
abstract class RemotedbFormBase extends EntityForm {

  /**
   * The remote database entity storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $remoteDbStorage;

  /**
   * Constructs a base class for remote database add and edit forms.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $remotedb_storage
   *   The remote database entity storage controller.
   */
  public function __construct(EntityStorageInterface $remotedb_storage) {
    $this->remoteDbStorage = $remotedb_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('remotedb')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this->remoteDbStorage, 'load'],
      ],
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getUrl(),
    ];

    return parent::form($form, $form_state);
  }

}
