<?php
/**
 * @file
 * Contains a Feeds plugin to fetch data from the remote database.
 */

/**
 * Result of FeedsHTTPFetcher::fetch().
 */
class FeedsRemoteDBFetcherResult extends FeedsFetcherResult {
  protected $url;
  protected $config;

  /**
   * Constructor.
   */
  public function __construct($url = NULL, $config) {
    $this->url = $url;
    $this->config = $config;
  }

  /**
   * Overrides FeedsFetcherResult::getRaw();
   */
  public function getRaw() {
    $oRemoteDB = RemoteDB::get($this->url);
    $oRemoteDB->sendRequest($this->config['method'], remotedb_text_to_params($this->config['params']));
    $result = $oRemoteDB->getResult();
    if (is_object($result) && isset($result->is_error) && $result->is_error == TRUE) {
      $variables = array(
        '@message' => $result->message,
        '@code' => $result->code,
      );
      throw new RemoteDBException(t('An error occured when fetching data from the remote database: @message (@code)', $variables));
    }
    return serialize($result);
  }
}

/**
 * Fetches data via HTTP.
 */
class FeedsRemoteDBFetcher extends FeedsFetcher {
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
    return new FeedsRemoteDBFetcherResult($url, $config);
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