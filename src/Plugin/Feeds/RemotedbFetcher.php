<?php

namespace Drupal\remotedb\Plugin\Feeds;

use FeedsFetcher;
use FeedsSource;
use FeedsNotExistingException;

/**
 * Fetches data via HTTP.
 */
class RemotedbFetcher extends FeedsFetcher {

  /**
   * Implements FeedsFetcher::fetch().
   */
  public function fetch(FeedsSource $source) {
    $source_config = $source->getConfigFor($this);
    $config = $source_config + $this->config;
    $remotedb = entity_load_single('remotedb', $config['remotedb']);
    if (empty($remotedb)) {
      throw new FeedsNotExistingException(t('Source configuration not valid.'));
    }
    return new RemotedbFetcherResult($remotedb, $config);
  }

  /**
   * Override parent::configDefaults().
   */
  public function configDefaults() {
    return [
      'remotedb' => NULL,
      'method' => '',
      'params' => '',
      'override' => FALSE,
    ];
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {
    $form = [];

    $form['remotedb'] = [
      '#type' => 'select',
      '#options' => entity_get_controller('remotedb')->options(),
      '#title' => t('Database'),
      '#required' => TRUE,
      '#description' => t('The remote database.'),
      '#default_value' => $this->config['remotedb'],
    ];

    $form['method'] = [
      '#type' => 'textfield',
      '#title' => t('Method'),
      '#required' => TRUE,
      '#description' => t('The method to call.'),
      '#default_value' => $this->config['method'],
    ];
    $form['params'] = [
      '#type' => 'textarea',
      '#title' => t('Parameters'),
      '#description' => t('Specify the parameters to use, one on each line.'),
      '#default_value' => $this->config['params'],
    ];

    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => t('Override'),
      '#description' => t('Allow the import form to override the values above.'),
      '#default_value' => $this->config['override'],
    ];

    return $form;
  }

  /**
   * Expose source form.
   */
  public function sourceForm($source_config) {
    $form = [];
    if (!$this->config['override']) {
      return $form;
    }

    // Merge source config with default config.
    $source_config += $this->config;
    $source_config += $this->configDefaults();

    if (empty($source_config['method'])) {
      foreach ($source_config as $key => $value) {
        if (empty($value) && isset($this->config[$key])) {
          $source_config[$key] = $this->config[$key];
        }
      }
    }

    $form['remotedb'] = [
      '#type' => 'select',
      '#options' => entity_get_controller('remotedb')->options(),
      '#title' => t('Database'),
      '#required' => TRUE,
      '#description' => t('The remote database.'),
      '#default_value' => $source_config['remotedb'],
    ];
    $form['method'] = [
      '#type' => 'textfield',
      '#title' => t('Method'),
      '#required' => TRUE,
      '#description' => t('The method to call.'),
      '#default_value' => $source_config['method'],
    ];
    $form['params'] = [
      '#type' => 'textarea',
      '#title' => t('Parameters'),
      '#description' => t('Specify the parameters to use, one on each line.'),
      '#default_value' => $source_config['params'],
    ];

    return $form;
  }

}
