<?php

/**
 * @file
 * Contains \Drupal\remotedb_mock_test\Entity\MockRemotedb.
 */

namespace Drupal\remotedb_mock_test\Entity;

use Drupal\remotedb\Entity\RemotedbInterface;

class MockRemotedb implements RemotedbInterface {
  /**
   * {@inheritdoc}
   */
  public function label() {
    return 'Mock';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return 'http://www.example.com';
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader($header) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($header, $value) { }

  /**
   * {@inheritdoc}
   */
  public function save() { }

  /**
   * {@inheritdoc}
   */
  public function sendRequest($method, array $params = array()) {
    return $this;
  }
}
