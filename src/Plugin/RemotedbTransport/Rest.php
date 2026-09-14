<?php

namespace Drupal\remotedb\Plugin\RemotedbTransport;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\remotedb\Attribute\RemotedbTransport;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb\Plugin\TransportBase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Sends requests to D11 REST resources (and core login/CSRF routes).
 *
 * Verb, path and parameter placement come from RestMethodMapper, not from
 * naming conventions on the XML-RPC method string.
 */
#[RemotedbTransport(
  id: 'rest',
  title: new TranslatableMarkup('REST'),
  description: new TranslatableMarkup('Connect using Drupal REST (JSON) resources.'),
  settings: [
    'prefix' => '',
  ]
)]
class Rest extends TransportBase {

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Method-to-route mapper.
   *
   * @var \Drupal\remotedb\Plugin\RemotedbTransport\RestMethodMapper
   */
  protected $mapper;

  /**
   * Constructs a new Rest transport.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database entity.
   * @param \GuzzleHttp\ClientInterface|null $http_client
   *   HTTP client. Defaults to the http_client service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RemotedbInterface $remotedb, ?ClientInterface $http_client = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $remotedb);
    $this->httpClient = $http_client ?? \Drupal::httpClient();
    $this->mapper = new RestMethodMapper();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $prefix = $this->configuration['prefix'] ?? '';
    if (!is_string($prefix)) {
      $prefix = '';
    }
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('REST path prefix'),
      '#description' => $this->t('Optional prefix prepended to REST paths. Leave empty when the remote paths already start with /rest and the URL is the site root.'),
      '#default_value' => $prefix,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest(string $method, array $params = []): mixed {
    $url = $this->remotedb->getUrl();
    if (!is_string($url) || $url === '') {
      throw new RemotedbException('The remote database URL is not set.');
    }

    try {
      $mapped = $this->mapper->map($method, $params);
    }
    catch (\InvalidArgumentException $e) {
      throw new RemotedbException($e->getMessage(), 0, $e);
    }

    $request_url = $this->buildUrl($url, $mapped);
    $options = [
      'headers' => $this->buildHeaders($mapped['response'] === 'token'),
      'http_errors' => FALSE,
      'allow_redirects' => FALSE,
      'timeout' => 30,
    ];
    if ($mapped['body'] !== NULL && $mapped['http_method'] !== 'GET') {
      $options['json'] = $mapped['body'];
    }

    try {
      $response = $this->httpClient->request($mapped['http_method'], $request_url, $options);
    }
    catch (\Throwable $e) {
      throw new RemotedbException($e->getMessage(), (int) $e->getCode(), $e);
    }

    $status = $response->getStatusCode();
    $raw = (string) $response->getBody();
    if ($status >= 400) {
      throw new RemotedbException($this->errorMessage($raw, $status), $status);
    }

    return $this->decode($mapped['response'], $raw, $response);
  }

  /**
   * Builds the absolute request URL.
   *
   * @param string $base_url
   *   Remotedb URL (site root for REST).
   * @param array{path: string, query: array<string, mixed>} $mapped
   *   Mapped request.
   */
  protected function buildUrl(string $base_url, array $mapped): string {
    $prefix = '';
    if (isset($this->configuration['prefix']) && is_string($this->configuration['prefix'])) {
      $prefix = rtrim($this->configuration['prefix'], '/');
    }
    $url = rtrim($base_url, '/') . $prefix . $mapped['path'];
    if ($mapped['query'] !== []) {
      $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($mapped['query']);
    }
    return $url;
  }

  /**
   * Builds request headers from the remotedb cookie/CSRF state.
   *
   * @param bool $plain_token
   *   TRUE when requesting /session/token as text/plain.
   *
   * @return array<string, string>
   *   HTTP headers.
   */
  protected function buildHeaders(bool $plain_token): array {
    $headers = [];
    foreach ($this->remotedb->getHeaders() as $name => $value) {
      if (!is_string($name) || !is_scalar($value)) {
        continue;
      }
      $headers[$name] = (string) $value;
    }
    if ($plain_token) {
      $headers['Accept'] = 'text/plain';
    }
    else {
      $headers += [
        'Accept' => 'application/json',
      ];
    }
    return $headers;
  }

  /**
   * Decodes a successful response.
   */
  protected function decode(string $type, string $raw, ResponseInterface $response): mixed {
    return match ($type) {
      'token' => ['token' => trim($raw)],
      'session' => $this->decodeSession($raw, $response),
      default => $this->decodeJson($raw),
    };
  }

  /**
   * Decodes JSON, preserving false/true/null.
   */
  protected function decodeJson(string $raw): mixed {
    if ($raw === '') {
      return NULL;
    }
    try {
      return json_decode($raw, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      throw new RemotedbException('The REST response was not valid JSON: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Normalizes core user.login.http into the D7 session_name/sessid shape.
   *
   * Login plugins store Cookie as session_name=sessid. Core login returns
   * that in Set-Cookie, not in the JSON body.
   */
  protected function decodeSession(string $raw, ResponseInterface $response): array|false {
    $session_name = NULL;
    $sessid = NULL;
    foreach ($response->getHeader('Set-Cookie') as $cookie) {
      if (preg_match('/^(S?SESS[0-9a-zA-Z]+)=([^;]+)/', $cookie, $matches) === 1) {
        $session_name = $matches[1];
        $sessid = $matches[2];
        break;
      }
    }
    if ($session_name === NULL || $sessid === NULL) {
      return FALSE;
    }
    $json = [];
    if ($raw !== '') {
      try {
        $decoded = json_decode($raw, TRUE, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
          $json = $decoded;
        }
      }
      catch (\JsonException) {
        $json = [];
      }
    }
    return [
      'session_name' => $session_name,
      'sessid' => $sessid,
      'csrf_token' => $json['csrf_token'] ?? NULL,
      'current_user' => $json['current_user'] ?? NULL,
    ];
  }

  /**
   * Extracts an error message from a failed REST response.
   */
  protected function errorMessage(string $raw, int $status): string {
    if ($raw !== '') {
      try {
        $decoded = json_decode($raw, TRUE, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])) {
          return $decoded['message'];
        }
      }
      catch (\JsonException) {
        // Fall through to the raw body.
      }
      $trimmed = trim($raw);
      if ($trimmed !== '') {
        return $trimmed;
      }
    }
    return sprintf('REST request failed with status %d.', $status);
  }

}
