<?php

namespace Drupal\remotedb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\remotedb\AuthenticationPluginCollection;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb\Plugin\AuthenticationInterface;

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
   * A collection of authentications.
   */
  protected ?AuthenticationPluginCollection $authenticationCollection;

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
  public function id(): string|int|null {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): ?string {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationMethods(): AuthenticationPluginCollection {
    if (!isset($this->authenticationCollection)) {
      $this->authenticationCollection = new AuthenticationPluginCollection(\Drupal::service('plugin.manager.remotedb.authentication'), $this->authentication_methods, $this);
      $this->authenticationCollection->sort();
    }
    return $this->authenticationCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationMethod(string $instance_id): AuthenticationInterface {
    return $this->getAuthenticationMethods()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections(): array {
    return ['authentication_methods' => $this->getAuthenticationMethods()];
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthenticationMethodConfig(string $instance_id, array $configuration): static {
    $this->authentication_methods[$instance_id] = $configuration;
    if (isset($this->authenticationCollection)) {
      $this->authenticationCollection->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader(string $header): mixed {
    if (isset($this->headers[$header])) {
      return $this->headers[$header];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders(): array {
    return $this->headers;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader(string $header, mixed $value): void {
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
  public function authenticate(): bool {
    $this->authenticated = FALSE;
    $methods = $this->getAuthenticationMethods();
    foreach ($methods as $method) {
      if (!$method instanceof AuthenticationInterface) {
        continue;
      }
      if ($method->getStatus()) {
        $result = $method->authenticate();
        if (!$result) {
          return FALSE;
        }
      }
    }
    $this->authenticated = TRUE;
    return TRUE;
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
  public function sendRequest(string $method, array $params = []): mixed {
    if (!$this->authenticated) {
      $this->authenticate();
    }

    $args = [$method => $params];
    // Call XML-RPC.
    $result = xmlrpc($this->url, $args, $this->headers);
    if ($result === FALSE) {
      $error = xmlrpc_error();
      // Throw exception in case of errors.
      if (
        is_object($error)
        && property_exists($error, 'is_error')
        && $error->is_error
        && property_exists($error, 'message')
        && property_exists($error, 'code')
      ) {
        throw new RemotedbException((string) $error->message, (int) $error->code);
      }
    }
    return $result;
  }

}
