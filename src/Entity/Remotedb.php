<?php

namespace Drupal\remotedb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;

/**
 * Defines the remote database entity type.
 *
 * @ConfigEntityType(
 *   id = "remotedb",
 *   label = @Translation("Remote database"),
 *   label_collection = @Translation("Remote databases"),
 *   label_singular = @Translation("remote database"),
 *   label_plural = @Translation("remote databases"),
 *   label_count = @PluralTranslation(
 *     singular = "@count remote database",
 *     plural = "@count remote databases",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\remotedb\RemoteDbListBuilder",
 *     "form" = {
 *       "add" = "Drupal\remotedb\Form\RemotedbAddForm",
 *       "edit" = "Drupal\remotedb\Form\RemotedbEditForm",
 *       "delete" = "Drupal\remotedb\Form\RemotedbDeleteForm"
 *     },
 *   },
 *   admin_permission = "remotedb.administer",
 *   config_prefix = "remotedb",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/remotedb/manage/{userprotect_rule}",
 *     "delete-form" = "/admin/config/services/remotedb/manage/{userprotect_rule}/delete"
 *   }
 * )
 */
class Remotedb extends ConfigEntityBase implements RemotedbInterface {

  // ---------------------------------------------------------------------------
  // PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * The URL of the remote database.
   *
   * @var string
   */
  protected $url;

  /**
   * The unique identifier for this remote database.
   *
   * @var string
   */
  protected $name;

  /**
   * The administrative name of this remote database.
   *
   * @var string
   */
  protected $label;

  /**
   * Configured authentication methods for this remote database.
   *
   * @var array
   */
  protected $authentication_methods = [];

  /**
   * An array of option to send along with the HTTP Request.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Whether or not the authentication process has run.
   *
   * @var bool
   */
  protected $authenticated = FALSE;

  // ---------------------------------------------------------------------------
  // GETTERS
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['authentication_methods' => $this->getAuthenticationMethods()];
  }

  /**
   * Returns available authentication methods.
   *
   * @todo replace with plugin collection.
   */
  public function getAuthenticationMethods() {
    return [];
    $methods = array();
    $method_info = remotedb_discover_plugins();
    foreach ($method_info as $key => $method_definition) {
      $class = $method_definition['class'];
      $config = isset($this->authentication_methods[$key]) ? $this->authentication_methods[$key] : array();
      $method = new $class($config, $method_definition, $this);
      $methods[$method->getPluginId()] = $method;
      uasort($methods, array($this, 'pluginSort'));
    }
    return $methods;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader($header) {
    if (isset($this->options['headers'][$header])) {
      return $this->options['headers'][$header];
    }
    return NULL;
  }

  // ---------------------------------------------------------------------------
  // SETTERS
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function setHeader($header, $value) {
    if (!is_null($value)) {
      $this->options['headers'][$header] = $value;
    }
    else {
      unset($this->options['headers'][$header]);
    }
  }

  // ---------------------------------------------------------------------------
  // ACTION
  // ---------------------------------------------------------------------------

  /**
   * Authenticates to the XML-RPC server.
   */
  public function authenticate() {
    $this->authenticated = FALSE;
    $methods = $this->getAuthenticationMethods();
    foreach ($methods as $method) {
      if ($method->status) {
        $result = $method->authenticate();
        if (!$result) {
          return FALSE;
        }
      }
    }
    $this->authenticated = TRUE;
  }

  /**
   * Send a request to the XML-RPC server.
   *
   * @todo update for D8.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   The result of the request.
   * @throws \Drupal\remotedb\Exception\RemotedbException
   *   In case of errors during the request.
   */
  public function sendRequest($method, array $params = []) {
    if (!$this->authenticated) {
      $this->authenticate();
    }

    $args = [$method => $params];
    // Call XML-RPC.
    $result = xmlrpc($this->url, $args, $this->options);
    if ($result === FALSE) {
      $error = xmlrpc_error();
      // Throw exception in case of errors.
      if (is_object($error) && !empty($error->is_error)) {
        throw new RemotedbException($error->message, $error->code);
      }
    }
    return $result;
  }

}
