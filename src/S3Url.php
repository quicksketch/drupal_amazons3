<?php

namespace Drupal\amazons3;

use Guzzle\Http\QueryString;
use Guzzle\Http\Url;

/**
 * Represents an s3:// stream URL.
 *
 * @class S3Url
 * @package Drupal\amazons3
 */
class S3Url extends Url {

  /**
   * Override __construct() to default scheme to s3.
   *
   * @param string $bucket
   *   The bucket to use for the URL.
   * @param string $key
   *   (optional) Key for the URL.
   */
  public function __construct($bucket, $key = null) {
    if ($key) {
      $key = '/' . $key;
    }

    parent::__construct('s3', $bucket, null, null, null, $key);
  }


  /**
   * Return the bucket associated with the URL.
   *
   * @return string
   */
  public function getBucket() {
    return $this->getHost();
  }

  /**
   * Set the bucket.
   *
   * @param string $bucket
   */
  public function setBucket($bucket) {
    $this->setHost($bucket);
  }

  /**
   * Return the S3 object key.
   *
   * @return string
   */
  public function getKey() {
    // Remove the leading slash getPath() keeps in the path.
    return substr($this->getPath(), 1);
  }

  /**
   * Set the S3 object key.
   *
   * This automatically prepends a slash to the path.
   *
   * @param string $key
   */
  public function setKey($key) {
    $this->setPath('/' . $key);
  }

  /**
   * Set the path part of the URL.
   *
   * Since we are using these URLs in a non-HTTP context, we don't replace
   * spaces or question marks.
   *
   * @param array|string $path Path string or array of path segments
   *
   * @return Url
   */
  public function setPath($path) {
    if (is_array($path)) {
      $path = '/' . implode('/', $path);
    }

    $this->path = $path;

    return $this;
  }

  /**
   * Return the image style URL associated with this URL.
   *
   * @param string $styleName
   *   The name of the image style.
   *
   * @return \Drupal\amazons3\S3Url
   *   An image style URL.
   */
  public function getImageStyleUrl($styleName) {
    $styleUrl = new S3Url($this->getBucket());
    $styleUrl->setPath("/styles/$styleName/" . $this->getKey());
    return $styleUrl;
  }

  /**
   * Overrides factory() to support bucket configs.
   *
   * @param string $url
   *   Full URL used to create a Url object.
   * @param \Drupal\amazons3\StreamWrapperConfiguration $config
   *   (optional) Configuration to associate with this URL.
   *
   * @throws \InvalidArgumentException
   *   Thrown when $url cannot be parsed by parse_url().
   *
   * @return static
   *   An S3Url.
   */
  public static function factory($url, StreamWrapperConfiguration $config = NULL) {
    $defaults = array(
      'scheme' => 's3',
      'host' => $config ? $config->getBucket() : NULL,
      'path' => NULL,
      'port' => NULL,
      'query' => NULL,
      'user' => NULL,
      'pass' => NULL,
      'fragment' => NULL,
    );

    $parts = parse_url($url);
    if ($parts === FALSE) {
      throw new \InvalidArgumentException('Was unable to parse malformed url: ' . $url);
    }
    $parts += $defaults;

    // Image styles are constructed as
    // "s3://styles/[style_name]/s3/[bucket_name]/[path]".
    // Correct this path so that the bucket is the host, and reinsert "styles/"
    // as part of the path.
    if ($parts['host'] === 'styles') {
      $path_array = explode('/', substr($parts['path'], 1));
      $path = '/styles/';
      $path .= array_shift($path_array); // [style_name].
      array_shift($path_array); // "s3".
      $parts['host'] = array_shift($path_array); // [bucket_name].
      $path .= '/' . implode('/', $path_array); // Remainder of the path.
      $parts['path'] = $path;
    }

    return new static($parts['host'], substr($parts['path'], 1));
  }
}
