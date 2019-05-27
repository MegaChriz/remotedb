<?php

namespace Drupal\remotedb_sso;

/**
 * @coversDefaultClass \Drupal\remotedb_sso\Filter\SSO
 */
class SSOTest extends RemotedbSSOTestBase {

  /**
   *
   */
  public static function getInfo() {
    return [
      'name' => 'SSO: SSO Filter',
      'description' => 'Test if the SSO Filter works as expected.',
      'group' => 'Remote database',
    ];
  }

  /**
   * @covers ::processDefault
   */
  public function testProcessDefault() {
    // Set global websites.
    \Drupal::configFactory()->getEditable('remotedb_sso.settings')->set('remotedb_sso_websites', implode("\n", [
      'www.example.com',
      'www.example2.com/subsite',
    ]))->save();

    $texts = [
      'A text with no link at all.',
      'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
      'You need to be at <a href="http://www.example.com">.',
      'There is a place at <a href="http://www.example2.com/subsite/place">.',
      'Dolors are at <a href="http://www.example2.com/subsite">.',
      '<a href="http://www.example2.com/subsite/subpath?path=Amen">Amen.</a>',
    ];
    $base = $this->getAbsoluteUrl('sso/goto');
    $expected = [
      'A text with no link at all.',
      'Go to <a href="' . $base . '?site=www.example.com&path=lorem"> for Lorem Ipsum.',
      'You need to be at <a href="' . $base . '?site=www.example.com">.',
      'There is a place at <a href="' . $base . '?site=www.example2.com/subsite&path=place">.',
      'Dolors are at <a href="' . $base . '?site=www.example2.com/subsite">.',
      '<a href="' . $base . '?site=www.example2.com/subsite&path=subpath%3Fpath%3DAmen">Amen.</a>',
    ];

    foreach ($texts as $i => $text) {
      $this->assertEqual($expected[$i], SSO::processDefault($text));
    }
  }

  /**
   * @covers ::process
   */
  public function testProcess() {
    // Set global websites.
    \Drupal::configFactory()->getEditable('remotedb_sso.settings')->set('remotedb_sso_websites', implode("\n", [
      'www.example.com',
    ]))->save();

    // Create dummy filter.
    $filter = new stdClass();
    $filter->settings['websites'] = implode("\n", [
      'www.example2.com/subsite',
    ]);
    $sso_filter = new SSO($filter);

    $texts = [
      'A text with no link at all.',
      'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
      'You need to be at <a href="http://www.example.com">.',
      'There is a place at <a href="http://www.example2.com/subsite/place">.',
      'Dolors are at <a href="http://www.example2.com/subsite">.',
    ];
    $base = $this->getAbsoluteUrl('sso/goto');
    $expected = [
      'A text with no link at all.',
      'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
      'You need to be at <a href="http://www.example.com">.',
      'There is a place at <a href="' . $base . '?site=www.example2.com/subsite&path=place">.',
      'Dolors are at <a href="' . $base . '?site=www.example2.com/subsite">.',
    ];

    foreach ($texts as $i => $text) {
      $this->assertEqual($expected[$i], $sso_filter->process($text));
    }
  }

}
