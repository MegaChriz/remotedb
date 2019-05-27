<?php

namespace Drupal\remotedb_sso;

/**
 * @coversDefaultClass \Drupal\remotedb_sso\Url
 */
class UrlTest extends RemotedbSSOTestBase {

  /**
   *
   */
  public static function getInfo() {
    return [
      'name' => 'SSO: Url',
      'description' => 'Test if SSO urls are handled as expected.',
      'group' => 'Remote database',
    ];
  }

  /**
   * @covers ::createSSOGotoUrl
   */
  public function testCreateSSOGotoUrl() {
    $sites = [
      'www.example.com',
      'www.example2.com/subsite',
    ];

    $texts = [
      'A text with no link at all.',
      'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
      'You need to be at <a href="http://www.example.com">.',
      'There is a place at <a href="http://www.example2.com/subsite/place">.',
      'Dolors are at <a href="http://www.example2.com/subsite">.',
      '<a href="http://www.example2.com/subsite/subpath?path=Amen">Amen.</a>',
    ];
    // @FIXME
    // url() expects a route name or an external URI.
    // $base = url('sso/goto', ['absolute' => TRUE]);
    $expected = [
      'A text with no link at all.',
      'Go to <a href="' . $base . '?site=www.example.com&path=lorem"> for Lorem Ipsum.',
      'You need to be at <a href="' . $base . '?site=www.example.com">.',
      'There is a place at <a href="' . $base . '?site=www.example2.com/subsite&path=place">.',
      'Dolors are at <a href="' . $base . '?site=www.example2.com/subsite">.',
      '<a href="' . $base . '?site=www.example2.com/subsite&path=subpath%3Fpath%3DAmen">Amen.</a>',
    ];

    foreach ($texts as $i => $text) {
      foreach ($sites as $site) {
        $text = Url::createSSOGotoUrl($site, $text);
      }
      $this->assertEqual($expected[$i], $text);
    }
  }

}
