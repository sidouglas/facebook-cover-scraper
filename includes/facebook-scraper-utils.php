<?php

class FacebookScraperUtils
{

  /**
   * @param $url
   *
   * @return bool|string
   */
  public static function curl_file($url)
  {

    $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';

    // create curl resource
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // $output contains the output string
    $output = curl_exec($ch);

    curl_close($ch);

    return $output;
  }

  public static function valid_url($url)
  {

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    /* Get the HTML or whatever is linked in $url. */
    curl_exec($handle);
    /* Check for 404 (file not found). */
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);

    return $httpCode <= 302;
  }

  /**
   * @param $url string
   * @param $saveto string
   */
  public static function save_remote_file($url, $saveto)
  {

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    $raw = curl_exec($ch);
    curl_close($ch);

    if (file_exists($saveto)) {
      unlink($saveto);
    }

    $fp = fopen($saveto, 'x');

    fwrite($fp, $raw);

    fclose($fp);

    list($width, $height) = getimagesize($saveto);

    if ($width) {

      $now = date_timestamp_get(date_create());

      $updated_file_name = self::supplant($saveto, ['width' => $width, 'height' => $height, 'timestamp' => $now]);

      rename($saveto, $updated_file_name);

      return $updated_file_name;
    } else {
      unlink($saveto);
    }

    return false;
  }


  /**
   * supplant
   * Replaces %tokens% inside a string with an array key/value
   *
   * @param $string :string,
   * @param $array :array
   *
   * @return string
   */
  static public function supplant($string, $array, $strip = true)
  {
    $merged = array_merge(array_fill_keys(array_keys($array), ''), $array);
    $keys = array_map(function ($key) {
      return '%' . $key . '%';
    }, array_keys($merged));

    $return = str_replace($keys, $merged, $string);
    if ($strip) {
      return preg_replace('/%.*?(%)/', '', $return);
    }

    return $return;
  }

  public static function html5_attributes($args)
  {
    $output = '';
    foreach ($args as $key => $value) {
      if (is_string($key)) {
        if (!$value) {
          continue;
        }
        $output .= $key . '="' . htmlspecialchars($value) . '" ';
      } else {
        $output .= $value . ' ';
      }
    }

    return $output;
  }

}
