<?php

namespace Drupal\Tests\flysystem_s3\Functional;

use Drupal\Tests\flysystem\Functional\ModuleInstallUninstallWebTest as Base;

/**
 * Tests module installation and uninstallation.
 *
 * @group flysystem_s3
 */
class ModuleInstallUninstallWebTest extends Base {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['flysystem_s3'];

}
