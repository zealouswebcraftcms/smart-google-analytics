<?php
/**
 * Smart Google Analytics plugin for Craft CMS 3.x
 *
 * Smart Google Analytics
 *
 * @link      https://www.zealousweb.com
 * @copyright Copyright (c) 2021 zealousweb
 */

namespace zealouswebcraftcms\smartgoogleanalytics;
require realpath(dirname(__DIR__)) . "/vendor/autoload.php";
use zealouswebcraftcms\smartgoogleanalytics\models\Settings;
use zealouswebcraftcms\smartgoogleanalytics\models\Views;
use zealouswebcraftcms\smartgoogleanalytics\records\CraftRecords;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use yii\base\Event;
use craft\web\View;
use yii\helpers\Url;
use yii\data\Pagination;
use yii\widgets\LinkPager;
use yii\data\Sort;
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterComponentTypesEvent;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    zealousweb
 * @package   SmartGoogleAnalytics
 * @since     1.0.0
 *
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class SmartGoogleAnalytics extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * SmartGoogleAnalytics::$plugin
     *
     * @var SmartGoogleAnalytics
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * SmartGoogleAnalytics::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
                function(RegisterUrlRulesEvent $event) {
                $event->rules['smart-google-analytics'] = 'smart-google-analytics/default/list';
                $event->rules['smart-google-analytics/views/add'] = 'smart-google-analytics/view/display';
                $event->rules['smart-google-analytics/views/add'] = 'smart-google-analytics/view/views';
                $event->rules['views-details'] = 'smart-google-analytics/view/details'; 
                $event->rules['views-properties'] = 'smart-google-analytics/view/properties'; 
                $event->rules['views-profiles'] = 'smart-google-analytics/view/profiles';
                $event->rules['views-save'] = 'smart-google-analytics/view/save';
                $event->rules['views-list'] = 'smart-google-analytics/default/filter-search';  
                $event->rules['views/delete/<viewId:\d+>'] = 'smart-google-analytics/view/delete-view'; 
                $event->rules['views/edit/<viewId:\d+>'] = 'smart-google-analytics/view/edit-view';  
                $event->rules['get-chart-data'] = 'smart-google-analytics/default/get-chart-data'; 
            }
        );
     
        //Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['smart-google-analytics1'] = 'smart-google-analytics/index';
            }
        );

        /* Creating Tabs for Setting Page */
        Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (TemplateEvent $e) {
            if (
                $e->template === 'settings/plugins/_settings' &&
                $e->variables['plugin'] === $this
            ) {
                // Add the tabs
                $e->variables['tabs'] = [
                    ['label' => 'Settings', 'url' => '#settings-tab-settings'],
                    ['label' => 'View', 'url' => '#settings-tab-View'],
                ];
            }
        });
        
        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'smart-google-analytics',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
        
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */

    protected function settingsHtml(): string
    {
        // Get and pre-validate the settings
        $settings = $this->getSettings();
        $request = Craft::$app->getRequest();
        $query = CraftRecords::find();

        /* pagination on view listing */
        $pagination = new Pagination(['totalCount' => $query->count(), 'pageSize'=>10, 'params' => array_merge($_GET, ['#' => 'settings-tab-View']),]);
        $pager = LinkPager::widget([
            'pagination' => $pagination,
        ]);
         
        /* Sorting on view listing */
        $sort = new Sort([
			'attributes' => [
                'Chartname' => [
                    'asc' => ['chartName' => SORT_ASC],
                    'desc' => ['chartName' => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => 'Chart Name',
                ],
                'Charttype' => [
                    'asc' => ['chartType' => SORT_ASC],
                    'desc' => ['chartType' => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => 'Chart Type',
                ],
                'Order' => [
                    'asc' => ['order' => SORT_ASC],
                    'desc' => ['order' => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => 'Order',
                ],
                'Status' => [
                    'asc' => ['status' => SORT_ASC],
                    'desc' => ['status' => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => 'Status',
                ],
                'Metric' => [
                    'asc' => ['metricsKey' => SORT_ASC],
                    'desc' => ['metricsKey' => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => 'Metric',
                ],
                'Dimension' => [
                    'asc' => ['dimensionKey' => SORT_ASC],
                    'desc' => ['dimensionKey' => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => 'Dimension',
                ],
                
			],
            'params' => array_merge($_GET, ['#' => 'settings-tab-View']),
		]);
        
        $googleIconUrl = dirname(__DIR__);
        $redirect_uri = UrlHelper::actionUrl('smart-google-analytics/default/callback');
        $googleIcon = Craft::$app->assetManager->getPublishedUrl($googleIconUrl.'/resources/img/google.png', true);
        $data = $query->asArray()->offset($pagination->offset)->limit($pagination->limit)->orderBy($sort->orders)->all();
        $chartname = $sort->link('Chartname');
        $charttype = $sort->link('Charttype');
        $order = $sort->link('Order');
        $status = $sort->link('Status');
        $metric= $sort->link('Metric');
        $dimension= $sort->link('Dimension');
        
        return Craft::$app->view->renderTemplate('smart-google-analytics/settings', [
            'settings' => $settings,
            'redirect_uri' => $redirect_uri,
            'googleIcon' => $googleIcon,
            'data' => $data,
            'Chartname' => $chartname,
            'Charttype' => $charttype,
            'Order' => $order,
            'Status' => $status,
            'Metric' => $metric,
            'Dimension' => $dimension,
            'pager'=>  $pager,
        ]);
    }

    public function getCpNavItem()
    {
        if(Craft::$app->getSession()->get("google_user_access_token") == '') {
            return;
        }     
        $navItem = parent::getCpNavItem();
        $navItem['label'] = Craft::t('smart-google-analytics', 'Smart Google Analytics');
        return $navItem;
    }

    public function afterSaveSettings()
    {
        parent::afterSaveSettings();
        Craft::$app->response
            ->redirect(UrlHelper::url('settings/plugins/smart-google-analytics'))
            ->send();
    } 
   
}

