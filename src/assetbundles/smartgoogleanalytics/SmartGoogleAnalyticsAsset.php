<?php
/**
 * Smart Google Analytics plugin for Craft CMS 3.x
 *
 * Smart Google Analytics
 *
 * @link      https://www.zealousweb.com
 * @copyright Copyright (c) 2021 zealousweb
 */

namespace zealouswebcraftcms\smartgoogleanalytics\assetbundles\smartgoogleanalytics;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * SmartGoogleAnalyticsAsset AssetBundle
 *
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application.
 * The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php)
 * of the class representing it.
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle
 * with a view, all its dependent asset bundles will be automatically registered.
 *
 * http://www.yiiframework.com/doc-2.0/guide-structure-assets.html
 *
 * @author    zealousweb
 * @package   SmartGoogleAnalytics
 * @since     1.0.0
 */
class SmartGoogleAnalyticsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@zealouswebcraftcms/smartgoogleanalytics/assetbundles/smartgoogleanalytics/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        //when this asset bundle is registered
        $this->js = [
            'js/charts.js',
            'js/moment.min.js',
            'js/daterangepicker.min.js',
        ];

        $this->css = [
            'css/SmartGoogleAnalytics.css',
            'css/daterangepicker.min.css',
        ];

        parent::init();
    }
}
