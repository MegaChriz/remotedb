<?php

namespace Drupal\remotedbuser\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\remotedb\Entity\RemotedbStorageInterface;
use Drupal\remotedbuser\RemotedbUserAuthenticationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures remotedbuser settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The storage class for remote database entities.
   *
   * @var \Drupal\remotedb\Entity\RemotedbStorageInterface
   */
  protected $remotedbStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\remotedb\Entity\RemotedbStorageInterface $remotedb_storage
   *   The storage class for remote database entities.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RemotedbStorageInterface $remotedb_storage, EntityFieldManagerInterface $field_manager) {
    parent::__construct($config_factory);
    $this->remotedbStorage = $remotedb_storage;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('remotedb'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remotedbuser_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['remotedbuser.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('remotedbuser.settings');

    $form['remotedb'] = [
      '#type' => 'select',
      '#options' => $this->remotedbStorage->options(),
      '#title' => $this->t('Database'),
      '#required' => TRUE,
      '#description' => $this->t('The remote database.'),
      '#default_value' => $config->get('remotedb'),
    ];
    $form['login'] = [
      '#type' => 'radios',
      '#title' => $this->t('Login settings'),
      '#required' => TRUE,
      '#options' => [
        RemotedbUserAuthenticationInterface::REMOTEONLY => $this->t('Use remote service only.'),
        RemotedbUserAuthenticationInterface::REMOTEFIRST => $this->t('Use remote service first, local user database is fallback.'),
        RemotedbUserAuthenticationInterface::LOCALFIRST => $this->t('Use local user database first, remote is fallback.'),
      ],
      '#default_value' => $config->get('login'),
    ];

    $sync_properties_options = [];
    foreach ($this->fieldManager->getFieldStorageDefinitions('user') as $key => $definition) {
      switch ($key) {
        case 'uid':
        case 'remotedb_uid':
          // Never sync these properties.
          break;

        default:
          $sync_properties_options[$key] = $definition->getLabel();
          break;
      }
    }
    // Add password as option as well.
    $form['sync_properties'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Synchronize properties'),
      '#description' => $this->t('Select which properties to sync.'),
      '#options' => $sync_properties_options,
      '#default_value' => $config->get('sync_properties'),
      'name' => ['#disabled' => TRUE, '#value' => 'name'],
      'mail' => ['#disabled' => TRUE, '#value' => 'mail'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('remotedbuser.settings')
      ->set('remotedb', $values['remotedb'])
      ->set('login', $values['login'])
      ->set('sync_properties', $values['sync_properties'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
