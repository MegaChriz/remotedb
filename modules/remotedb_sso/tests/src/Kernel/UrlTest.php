<?php

namespace Drupal\Tests\remotedb_sso\Kernel;

use Drupal\remotedb_sso\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test if SSO urls are handled as expected.
 *
 * @coversDefaultClass \Drupal\remotedb_sso\Url
 * @group remotedb_sso
 */
class UrlTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'remotedb_sso',
    'filter',
  ];

  /**
   * @covers ::createSsoGotoUrl
   *
   * @param array $sites
   *   The sites to replace urls for in the texts.
   * @param string $text
   *   The original text.
   * @param string $expected
   *   The expected result text after generating the sso urls.
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
  public function urlDataProvider() {
    $sites = [
      'www.example.com',
      'www.example2.com/subsite',
    ];

    return [
      'no link' => [
        'sites' => $sites,
        'text' => 'A text with no link at all.',
        'expected' => 'A text with no link at all.',
      ],
      'url' => [
        'sites' => $sites,
        'text' => 'You need to be at <a href="http://www.example.com">.',
        'expected' => 'You need to be at <a href="http://localhost/sso/goto?site=www.example.com">.',
      ],
      'url with path' => [
        'sites' => $sites,
        'text' => 'Go to <a href="http://www.example.com/lorem"> for Lorem Ipsum.',
        'expected' => 'Go to <a href="http://localhost/sso/goto?site=www.example.com&path=lorem"> for Lorem Ipsum.',
      ],
      'suburl' => [
        'sites' => $sites,
        'text' => 'Dolors are at <a href="http://www.example2.com/subsite">.',
        'expected' => 'Dolors are at <a href="http://localhost/sso/goto?site=www.example2.com/subsite">.',
      ],
      'suburl with path' => [
        'sites' => $sites,
        'text' => 'There is a place at <a href="http://www.example2.com/subsite/place">.',
        'expected' => 'There is a place at <a href="http://localhost/sso/goto?site=www.example2.com/subsite&path=place">.',
      ],
      'suburl with path and query' => [
        'sites' => $sites,
        'text' => '<a href="http://www.example2.com/subsite/subpath?path=Amen">Amen.</a>',
        'expected' => '<a href="http://localhost/sso/goto?site=www.example2.com/subsite&path=subpath%3Fpath%3DAmen">Amen.</a>',
      ],
    ];
  }

}
