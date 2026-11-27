<?php
/**
 * UnusualDope Uduny Module Registration
 *
 * This file registers the module with Magento's component system.
 * It's the first file Magento looks for when discovering modules.
 *
 * @category  UnusualDope
 * @package   UnusualDope_Uduny
 */

use Magento\Framework\Component\ComponentRegistrar;

/**
 * Register the module
 *
 * Parameters:
 * 1. ComponentRegistrar::MODULE - tells Magento this is a module (not a theme, library, or language pack)
 * 2. 'UnusualDope_Uduny' - the module name in format: VendorName_ModuleName
 * 3. __DIR__ - the absolute path to this module's directory
 */
ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'UnusualDope_Uduny',
    __DIR__
);

