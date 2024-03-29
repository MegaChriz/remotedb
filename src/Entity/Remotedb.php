<?php

namespace Drupal\remotedb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\remotedb\AuthenticationPluginCollection;
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
 *     "list_builder" = "Drupal\remotedb\RemotedbListBuilder",
 *     "storage" = "Drupal\remotedb\Entity\RemotedbStorage",
 *     "access" = "Drupal\remotedb\RemotedbAccessControlHandler",
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
 *     "edit-form" = "/admin/config/services/remotedb/manage/{remotedb}",
 *     "delete-form" = "/admin/config/services/remotedb/manage/{remotedb}/delete"
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "url",
 *     "authentication_methods",
 *   }
 * )
 */
class Remotedb extends ConfigEntityBase implements RemotedbInterface, EntityWithPluginCollectionInterface {

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
   * The URL of the remote database.
   *
   * @var string
   */
  protected $url;

  /**
   * Configured authentication methods for this remote database.
   *
   * @var array
   */
  protected $authentication_methods = [];

  /**
   * An array of headers to send along with the HTTP Request.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * Whether or not the authentication process has run.
   *
   * @var bool
   */
  protected $authenticated = FALSE;

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
  public function getAuthenticationMethods($instance_id = NULL) {
    if (!isset($this->authenticationCollection)) {
      $this->authenticationCollection = new AuthenticationPluginCollection(\Drupal::service('plugin.manager.remotedb.authentication'), $this->authentication_methods, $this);
      $this->authenticationCollection->sort();
    }
    if (isset($instance_id)) {
      return $this->authenticationCollection->get($instance_id);
    }
    return $this->authenticationCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['authentication_methods' => $this->getAuthenticationMethods()];
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthenticationMethodConfig($instance_id, array $configuration) {
    $this->authentication_methods[$instance_id] = $configuration;
    if (isset($this->authenticationCollection)) {
      $this->authenticationCollection->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader($header) {
    if (isset($this->headers[$header])) {
      return $this->headers[$header];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($header, $value) {
    if (!is_null($value)) {
      $this->headers[$header] = $value;
    }
    else {
      unset($this->headers[$header]);
    }
  }

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
   * Sends a request to the XML-RPC server.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   The result of the request.
   *
   * @throws \Drupal\remotedb\Exception\RemotedbException
   *   In case of errors during the request.
   */
  public function sendRequest($method, array $params = []) {
    if (!$this->authenticated) {
      $this->authenticate();
    }

    $args = [$method => $params];
    // Call XML-RPC.
    $result = xmlrpc($this->url, $args, $this->headers);
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
