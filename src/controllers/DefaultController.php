<?php
/**
 * Smart Google Analytics plugin for Craft CMS 3.x
 *
 * Smart Google Analytics
 *
 * @link      https://www.zealousweb.com
 * @copyright Copyright (c) 2021 zealousweb
 */

namespace zealouswebcraftcms\smartgoogleanalytics\controllers;

use Craft;
use zealouswebcraftcms\smartgoogleanalytics\SmartGoogleAnalytics;
use zealouswebcraftcms\smartgoogleanalytics\records\CraftRecords;
use zealouswebcraftcms\smartgoogleanalytics\models\Views;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use yii\web\Response;
use craft\web\Session;
use craft\web\View;
use yii\helpers\Json;
use craft\controllers\PluginsController;
use craft\helpers\Html;
use zealouswebcraftcms\smartgoogleanalytics\controllers\ViewController;
use Google_Client, Google_Service_Analytics, Google_Service_Exception;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    zealousweb
 * @package   SmartGoogleAnalytics
 * @since     1.0.0
 */
class DefaultController extends Controller
{
	protected $client;
    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/smart-google-analytics/default
     *
     * @return mixed
     */

	/* To Connect with Google Analytics Account */
	public function actionConnect(){	
		$settings = $this->actionSettingsData();
		$client = new Google_Client();
        $client->setClientId($settings->oauthClientId);
        $client->setClientSecret($settings->oauthClientSecret);
        $client->setRedirectUri(UrlHelper::actionUrl('smart-google-analytics/default/callback'));
		$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
		$client->setAccessType('offline'); 
		if(Craft::$app->getSession()->get("access_token") != '') {
			$client->setAccessToken(Craft::$app->getSession()->get("access_token"));
			$analytics = new Google_Service_Analytics($client);
			return Craft::$app->view->renderTemplate('smart-google-analytics/connect', [
				'setting_url' => UrlHelper::cpUrl('settings/plugins/smart-google-analytics'),
			]);
		} else {
			/* Generate a URL to request access from Google's OAuth 2.0 serve */
			$auth_url = $client->createAuthUrl();
			header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
			exit;
		}
    }

	/* Return back from Google Console Account */
    public function actionCallback($code){			
        $settings = $this->actionSettingsData();
		$client = new Google_Client();
		$client->setClientId($settings->oauthClientId);
        $client->setClientSecret($settings->oauthClientSecret);
		$client->setRedirectUri(UrlHelper::actionUrl('smart-google-analytics/default/callback'));
		$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
		$client->setApprovalPrompt('auto');
		$client->setAccessType('offline'); 
		$token = Craft::$app->getSession()->get("google_user_access_token");
		
		if(!isset($token) || $token == '') {
			$client->authenticate($code);
			$token = $client->getAccessToken();
			Craft::getLogger()->log($token, \yii\log\Logger::LEVEL_INFO, 'Return back from Google Console Account');

			Craft::$app->getSession()->set('google_user_access_token', $token);
			Craft::$app->getSession()->setNotice(Craft::t('smart-google-analytics', 'Connected to Google Analytics.'));	
		} else {
			$client->setAccessToken($token);
		}
		$analytics = new Google_Service_Analytics($client);
		$user_name = $this->actionGetUserName();
		Craft::$app->getSession()->set('user_name', $user_name);
		return Craft::$app->view->renderTemplate('smart-google-analytics/connect', [
			'setting_url' => UrlHelper::cpUrl('settings/plugins/smart-google-analytics'),
		]);
		exit;
    }

	/* To Disconnect with Google Analytics Account  */
	public function actionDisconnect(){
		$settings = $this->actionSettingsData();
		$session = Craft::$app->getSession()->get("google_user_access_token");
		$tokens = Craft::$app->getSession()->removeAll('google_user_access_token',$session);
		$tokens = Craft::$app->getSession()->remove('user_name');
		if($tokens){
			Craft::$app->getSession()->setError(Craft::t('smart-google-analytics', 'Couldn’t disconnect from Google Analytics'));
		}else{
			Craft::$app->getSession()->setNotice(Craft::t('smart-google-analytics', 'Disconnected from Google Analytics.'));
			return $this->renderTemplate('smart-google-analytics/disconnect', [
				'setting_url' => UrlHelper::cpUrl('settings/plugins/smart-google-analytics')
			]);
			exit;
		}
	}

	/* Get settings data  */
	public static function actionSettingsData() {
		$plugin = SmartGoogleAnalytics::getInstance();
		$settings = $plugin->getSettings();
		return $settings;
	}

	/* Get user name */
	protected function actionGetUserName(){
        // Get and pre-validate the settings
        $settings = $this->actionSettingsData();

        $client = new Google_Client();
        $client->setClientId($settings->oauthClientId);
        $client->setClientSecret($settings->oauthClientSecret);
        $client->setRedirectUri(UrlHelper::actionUrl('smart-google-analytics/default/callback'));
		$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
        if (Craft::$app->getSession()->get("google_user_access_token") != '') {
			$client->setAccessToken(Craft::$app->getSession()->get("google_user_access_token"));
			$analytics = new Google_Service_Analytics($client);
			$accounts = $analytics->management_accounts->listManagementAccounts();
        } else {
            $auth_url = $client->createAuthUrl();
			header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
			exit;
        }

        $username = (isset($accounts) && isset($accounts['username']) && $accounts['username'] != '') ? $accounts['username'] : '';
        
        return  $username;
    }

	/* Call Dashboard Page */
	public function actionList() {	
		$settings = $this->actionSettingsData();
		$analytics = $this->getAnalyticData();
		if($analytics == '') {
			Craft::$app->getSession()->setNotice(Craft::t('smart-google-analytics', 'Please Connect With Google Analytics.'));
			return Craft::$app->view->renderTemplate('smart-google-analytics/connect', [
				'setting_url' => UrlHelper::cpUrl('settings/plugins/smart-google-analytics'),
			]);
		}
		/* Get first account on dashboard */
		$accounts = $analytics->management_accounts->listManagementAccounts();
		if (count($accounts->getItems()) > 0) {
			$items = $accounts->getItems();
			$firstAccountId = $items[0]->getId();
		}

		/* Get first property based on account */
		$properties = $analytics->management_webproperties->listManagementWebproperties($firstAccountId);
		if(count($properties->getItems()) > 0)
		{
			$items = $properties->getItems();
			$firstPropertyId = $items[0]->getId();
		}

		/* Get first view based on account and property */
		if($firstAccountId != '' && $firstPropertyId != '') {
			$profiles = $analytics->management_profiles->listManagementProfiles($firstAccountId, $firstPropertyId);
			if(count($profiles->getItems()) > 0) 
			{
				$items = $profiles->getItems();
				$firstViewId =  $items[0]->getId();
			}
		}
		
		return $this->renderTemplate('smart-google-analytics/index', [
			'settings' => $settings,
			'accounts' => $accounts,
			'firstAccountId' => $firstAccountId,
			'properties' => $properties,
			'firstPropertyId' => $firstPropertyId,
			'profiles' => $profiles,
			'firstViewId' => $firstViewId,
		]);
	}

	/* Get chart data  */
	public function actionGetChartData($view_id = '',$search_text = '',$start_date = '',$end_date = ''){
		
		$analytics = $this->getAnalyticData();
		$view_records = CraftRecords::find();

		if($view_id != '') {
			$view_id = str_replace("ga:","",$view_id);
			$view_records = $view_records->where(['gaViewId' => $view_id]);
		}
		
		$search_text = trim($search_text);
        if($search_text != '') {
           $view_records = $view_records->andWhere(['like', 'chartName',  $_GET['search_text'] . '%', false ]);  
        }
		
		$view_records = $view_records->andWhere(['dateDeleted' => null]);
		$view_records = $view_records->andWhere(['status' => 'Active']);         
		$view_records = $view_records->asArray()->all();
		
		$array = $bar_data = $pie_data = $geo_data = $column_data = $line_data = [];

		foreach ($view_records as $key => $value) {
			if($value['chartType'] == 'BAR') {
				$array[$key]['chartType'] = $value['chartType'];
				$array[$key]['chartId'] = $value['id'];
				$array[$key]['chartName'] = $value['chartName'];
				$array[$key]['order'] = $value['order'];
				$array[$key]['dimensionKey'] = $value['dimensionKey'];
				$array[$key]['metricsKey'] = $value['metricsKey'];
				try {
					$array[$key]['chartData'] = $analytics->data_ga->get(
						'ga:' . $value['gaViewId'],
						$start_date,
						$end_date,
						$value['metricsValue'],
						array(
							'dimensions' => $value['dimensionValue'],
						)
					);
					if($array[$key]['chartData']){
						foreach($array[$key]['chartData'] as $key_cd => $value_cd){
							if(!isset($value_cd[1]) && $value_cd[1] == 0){
								$array[$key]['flag'] = 0;
							}else{
								$array[$key]['flag'] = 1;
							}
						}
					}else{
						echo "Notfound";
					}
				} catch (Google_Service_Exception $e) {
					$array[$key]['error'] = $e->getErrors()[0]['message'] ;
				}

			} elseif ($value['chartType'] == 'PIE') {
				$array[$key]['chartType'] = $value['chartType'];
				$array[$key]['chartId'] = $value['id'];
				$array[$key]['chartName'] = $value['chartName'];
				$array[$key]['order'] = $value['order'];
				$array[$key]['dimensionKey'] = $value['dimensionKey'];
				$array[$key]['metricsKey'] = $value['metricsKey'];
				try {
					$array[$key]['chartData'] = $analytics->data_ga->get(
						'ga:' . $value['gaViewId'],
						$start_date,
						$end_date,
						$value['metricsValue'],
						array(
							'dimensions' => $value['dimensionValue'],
						)
					);
					if($array[$key]['chartData']){
						foreach($array[$key]['chartData'] as $key_cd => $value_cd){	
							if(!isset($value_cd[1]) && $value_cd[1] == 0){
								$array[$key]['flag'] = 0;
							}else{
								$array[$key]['flag'] = 1;
							}
						}
					}else{
						echo "Notfound";
					}
				} catch (Google_Service_Exception $e) {
					$array[$key]['error'] = $e->getErrors()[0]['message'] ;
				}

			} elseif ($value['chartType'] == 'GEO') {
				$array[$key]['chartType'] = $value['chartType'];
				$array[$key]['chartId'] = $value['id'];
				$array[$key]['chartName'] = $value['chartName'];
				$array[$key]['order'] = $value['order'];
				$array[$key]['dimensionKey'] = $value['dimensionKey'];
				$array[$key]['metricsKey'] = $value['metricsKey'];
				try {
					$array[$key]['chartData'] = $analytics->data_ga->get(
						'ga:' . $value['gaViewId'], 
						$start_date,
						$end_date,
						$value['metricsValue'],
						array(
							'dimensions' => $value['dimensionValue'],
						)
					);
					if($array[$key]['chartData']){
						foreach($array[$key]['chartData'] as $key_cd => $value_cd){
							if(!isset($value_cd[1]) && $value_cd[1] == 0){
								$array[$key]['flag'] = 0;
							}else{
								$array[$key]['flag'] = 1;
							}
						}
					}else{
						echo "Notfound";
					}
				} catch (Google_Service_Exception $e) {
					$array[$key]['error'] = $e->getErrors()[0]['message'] ;
				}
				
			} elseif ($value['chartType'] == 'COLUMN') {
				$array[$key]['chartType'] = $value['chartType'];
				$array[$key]['chartId'] = $value['id'];
				$array[$key]['chartName'] = $value['chartName'];
				$array[$key]['order'] = $value['order'];
				$array[$key]['dimensionKey'] = $value['dimensionKey'];
				$array[$key]['metricsKey'] = $value['metricsKey'];
				try {
					$array[$key]['chartData'] = $analytics->data_ga->get(
						'ga:' . $value['gaViewId'],
						$start_date,
						$end_date,
						$value['metricsValue'],
						array(
							'dimensions' => $value['dimensionValue'],
						)
					);
					if($array[$key]['chartData']){
						foreach($array[$key]['chartData'] as $key_cd => $value_cd){
							if(!isset($value_cd[1]) && $value_cd[1] == 0){
								$array[$key]['flag'] = 0;
							}else{
								$array[$key]['flag'] = 1;
							}
						}
					}else{
						echo "Notfound";
					}
				} catch (Google_Service_Exception $e) {
					$array[$key]['error'] = $e->getErrors()[0]['message'] ;
				}
				
			} elseif ($value['chartType'] == 'LINE') {
				$array[$key]['chartType'] = $value['chartType'];
				$array[$key]['chartId'] = $value['id'];
				$array[$key]['chartName'] = $value['chartName'];
				$array[$key]['order'] = $value['order'];
				$array[$key]['dimensionKey'] = $value['dimensionKey'];
				$array[$key]['metricsKey'] = $value['metricsKey'];
				try {
					$array[$key]['chartData'] = $analytics->data_ga->get(
						'ga:' . $value['gaViewId'],
						$start_date,
						$end_date,
						$value['metricsValue'],
						array(
							'dimensions' => $value['dimensionValue'],
						)
					);
					if($array[$key]['chartData']){
						foreach($array[$key]['chartData'] as $key_cd => $value_cd){
							if(!isset($value_cd[1]) && $value_cd[1] == 0){
								$array[$key]['flag'] = 0;
							}else{
								$array[$key]['flag'] = 1;
							}
						}
					}else{
						echo "Notfound";
					}
				} catch (Google_Service_Exception $e) {
					$array[$key]['error'] = $e->getErrors()[0]['message'];
				}

			} elseif ($value['chartType'] == 'STAT') {
				$array[$key]['chartType'] = $value['chartType'];
				$array[$key]['chartId'] = $value['id'];
				$array[$key]['chartName'] = $value['chartName'];
				$array[$key]['order'] = $value['order'];
				$array[$key]['metricsKey'] = $value['metricsKey'];
				$array[$key]['startDate'] = date("jS \of F Y ", strtotime($start_date));
                $array[$key]['endDate'] = date("jS \of F Y ", strtotime($end_date));
				try {
					$array[$key]['chartData'] = $analytics->data_ga->get(
						'ga:' . $value['gaViewId'],
						$start_date,
						$end_date,
						$value['metricsValue'],
					);
				} catch (Google_Service_Exception $e) {
					$array[$key]['error'] = $e->getErrors()[0]['message'] ;
				}

			} elseif ($value['chartType'] == 'LIST') {
				$array[$key]['chartType'] = $value['chartType'];
				$array[$key]['chartId'] = $value['id'];
				$array[$key]['chartName'] = $value['chartName'];
				$array[$key]['order'] = $value['order'];
				$array[$key]['dimensionKey'] = $value['dimensionKey'];
				$array[$key]['metricsKey'] = $value['metricsKey'];
				try {
					$array[$key]['chartData'] = $analytics->data_ga->get(
						'ga:' . $value['gaViewId'],
						$start_date,
						$end_date,
						$value['metricsValue'],
						array(
							'dimensions' => $value['dimensionValue'],
						)
					);
					if($array[$key]['chartData']){
						foreach($array[$key]['chartData'] as $key_cd => $value_cd){
							if(!isset($value_cd[1]) && $value_cd[1] == 0){
								$array[$key]['flag'] = 0;
							}else{
								$array[$key]['flag'] = 1;
							}
						}
					}else{
						echo "Notfound";
					}
				} catch (Google_Service_Exception $e) {
					$array[$key]['error'] = $e->getErrors()[0]['message'] ;
				}
			}	
		}
		
		return json_encode($array);
	}

	/* Get analytics data  */
	public static function getAnalyticData() {
	
		$plugin = SmartGoogleAnalytics::getInstance();
		$settings = $plugin->getSettings();
		$client = new Google_Client();
		$client->setClientId($settings->oauthClientId);
		$client->setClientSecret($settings->oauthClientSecret);
		$client->setRedirectUri(UrlHelper::actionUrl('smart-google-analytics/default/callback'));
		$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);	
			
		$token = Craft::$app->getSession()->get("google_user_access_token");
		if($token != '') { 		
			$client->setAccessToken($token);

			if(!isset($token['access_token']) || (isset($token['access_token']) && $token['access_token'] == '')) {
				if ($client->isAccessTokenExpired()) {		
					// save refresh token to some variable
					$refreshToken = $token['refresh_token'];

					// refresh the token
					$client->refreshToken($refreshToken);
				}
			}
		} else {
			return;
		}
		
		$analytics = new Google_Service_Analytics($client);
		return $analytics;
	}
}
