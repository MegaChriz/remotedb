<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\Tests\Filter\SSOTest.
 */

namespace Drupal\remotedb_sso\Tests\Filter;

use stdClass;
use Drupal\remotedb_sso\Filter\SSO;
use Drupal\remotedb_sso\Tests\RemotedbSSOTestBase;

/**
 * @coversDefaultClass \Drupal\remotedb_sso\Filter\SSO
 */
class SSOTest extends RemotedbSSOTestBase {
  public static function getInfo() {
    return array(
      'name' => 'SSO: SSO Filter',
      'description' => 'Test if the SSO Filter works as expected.',
      'group' => 'Remote database',
    );
  }

  /**
   * @covers ::processDefault
   */
  public function testProcessDefault() {
    // Set global websites.
    variable_set('remotedb_sso_websites', implode("\n", array(
      'www.example.com',
      'www.example2.com/subsite',
    )));

    $texts = array(
      'A text with no link at all.',
      'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
      'You need to be at <a href="http://www.example.com">.',
      'There is a place at <a href="http://www.example2.com/subsite/place">.',
      'Dolors are at <a href="http://www.example2.com/subsite">.',
    );
    $base = $this->getAbsoluteUrl('sso/goto');
    $expected = array(
      'A text with no link at all.',
      'Go to <a href="' . $base . '/www.example.com/lorem"> for Lorem Ipsum.',
      'You need to be at <a href="' . $base . '/www.example.com">.',
      'There is a place at <a href="' . $base . '/www.example2.com/subsite/place">.',
      'Dolors are at <a href="' . $base . '/www.example2.com/subsite">.',
    );

    foreach ($texts as $i => $text) {
      $this->assertEqual($expected[$i], SSO::processDefault($text));
    }
  }

  /**
   * @covers ::process
   */
  public function testProcess() {
    // Set global websites.
    variable_set('remotedb_sso_websites', implode("\n", array(
      'www.example.com',
    )));

    // Create dummy filter.
    $filter = new stdClass();
    $filter->settings['websites'] = implode("\n", array(
      'www.example2.com',
    ));
    $sso_filter = new SSO($filter);

    $texts = array(
      'A text with no link at all.',
      'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
      'You need to be at <a href="http://www.example.com">.',
      'There is a place at <a href="http://www.example2.com/subsite/place">.',
      'Dolors are at <a href="http://www.example2.com/subsite">.',
    );
    $base = $this->getAbsoluteUrl('sso/goto');
    $expected = array(
      'A text with no link at all.',
      'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
      'You need to be at <a href="http://www.example.com">.',
      'There is a place at <a href="' . $base . '/www.example2.com/subsite/place">.',
      'Dolors are at <a href="' . $base . '/www.example2.com/subsite">.',
    );

    foreach ($texts as $i => $text) {
      $this->assertEqual($expected[$i], $sso_filter->process($text));
    }
  }
}
