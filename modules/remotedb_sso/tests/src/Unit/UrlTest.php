<?php

namespace Drupal\Tests\remotedb_sso\Unit;

use Drupal\remotedb_sso\Url;
use Drupal\Tests\UnitTestCase;

/**
 * Test if SSO urls are handled as expected.
 *
 * @coversDefaultClass \Drupal\remotedb_sso\Url
 * @group remotedb_sso
 */
class UrlTest extends UnitTestCase {

  /**
   * @covers ::createSsoGotoUrl
   *
   * @dataProvider urlDataProvider
   */
  public function testCreateSsoGotoUrl(array $sites, $text, $expected) {
    foreach ($sites as $site) {
      $url = new Url();
      $text = $url->createSsoGotoUrl($site, $text);
    }
    $this->assertEquals($expected, $text);
  }

  /**
   * Data provider for testCreateSsoGotoUrl().
   *
   * @see ::testCreateSsoGotoUrl()
   */
  protected function urlDataProvider() {
    $sites = [
      'www.example.com',
      'www.example2.com/subsite',
    ];

    return [
      [
        'sites' => $sites,
        'text' => 'A text with no link at all.',
        'expected' => 'A text with no link at all.',
      ],
      [
        'sites' => $sites,
        'text' => 'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
        'expected' => 'You need to be at <a href="@base?site=www.example.com">.',
      ],
      [
        'sites' => $sites,
        'text' => 'You need to be at <a href="http://www.example.com">.',
        'expected' => 'You need to be at <a href="@base?site=www.example.com">.',
      ],
      [
        'sites' => $sites,
        'text' => 'There is a place at <a href="http://www.example2.com/subsite/place">.',
        'expected' => 'There is a place at <a href="@base?site=www.example2.com/subsite&path=place">.',
      ],
      [
        'sites' => $sites,
        'text' => 'Dolors are at <a href="http://www.example2.com/subsite">.',
        'expected' => 'Dolors are at <a href="@base?site=www.example2.com/subsite">.',
      ],
      [
        'sites' => $sites,
        'text' => '<a href="http://www.example2.com/subsite/subpath?path=Amen">Amen.</a>',
        'expected' => '<a href="@base?site=www.example2.com/subsite&path=subpath%3Fpath%3DAmen">Amen.</a>',
      ],
    ];
  }

}
