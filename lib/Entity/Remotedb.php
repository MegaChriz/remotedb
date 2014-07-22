<?php

/**
 * Contains Drupal\remotedb\Entity\Remotedb.
 */

namespace Drupal\remotedb\Entity;

use \Entity;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;

class Remotedb extends Entity implements RemotedbInterface {
  // ---------------------------------------------------------------------------
  // PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * The URL of the remote database.
   *
   * @var string
   * @access private
   */
  private $url;

  /**
   * The unique identifier for this remote database.
   *
   * @var string
   * @access private
   */
  private $name;

  /**
   * The administrative name of this remote database.
   *
   * @var string
   * @access private
   */
  private $label;

  /**
   * Configured authentication methods for this remote database.
   *
   * @var array
   */
  protected $authentication_methods = array();

  /**
   * An array of option to send along with the HTTP Request.
   *
   * @var array
   * @access private
   */
  private $options;

  /**
   * Whether or not the authentication process has run.
   *
   * @var bool
   * @access private
   */
  private $authenticated;

  // ---------------------------------------------------------------------------
  // CONSTRUCT
  // ---------------------------------------------------------------------------

  /**
   * Remotedb object constructor.
   *
   * @return \Drupal\remotedb\Entity\RemoteDB.
   */
  public function __construct(array $values = array(), $entityType = NULL) {
    if (is_null($entityType)) {
      $entityType = 'remotedb';
    }
    $this->options = array();
    $this->authenticated = FALSE;
    parent::__construct($values, $entityType);
  }

  // ---------------------------------------------------------------------------
  // GETTERS
  // ---------------------------------------------------------------------------

  /**
   * Implements RemotedbInterface::id().
   */
  public function id() {
    return $this->identifier();
  }

  /**
   * Magic getter.
   *
   * @return mixed
   *   Property or field values.
   */
  public function __get($property) {
    if (isset($this->$property)) {
      return $this->$property;
    }
  }

  /**
   * Magic method for giving back if property exists or not.
   *
   * @return bool
   *   TRUE if the property exists.
   *   FALSE otherwise.
   */
  public function __isset($property) {
    return isset($this->$property);
  }

  /**
   * Overrides \Entity::label().
   */
  public function label() {
    return $this->label;
  }

  /**
   * Implements RemotedbInterface::getUrl().
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Returns available authentication methods.
   *
   * @todo Don't call a procedural function here.
   */
  public function getAuthenticationMethods() {
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
   * Returns all options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Gets a header.
   *
   * @param string $header
   *   The header to get.
   *
   * @return mixed
   *   The header's value if it exists.
   *   NULL otherwise.
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
   * Magic setter.
   *
   * @return void
   */
  public function __set($property, $value) {
    switch ($property) {
      default:
        $this->$property = $value;
    }
  }

  /**
   * Sets a header.
   *
   * @param string $header
   *   The header to set.
   * @param mixed $value
   *   The header's value.
   *
   * @return void
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
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   The result of the request.
   * @throws Drupal\remotedb\Exception\RemotedbException
   *   In case of errors during the request.
   */
  public function sendRequest($method, array $params = array()) {
    if (!$this->authenticated) {
      $this->authenticate();
    }

    $args = array($method => $params);
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

  /**
   * Sorts authentication plugins by weight.
   */
  public function pluginSort($a, $b) {
    $a_weight = (isset($a->weight)) ? $a->weight : 0;
    $b_weight = (isset($b->weight)) ? $b->weight : 0;
    if ($a_weight == $b_weight) {
      return 0;
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }
}
