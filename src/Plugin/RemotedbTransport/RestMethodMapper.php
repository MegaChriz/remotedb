<?php

namespace Drupal\remotedb\Plugin\RemotedbTransport;

/**
 * Maps D7 XML-RPC method names to D11 REST routes.
 *
 * Calling code still uses sendRequest('dbuser.save', [...]) etc. This table
 * is the only place that decides HTTP verb, path and where each parameter
 * goes. Do not infer that from the method name.
 */
final class RestMethodMapper {

  /**
   * Maps a method name and params to an HTTP request description.
   *
   * @param string $method
   *   XML-RPC style method name.
   * @param array $params
   *   Positional or named parameters as used by sendRequest().
   *
   * @return array{http_method: string, path: string, query: array<string, mixed>, body: mixed, response: string}
   *   Request description for the REST transport.
   *
   * @throws \InvalidArgumentException
   *   When the method is not mapped.
   */
  public function map(string $method, array $params): array {
    $definitions = self::definitions();
    if (!isset($definitions[$method])) {
      throw new \InvalidArgumentException(sprintf('No REST mapping for method "%s".', $method));
    }
    $definition = $definitions[$method];
    $named = $this->namedParams($definition, $params);

    $path = is_string($definition['path']) ? $definition['path'] : '';
    $http_method = is_string($definition['http_method']) ? $definition['http_method'] : 'GET';
    $query = [];
    $body = NULL;
    $param_list = $this->paramList($definition);

    foreach ($param_list as $param) {
      $name = $param['name'];
      if (!array_key_exists($name, $named)) {
        continue;
      }
      $value = $named[$name];
      switch ($param['in']) {
        case 'path':
          $path_value = is_scalar($value) ? (string) $value : '';
          $path = str_replace('{' . $name . '}', rawurlencode($path_value), $path);
          break;

        case 'query':
          $query[$name] = $value;
          break;

        case 'body':
          if (!is_array($body)) {
            $body = [];
          }
          $body[$name] = $value;
          break;
      }
    }

    if (isset($definition['query']) && is_array($definition['query'])) {
      foreach ($definition['query'] as $key => $value) {
        if (is_string($key)) {
          $query[$key] = $value;
        }
      }
    }

    if (($definition['body'] ?? NULL) === 'raw') {
      $body = $this->rawBody($params);
    }

    if (isset($definition['body_map']) && is_array($definition['body_map']) && is_array($body)) {
      $mapped = [];
      foreach ($definition['body_map'] as $from => $to) {
        if (is_string($from) && is_string($to) && array_key_exists($from, $body)) {
          $mapped[$to] = $body[$from];
        }
      }
      $body = $mapped + $body;
      foreach ($definition['body_map'] as $from => $to) {
        if (is_string($from) && is_string($to) && $from !== $to) {
          unset($body[$from]);
        }
      }
    }

    $response = $definition['response'] ?? 'json';
    if (!is_string($response)) {
      $response = 'json';
    }

    return [
      'http_method' => $http_method,
      'path' => $path,
      'query' => $query,
      'body' => $body,
      'response' => $response,
    ];
  }

  /**
   * Method-to-route mapping table.
   *
   * @return array<string, array<string, mixed>>
   *   Definitions keyed by XML-RPC method name.
   */
  public static function definitions(): array {
    return [
      'dbuser.retrieve' => [
        'http_method' => 'GET',
        'path' => '/rest/dbuser/{arg}',
        'params' => [
          ['name' => 'arg', 'in' => 'path'],
          ['name' => 'argtype', 'in' => 'query'],
        ],
      ],
      'dbuser.authenticate' => [
        'http_method' => 'POST',
        'path' => '/rest/dbuser/authenticate',
        'params' => [
          ['name' => 'username', 'in' => 'body'],
          ['name' => 'password', 'in' => 'body'],
        ],
      ],
      'dbuser.validatename' => [
        'http_method' => 'POST',
        'path' => '/rest/dbuser/validatename',
        'params' => [
          ['name' => 'name', 'in' => 'body'],
          ['name' => 'mail', 'in' => 'body'],
        ],
        'aliases' => [
          'username' => 'name',
        ],
      ],
      'dbuser.save' => [
        'http_method' => 'POST',
        'path' => '/rest/dbuser',
        'params' => [],
        'body' => 'raw',
      ],
      'dbsubscription.retrieve' => [
        'http_method' => 'GET',
        'path' => '/rest/dbsubscription/{arg}',
        'params' => [
          ['name' => 'arg', 'in' => 'path'],
          ['name' => 'argtype', 'in' => 'query'],
        ],
      ],
      'dbsubscription.create' => [
        'http_method' => 'POST',
        'path' => '/rest/dbsubscription',
        'params' => [
          ['name' => 'uid', 'in' => 'body'],
          ['name' => 'subscription_id', 'in' => 'body'],
          ['name' => 'expire', 'in' => 'body'],
        ],
      ],
      'dbsubscription.new' => [
        'http_method' => 'POST',
        'path' => '/rest/dbsubscription/new',
        'params' => [
          ['name' => 'abonnee', 'in' => 'body'],
          ['name' => 'subscription_id', 'in' => 'body'],
          ['name' => 'options', 'in' => 'body'],
        ],
      ],
      'ticket.retrieve' => [
        'http_method' => 'GET',
        'path' => '/rest/ticket/{arg}',
        'params' => [
          ['name' => 'arg', 'in' => 'path'],
          ['name' => 'argtype', 'in' => 'query'],
        ],
      ],
      'ticket.validate' => [
        'http_method' => 'POST',
        'path' => '/rest/ticket/validate',
        'params' => [
          ['name' => 'uid', 'in' => 'body'],
          ['name' => 'timestamp', 'in' => 'body'],
          ['name' => 'hash', 'in' => 'body'],
        ],
      ],
      'afasuser.retrieve' => [
        'http_method' => 'GET',
        'path' => '/rest/afasuser/{uid}',
        'params' => [
          ['name' => 'uid', 'in' => 'path'],
        ],
      ],
      'afasuser.getcontacts' => [
        'http_method' => 'POST',
        'path' => '/rest/afasuser/getcontacts',
        'params' => [
          ['name' => 'uid', 'in' => 'body'],
          ['name' => 'exclude_primary', 'in' => 'body'],
        ],
      ],
      'kkbservices_webhook.index' => [
        'http_method' => 'GET',
        'path' => '/rest/kkbservices_webhook',
        'params' => [],
      ],
      'kkbservices_webhook.create' => [
        'http_method' => 'POST',
        'path' => '/rest/kkbservices_webhook',
        'params' => [
          ['name' => 'url', 'in' => 'body'],
          ['name' => 'actions', 'in' => 'body'],
        ],
      ],
      'kkbservices_webhook.delete' => [
        'http_method' => 'DELETE',
        'path' => '/rest/kkbservices_webhook/{id}',
        'params' => [
          ['name' => 'id', 'in' => 'path'],
        ],
      ],
      // Core user.login.http — not a custom REST resource.
      'user.login' => [
        'http_method' => 'POST',
        'path' => '/user/login',
        'params' => [
          ['name' => 'username', 'in' => 'body'],
          ['name' => 'password', 'in' => 'body'],
        ],
        'body_map' => [
          'username' => 'name',
          'password' => 'pass',
        ],
        'response' => 'session',
        'query' => ['_format' => 'json'],
      ],
      // Core system.csrftoken — plaintext, not {token: ...}.
      'user.token' => [
        'http_method' => 'GET',
        'path' => '/session/token',
        'params' => [],
        'response' => 'token',
      ],
    ];
  }

  /**
   * Converts positional params to named params using the definition.
   *
   * @param array<string, mixed> $definition
   *   Method definition.
   * @param array $params
   *   Caller params.
   *
   * @return array<string, mixed>
   *   Named params.
   */
  protected function namedParams(array $definition, array $params): array {
    if ($params !== [] && !array_is_list($params)) {
      $named = $params;
    }
    else {
      $named = [];
      foreach ($this->paramList($definition) as $index => $param) {
        if (array_key_exists($index, $params)) {
          $named[$param['name']] = $params[$index];
        }
      }
    }
    if (isset($definition['aliases']) && is_array($definition['aliases'])) {
      foreach ($definition['aliases'] as $from => $to) {
        if (is_string($from) && is_string($to) && array_key_exists($from, $named) && !array_key_exists($to, $named)) {
          $named[$to] = $named[$from];
        }
      }
    }
    return $named;
  }

  /**
   * Returns typed param specs from a method definition.
   *
   * @param array<string, mixed> $definition
   *   Method definition.
   *
   * @return list<array{name: string, in: string}>
   *   Parameter specs.
   */
  protected function paramList(array $definition): array {
    if (!isset($definition['params']) || !is_array($definition['params'])) {
      return [];
    }
    $list = [];
    foreach ($definition['params'] as $param) {
      if (!is_array($param) || !isset($param['name'], $param['in'])) {
        continue;
      }
      if (!is_string($param['name']) || !is_string($param['in'])) {
        continue;
      }
      $list[] = [
        'name' => $param['name'],
        'in' => $param['in'],
      ];
    }
    return $list;
  }

  /**
   * Uses the first positional param, or the whole array, as JSON body.
   *
   * @param array $params
   *   Caller params.
   *
   * @return mixed
   *   Body payload.
   */
  protected function rawBody(array $params): mixed {
    if ($params !== [] && array_is_list($params)) {
      return $params[0];
    }
    return $params;
  }

}
