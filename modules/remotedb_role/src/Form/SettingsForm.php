<?php

namespace Drupal\remotedb_role\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\remotedb\Entity\RemotedbStorageInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures remotedb_role settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module information provider.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $extensionList;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ExtensionList $extension_list
   *   Module information provider.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, EntityTypeManagerInterface $entity_type_manager, ExtensionList $extension_list) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityTypeManager = $entity_type_manager;
    $this->extensionList = $extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remotedb_role_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['remotedb_role.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('remotedb_role.settings');
    $debug_enabled = (bool) $config->get('debug');

    $form['remotedb'] = [
      '#type' => 'select',
      '#options' => $this->getRemotedbStorage()->options(),
      '#title' => $this->t('Database'),
      '#required' => TRUE,
      '#description' => $this->t('The remote database.'),
      '#default_value' => $config->get('remotedb'),
    ];

    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    $role_names = [];
    foreach ($roles as $rid => $role) {
      $role_names[$rid] = Html::cleanCssIdentifier((string) $rid);
    }

    $form['role_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['remotedb_role/remote_role_settings'],
        'drupalSettings' => [
          'remotedb_roles' => $role_names,
          'remotedb_role_image_enabled' => $this->extensionList->getPath('remotedb_role') . '/images/enabled.svg',
          'remotedb_role_image_disabled' => $this->extensionList->getPath('remotedb_role') . '/images/disabled.svg',
        ],
      ],
    ];

    $form['roles'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    if (count($roles) > 0) {
      foreach ($roles as $rid => $role) {
        $form['roles'][$rid] = [
          '#type' => 'details',
          '#title' => $role->label(),
          '#open' => TRUE,
          '#group' => 'role_settings',
          '#tree' => TRUE,
        ];

        // Enable/Disable.
        $form['roles'][$rid]['status'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enabled'),
          '#default_value' => $config->get('roles.' . $rid . '.status'),
          '#description' => $this->t('Enable or disable this role for automatic role assignment via remote database.'),
        ];

        $subscriptions = $config->get('roles.' . $rid . '.subscriptions');
        if (!is_array($subscriptions)) {
          $subscriptions = [];
        }
        $subscription_lines = [];
        foreach ($subscriptions as $subscription) {
          if (is_scalar($subscription)) {
            $subscription_lines[] = (string) $subscription;
          }
        }
        $form['roles'][$rid]['subscriptions'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Subscriptions'),
          '#description' => $this->t('Specify which subscriptions should give the user the role %role. Enter one per line.', [
            '%role' => $role->label(),
          ]),
          '#default_value' => $subscription_lines !== [] ? implode("\n", $subscription_lines) : NULL,
        ];
      }
    }

    // Extra settings.
    $form['extra'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Extra'),
      '#collapsible' => TRUE,
      '#collapsed' => !$debug_enabled,
    ];

    // Debug settings.
    $form['extra']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('If enabled, a message about which roles were assigned/unassigned will be displayed when users login.'),
      '#default_value' => $debug_enabled,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    foreach ($values['roles'] as &$role_values) {
      if (isset($role_values['subscriptions']) && is_string($role_values['subscriptions']) && trim($role_values['subscriptions']) !== '') {
        $role_values['subscriptions'] = explode("\n", $role_values['subscriptions']);
        $role_values['subscriptions'] = array_map('trim', $role_values['subscriptions']);
      }
      else {
        $role_values['subscriptions'] = [];
      }
    }

    $this->config('remotedb_role.settings')
      ->set('remotedb', $values['remotedb'])
      ->set('roles', $values['roles'])
      ->set('debug', $values['debug'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the remotedb storage handler.
   */
  protected function getRemotedbStorage(): RemotedbStorageInterface {
    $storage = $this->entityTypeManager->getStorage('remotedb');
    if (!$storage instanceof RemotedbStorageInterface) {
      throw new \LogicException('Expected remotedb storage to implement RemotedbStorageInterface.');
    }
    return $storage;
  }

}
