<?php

namespace Drupal\remotedb\Component;

/**
 * String related helper functions.
 */
class StringLib {

  /**
   * Converts text to array.
   *
   * @param string $text
   *   The string to convert.
   *
   * @return array
   *   An array of parameters that can be used.
   */
  public function textToArray($text) {
    $explode = explode("\n", $text);
    $array = [];
    // Trim all params.
    foreach ($explode as $index => $value) {
      $key = $index;
      $this->textToArrayParse($key, $value);
      $array[$key] = $value;
    }
    return $array;
  }

  /**
   * Parses a single array value.
   */
  private function textToArrayParse(&$key, &$value) {
    $value = trim($value);
    if (strpos($value, '|') !== FALSE) {
      $paramparts = explode('|', $value);
      $key = $paramparts[0];
      $value = $paramparts[1];
    }
    $value = trim($value);
    $regex = '/^array\((.+)\)$/i';
    if (preg_match($regex, $value)) {
      $sValues = preg_replace($regex, '${1}', $value);
      $aValues = explode(',', $sValues);
      $value = [];
      foreach ($aValues as $index => $sValuePart) {
        $this->textToArrayParse($index, $sValuePart);
        $value[$index] = $sValuePart;
      }
    }
  }

}
