<?php

namespace Drupal\remotedb\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Entity\RemotedbStorageInterface;
use Drupal\remotedb\Plugin\AuthenticationInterface;

/**
 * Base form for remote database add and edit forms.
 */
abstract class RemotedbFormBase extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    if (!$this->entity instanceof RemotedbInterface) {
      throw new \LogicException(sprintf('Entity is not of the correct type. Should be a %s but it is a %s.', RemotedbInterface::class, get_class($this->entity)));
    }
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
        'exists' => [$this->getRemotedbStorage(), 'load'],
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
      if (!$method instanceof AuthenticationInterface) {
        continue;
      }
      $form['authentication_methods']['status'][$name] = [
        '#type' => 'checkbox',
        '#title' => $method->getLabel(),
        '#default_value' => $method->getStatus(),
        '#parents' => ['authentication_methods', $name, 'status'],
        '#description' => $method->getDescription(),
        '#weight' => $method->getWeight(),
      ];

      $form['authentication_methods']['order'][$name]['#attributes']['class'][] = 'draggable';
      $form['authentication_methods']['order'][$name]['#weight'] = $method->getWeight();
      $form['authentication_methods']['order'][$name]['authentication_method'] = [
        '#markup' => $method->getLabel(),
      ];
      $form['authentication_methods']['order'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $method->getLabel()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $method->getWeight(),
        '#parents' => ['authentication_methods', $name, 'weight'],
        '#attributes' => ['class' => ['authentication-method-order-weight']],
      ];

      // Retrieve the settings form of the plugin.
      $settings_form = [
        '#parents' => ['authentication_methods', $name, 'settings'],
        '#tree' => TRUE,
      ];
      $settings_form = $method->settingsForm($settings_form, $form_state);
      if ($settings_form !== []) {
        $form['authentication_methods']['settings'][$name] = [
          '#type' => 'details',
          '#title' => $method->getLabel(),
          '#open' => TRUE,
          '#weight' => $method->getWeight(),
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    // Add the submitted form values to the entity, and save it.
    if (!$this->entity instanceof RemotedbInterface) {
      throw new \LogicException(sprintf('Entity is not of the correct type. Should be a %s but it is a %s.', RemotedbInterface::class, get_class($this->entity)));
    }
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
