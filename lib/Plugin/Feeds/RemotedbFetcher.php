<?php

/**
 * @file
 * Contains \Drupal\remotedb\Plugin\Feeds\RemotedbFetcher.
 */

namespace Drupal\remotedb\Plugin\Feeds;

use Drupal\remotedb\Plugin\Feeds\RemotedbFetcherResult;
use \FeedsFetcher;

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
    if (!empty($config['override'])) {
      $url = $source_config['source'];
    }
    else {
      $url = RemoteDB::get()->getUrl();
      $source_config['source'] = $url;
      $source->setConfigFor($this, $source_config);
    }
    return new RemotedbFetcherResult($url, $config);
  }

  /**
   * Override parent::configDefaults().
   */
  public function configDefaults() {
    return array(
      'method' => '',
      'params' => '',
      'override' => FALSE,
    );
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {
    $form = array();
    $form['method'] = array(
      '#type' => 'textfield',
      '#title' => t('Method'),
      '#required' => TRUE,
      '#description' => t('The method to call.'),
      '#default_value' => $this->config['method'],
    );
    $form['params'] = array(
      '#type' => 'textarea',
      '#title' => t('Parameters'),
      '#description' => t('Specify the parameters to use, one on each line.'),
      '#default_value' => $this->config['params'],
    );

    $form['override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override'),
      '#description' => t('Allow the import form to override the values above.'),
      '#default_value' => $this->config['override'],
    );
    return $form;
  }

  /**
   * Expose source form.
   */
  public function sourceForm($source_config) {
    $form = array();
    if (!$this->config['override']) {
      return $form;
    }
    try {
      $form = array();
      $form['source'] = array(
        '#type' => 'textfield',
        '#value' => RemoteDB::get()->getUrl(),
      );
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    $form['method'] = array(
      '#type' => 'textfield',
      '#title' => t('Method'),
      '#required' => TRUE,
      '#description' => t('The method to call.'),
      '#default_value' => isset($source_config['method']) ? $source_config['method'] : '',
    );
    $form['params'] = array(
      '#type' => 'textarea',
      '#title' => t('Parameters'),
      '#description' => t('Specify the parameters to use, one on each line.'),
      '#default_value' => isset($source_config['params']) ? $source_config['params'] : '',
    );
    return $form;
  }
}