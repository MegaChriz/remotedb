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
    $remotedb = $this->entity;

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'remotedb/drupal.remotedb.admin';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $remotedb->label(),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this->remoteDbStorage, 'load'],
      ],
      '#default_value' => $remotedb->id(),
      '#required' => TRUE,
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#maxlength' => 255,
      '#default_value' => $remotedb->getUrl(),
    ];

    // Status.
    $form['authentication_methods']['status'] = [
      '#type' => 'item',
      '#title' => $this->t('Enabled authentication methods'),
      '#prefix' => '<div id="authentication-methods-status-wrapper">',
      '#suffix' => '</div>',
      // This item is used as a pure wrapping container with heading. Ignore its
      // value, since 'authentication methods' should only contain
      // authentication method definitions.
      // See https://www.drupal.org/node/1829202.
      '#input' => FALSE,
    ];
    // Order (tabledrag).
    $form['authentication_methods']['order'] = [
      '#type' => 'table',
      // For remotedb.admin.js.
      '#attributes' => ['id' => 'authentication-method-order'],
      '#title' => $this->t('Authentication method processing order'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'authentication-method-order-weight',
        ],
      ],
      '#tree' => FALSE,
      '#input' => FALSE,
      '#theme_wrappers' => ['form_element'],
    ];
    // Settings.
    $form['authentication_method_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Authentication method settings'),
    ];

    $methods = $remotedb->getAuthenticationMethods();
    foreach ($methods as $name => $method) {
      $form['authentication_methods']['status'][$name] = [
        '#type' => 'checkbox',
        '#title' => $method->getLabel(),
        '#default_value' => $method->status,
        '#parents' => ['authentication_methods', $name, 'status'],
        '#description' => $method->getDescription(),
        '#weight' => $method->weight,
      ];

      $form['authentication_methods']['order'][$name]['#attributes']['class'][] = 'draggable';
      $form['authentication_methods']['order'][$name]['#weight'] = $method->weight;
      $form['authentication_methods']['order'][$name]['authentication_method'] = [
        '#markup' => $method->getLabel(),
      ];
      $form['authentication_methods']['order'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $method->getLabel()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $method->weight,
        '#parents' => ['authentication_methods', $name, 'weight'],
        '#attributes' => ['class' => ['authentication-method-order-weight']],
      ];

      // Retrieve the settings form of the plugin.
      $settings_form = [
        '#parents' => ['authentication_methods', $name, 'settings'],
        '#tree' => TRUE,
      ];
      $settings_form = $method->settingsForm($settings_form, $form_state);
      if (!empty($settings_form)) {
        $form['authentication_methods']['settings'][$name] = [
          '#type' => 'details',
          '#title' => $method->getLabel(),
          '#open' => TRUE,
          '#weight' => $method->weight,
          '#parents' => ['authentication_methods', $name, 'settings'],
          '#group' => 'authentication_method_settings',
        ];
        $form['authentication_methods']['settings'][$name] += $settings_form;
      }
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Add the submitted form values to the entity, and save it.
    $remotedb = $this->entity;
    foreach ($form_state->getValues() as $key => $value) {
      switch ($key) {
        case 'authentication_methods':
          foreach ($value as $instance_id => $config) {
            $remotedb->setAuthenticationMethodConfig($instance_id, $config);
          }
          break;

        default:
          $remotedb->set($key, $value);
      }
    }
    $remotedb->save();

    return $this->entity;
  }

}
