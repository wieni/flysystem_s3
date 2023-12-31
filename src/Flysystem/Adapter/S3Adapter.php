<?php

namespace Drupal\flysystem_s3\Flysystem\Adapter;

use Aws\S3\S3ClientInterface;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;
use League\Flysystem\Util;
use League\Flysystem\Util\MimeType;

/**
 * Overrides methods so it works with Drupal.
 */
class S3Adapter extends AwsS3Adapter {

  /**
   * {@inheritdoc}
   */
  public function __construct(S3ClientInterface $client, $bucket, $prefix = '', array $options = [], $streamReads = TRUE) {
    // In order to stat files by specifying non-streaming http option which has
    // become default setting as of league/flysystem-aws-s3-v3 version 1.0.25.
    // @see https://www.drupal.org/project/flysystem_s3/issues/3172969
    if (!isset($options['@http']['stream'])) {
      $options['@http']['stream'] = FALSE;
    }

    parent::__construct($client, $bucket, $prefix, $options, $streamReads);
  }

  /**
   * {@inheritdoc}
   */
  public function has($path) {
    $location = $this->applyPathPrefix($path);
    if ($this->s3Client->doesObjectExist($this->bucket, $location, $this->options)) {
      return TRUE;
    }
    if ($this->s3Client->doesObjectExist($this->bucket, $location . '/') === TRUE) {
      return TRUE;
    }
    else {
      return $this->doesDirectoryExist($location);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($path) {
    $metadata = parent::getMetadata($path);

    if ($metadata === FALSE) {
      return [
        'type' => 'dir',
        'path' => $path,
        'timestamp' => \Drupal::time()->getRequestTime(),
        'visibility' => AdapterInterface::VISIBILITY_PUBLIC,
      ];
    }

    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  protected function upload($path, $body, Config $config) {
    $key = $this->applyPathPrefix($path);
    $options = $this->getOptionsFromConfig($config);
    $acl = isset($options['ACL']) ? $options['ACL'] : 'private';

    if (!isset($options['ContentType'])) {
      if (is_string($body)) {
        $options['ContentType'] = Util::guessMimeType($path, $body);
      }
      else {
        $options['ContentType'] = MimeType::detectByFilename($path);
      }
    }

    if (!isset($options['ContentLength'])) {
      $options['ContentLength'] = is_string($body) ? Util::contentSize($body) : Util::getStreamSize($body);
    }

    $this->s3Client->upload($this->bucket, $key, $body, $acl, ['params' => $options]);

    return $this->normalizeResponse($options, $key);
  }

}
