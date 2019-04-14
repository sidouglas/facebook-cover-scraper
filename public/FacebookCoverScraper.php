<?php

class FacebookCoverScraper
{

  private $name = '';
  private $plugin_name = 'facebook-public-cover';
  private $target_tld = 'https://www.facebook.com/';

  protected static $instance = null;

  protected function __construct()
  {
  }

  protected function __clone()
  {
  }

  public static function getInstance()
  {
    if (!isset(static::$instance)) {
      static::$instance = new static;
    }

    return static::$instance;
  }

  public static function run()
  {
    $instance = self::getInstance();
    add_shortcode('fb_public_cover', [$instance, 'shortcode']);
  }

  public static function get_info($image)
  {
    $file_name = pathinfo(basename($image))['filename'];
    $parts = explode('--', $file_name);
    $name = $parts[0];

    list($width, $height, $timestamp) = explode('-', $parts[1]);

    return [
      'height' => $height,
      'name' => $name,
      'src' => $image,
      'timestamp' => $timestamp,
      'width' => $width,
    ];
  }

  public function shortcode($params, $content = null)
  {
    $expiry = null;
    shortcode_atts([
      'alt' => '',
      'class' => '',
      'expiry' => '',
      'height' => '',
      'href' => '',
      'rel' => '',
      'slug' => '',
      'title' => '',
      'width' => '',
      '_target' => '',
    ], $params);

    if ($params['slug']) {
      $this->set_name($params['slug']);
      unset($params['slug']);
    } else {
      Facebook_Admin_Notice::display('Supply a slug to scrape');

      return;
    }

    if ($params['expiry']) {
      $expiry = $params['expiry'];
      unset($params['expiry']);
    }
    echo $this->get_public_image($this->get_name(), $expiry, $params);
  }

  /**
   * @todo if there's more than one image, then this just returns the largest by default
   * @param $slug
   * @param null $expiry
   * @param array $attrs
   * @return mixed
   * @throws Exception
   */
  public function get_public_image($slug, $expiry = null, $attrs = [])
  {

    if ($src = $this->get_public_cover_src($slug, $expiry)) {

      $attrs = apply_filters('fbcs_public_image_attr', array_merge($this->get_info($src[0]), $attrs));
      $anchor_attrs = [];
      $image_attrs = [];
      $img_template = '<img %img_template%  />';

      foreach ($attrs as $key => $value) {
        if (in_array($key, ['href', 'target', 'rel'])) {
          $anchor_attrs[$key] = $value;
        } else if (!in_array($key, ['timestamp', 'name'])) {
          $image_attrs[$key] = $value;
        }
      }

      $image_attrs = apply_filters('fbcs_get_public_image_attrs', $image_attrs);
      $anchor_attrs = apply_filters('fbcs_get_public_anchor_attrs', $anchor_attrs);

      if (count($anchor_attrs)) {
        $anchor_attrs = FacebookScraperUtils::html5_attributes($anchor_attrs);
        $img_template = FacebookScraperUtils::supplant('<a %anchor_template%><img %img_template% /></a>', ['anchor_template' => $anchor_attrs], false);
      }

      $rendered = FacebookScraperUtils::supplant($img_template, ['img_template' => FacebookScraperUtils::html5_attributes($image_attrs)]);

      return apply_filters('fbcs_get_public_image', $rendered, $image_attrs, $anchor_attrs);
    }
  }

  /**
   * @param $url
   * @param null $expiry
   *
   * @return array|string
   * @throws Exception
   */
  public function get_public_cover_src($slug, $expiry = null)
  {
    $this->set_name($slug);
    $this->refresh_saved_images($expiry);

    if ($src = $this->get_public_cover_file_path()) {
      if (is_array($src)) {
        return array_map(function ($image) {
          return wp_upload_dir()['baseurl'] . '/' . $this->plugin_name . '/' . basename($image);
        }, $src);
      } else {
        return wp_upload_dir()['baseurl'] . '/' . $this->plugin_name . '/' . basename($src);
      }
    }

    return [];
  }

  /**
   * @return array|bool
   */
  protected function get_public_cover_file_path()
  {
    $src = [];
    if (count($src = $this->has_scraped()) == 0) {
      $src = $this->scrape_fb();
    }
    if ($src) {
      return count($src) == 1 ? $src[0] : $src;
    }
    Facebook_Admin_Notice::display('Looks like that supplied slug is invalid or you\'re trying to save a video');
    return false;
  }

  /**
   *
   * @param $url string
   * @param null $expiry
   *
   * @return bool
   * @throws Exception
   */
  protected function refresh_saved_images($expiry = null)
  {
    if (!$expiry) {
      return false;
    }

    if ($images = $this->has_scraped()) {
      $compare = new DateTime('-' . $expiry);

      // we don't know what the user has entered in as $expiry param so bail
      if (!$compare) {
        return false;
      }

      $file_date = new DateTime();
      $file_date->setTimestamp($this->get_info($images[0])['timestamp']);

      if ($compare >= $file_date) {
        return $this->delete_files();
      }
    }

    return false;
  }

  /**
   * delete_files
   * clears all images in the directory
   * @param $file : string
   * @return bool
   */
  protected function delete_files()
  {
    if (FacebookScraperUtils::valid_url($this->get_url())) {
      foreach (glob($this->get_wp_upload_dir() . '*.{jpg,jpeg,png,gif}', GLOB_BRACE) as $image) {
        wp_delete_file($image);
      }
      return true;
    }
    return false;
  }

  protected function get_name()
  {
    return $this->name;
  }

  protected function set_name($name)
  {
    $this->name = strtolower(basename($name));
  }

  protected function get_url()
  {
    return $this->target_tld . $this->get_name();
  }

  protected function get_wp_upload_dir()
  {
    $path = wp_upload_dir()['basedir'] . '/' . $this->plugin_name;
    wp_mkdir_p($path);

    return trailingslashit($path);
  }

  /**
   * has_scraped
   * @return array - full filepath
   */
  protected function has_scraped()
  {
    $images = [];
    foreach (glob($this->get_wp_upload_dir() . '*.{jpg,jpeg,png,gif}', GLOB_BRACE) as $image) {
      if (strpos($image, $this->get_name()) !== false) {
        $images[] = $image;
      }
    }
    if (count($images)) {
      // sort files by file size - largest to smallest
      usort($images, function ($a, $b) {
        return filesize($b) - filesize($a);
      });
      $images = apply_filters('fbcs_has_scraped', $images);
    }
    return $images;
  }

  protected function scrape_fb()
  {

    $have_image = false;

    if (!FacebookScraperUtils::valid_url($this->get_url())) {
      return false;
    }

    $content = FacebookScraperUtils::curl_file($this->get_url());

    $cover_url = $this->extract_cover_url($content);

    if ($cover_url) {
      $have_image = $this->normalize_string($cover_url);
    }

    if ($have_image) {
      $path = $this->get_wp_upload_dir() . "{$this->get_name()}--%width%-%height%-%timestamp%.jpg";
      FacebookScraperUtils::save_remote_file($have_image, apply_filters('fbcs_scrape_success', $path, $this->get_name()));
      return $this->has_scraped();
    }

    return false;
  }

  /**
   * extract_cover_url
   * here is where the fun begins.
   *
   * @param $content
   *
   * @return bool|string
   */
  protected function extract_cover_url($content)
  {
    $explode_on = false;
    $regex = null;

    if (strpos($content, 'coverPhotoData') !== false) {
      $regex = '/uri"\s*:\s*\"([^"]+)/';
      $explode_on = 'coverPhotoData';
    } else if (strpos($content, 'coverPhotoImg') !== false) {
      $regex = '/src=\"([^"]+)/';
      $explode_on = 'coverPhotoImg';
    }
    if ($explode_on) {
      $parts = explode($explode_on, $content);
      preg_match($regex, $parts[1], $matches);

      if (is_array($matches)) {
        return htmlspecialchars_decode($matches[1]);
      }
    }

    return false;
  }

  protected function normalize_string($string)
  {
    return str_replace('\/', '/', $string);
  }
}



