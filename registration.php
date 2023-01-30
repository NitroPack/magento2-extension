<?php
/**
 * @category  NitroPack.io
 * @package   NitroPack
 * @author    NitroPack.io
 * @copyright Copyright (c) NitroPack (https://nitropack.io)
 * @license   GNU GPL v2
 */

use \Magento\Framework\Component\ComponentRegistrar;

//require_once(__DIR__ . '/NitroPackSDK/autoload.php');

define('NITROPACK_INSTALL_DIR', __DIR__);

ComponentRegistrar::register(
	ComponentRegistrar::MODULE,
	'NitroPack_NitroPack',
	__DIR__
);
