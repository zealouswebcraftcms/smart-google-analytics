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
use zealouswebcraftcms\smartgoogleanalytics\models\Views;
use zealouswebcraftcms\smartgoogleanalytics\records\CraftRecords;
use craft\errors\InvalidPluginException;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use yii\web\Response;
use craft\web\Session;
use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use yii\web\NotFoundHttpException;
use zealouswebcraftcms\smartgoogleanalytics\controllers\DefaultController;

/**
 * View Controller
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
class ViewController extends Controller
{

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
     * e.g.: actions/smart-google-analytics/View
     *
     * @return mixed
     */

	/* To Get Google Analytics Details */
	public function actionDetails(){		
		$client = DefaultController::getAnalyticData();			
		return $client;
	}

	/* To Get and Display Google Analytics Profile Details */
	public function actionDisplay(){	
		
		$analytics_services = $this->actionDetails();
			
		/* Get the list of accounts for the authorized user */
		$accounts=$analytics_services->management_accounts->listManagementAccounts();
		if (count($accounts->getItems()) > 0) {
			$items = $accounts->getItems();
			$firstAccountId = $items[0]->getId();
		}
		
		/* Get the list of properties for the authorized user */
		$properties = $analytics_services->management_webproperties
		->listManagementWebproperties($firstAccountId);
		if (count($properties->getItems()) > 0) {
			$items = $properties->getItems();
			$firstPropertyId = $items[0]->getId();
		}

		/* Get the list of views (profiles) for the authorized user */
		$profiles = $analytics_services->management_profiles
		->listManagementProfiles($firstAccountId, $firstPropertyId);

		return [
			'accounts' =>($accounts) ? $accounts : '',
			'properties' =>($properties) ? $properties : '',
			'views' =>($profiles) ? $profiles : '',
			
		];
	}
	
	/* To get dimension using API */
	public function actionDimensions($chartType) {
		$url = "https://www.googleapis.com/analytics/v3/metadata/ga/columns";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$items = curl_exec($ch);
		curl_close ($ch);
		$data = json_decode($items, true);
		$items = $data['items'];
		$data_items = [];
		$dimensions_data = [];
		$remove_dimensions_id = [
			'ga:region', 
			'ga:metro', 
			'ga:latitude', 
			'ga:longitude', 
			'ga:networkDomain', 
			'ga:networkLocation', 
			'ga:cityId', 
			'ga:continentId', 
			'ga:countryIsoCode', 
			'ga:metroId', 
			'ga:regionId', 
			'ga:regionIsoCode', 
			'ga:subContinentCode',
			'ga:city'
		];
		foreach( $items as $item ){
			if( $item['attributes']['status'] == 'DEPRECATED' ) {
				continue;
			}
			if($chartType == 'GEO') {
				if( $item['attributes']['type'] == 'DIMENSION' ) {
					
					if($item['attributes']['group'] == 'Geo Network' && !in_array($item['id'], $remove_dimensions_id)) {					
						$dimensions_data[ $item['attributes']['group'] ][] = $item;					
					} 
				}
			} else {
				if( $item['attributes']['type'] == 'DIMENSION' ) {		
					if($item['attributes']['group'] != 'Geo Network') {			
						$dimensions_data[ $item['attributes']['group'] ][] = $item;
					}
				}
			}
		}
	
		$data_items['dimensions'] = $dimensions_data;	
		
		return json_encode($data_items);
	}

	/* To get metrics using API */
	public function actionMetrics(){
		$url = "https://www.googleapis.com/analytics/v3/metadata/ga/columns";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$items = curl_exec($ch);
		curl_close ($ch);
		$data = json_decode($items, true);
		$items = $data['items'];
		$data_items = [];
		$dimensions_data = [];
		$metrics_data = [];
		$remove_metrics_id = [
			'ga:1dayUsers',
			'ga:7dayUsers',
			'ga:14dayUsers',
			'ga:28dayUsers',
			'ga:30dayUsers'
		];
		foreach( $items as $item ){
			if( $item['attributes']['status'] == 'DEPRECATED' )
				continue;

			if( $item['attributes']['type'] == 'DIMENSION' ) {
				if($item['attributes']['group'] != 'Geo Network') {
					$dimensions_data[ $item['attributes']['group'] ][] = $item;
				}
			}

			if( $item['attributes']['type'] == 'METRIC' ) {
				if (!in_array($item['id'], $remove_metrics_id)) {
					$metrics_data[ $item['attributes']['group'] ][] = $item;
				}		
			}
		}
		$data_items['metrics'] = $metrics_data;
		$data_items['dimensions'] = $dimensions_data;
		return $data_items;
	}

	/* For Displaying View Form */
    public function actionViews(){		
		
		$dimensions_metrics_data = $this->actionMetrics();
		$dimension = $dimensions_metrics_data['dimensions'];
		$metrics = $dimensions_metrics_data['metrics'];
		
		$token = Craft::$app->getSession()->get("google_user_access_token");
		if(isset($token) && $token != '') {
			$data = $this->actionDisplay();
			$accounts = $data['accounts'];
			$properties = $data['properties'];
			$profiles = $data['views'];
		}else{
			Craft::$app->getSession()->setNotice(Craft::t('smart-google-analytics', 'Please Connect With Google Analytics.'));
			return $this->redirect('settings/plugins/smart-google-analytics#settings-tab-settings');
		}
	
		$reportingView = new Views();
		
		return $this->renderTemplate('smart-google-analytics/views/add',[
			'reportingView'=>$reportingView,
			'accounts' =>$accounts,
			'properties' => $properties,
			'profiles'=> $profiles,
			'dimensions' => $dimension,
			'metrics' => $metrics,
		]);
	}

	/* For Fetching Properties */
	public function actionProperties($AccountId) {
		$arr_data = $arr_data1 = [];
		$analytics_services = $this->actionDetails();	
		$properties = $analytics_services->management_webproperties
		->listManagementWebproperties($AccountId);
		foreach ($properties as $key => $value) {
			$arr_data['property_array'][$key]['id'] = $value->id;
			$arr_data['property_array'][$key]['name'] = $value->name;
		}
		
		$items = $properties->getItems();
        $PropertyId = isset($items[0]) ? $items[0]->getId() : '';
        $flag = 0;
        if($PropertyId != ''){
            $get_property = $this->actionProfiles($AccountId, $PropertyId, $flag);
            $arr_data1 = $get_property;
        }
        $merge_array = array_merge($arr_data, $arr_data1); 
		return json_encode($merge_array);
	}
	
	/* For Fetching Profiles */
	public function actionProfiles($AccountId ,$PropertyId, $flag) {
		$arry_data = [];
		$analytics_services = $this->actionDetails();
		$profiles = $analytics_services->management_profiles
			->listManagementProfiles($AccountId ,$PropertyId);
		foreach ($profiles as $key => $value) {
			$arry_data['profiles_array'][$key]['id'] = $value->id;
			$arry_data['profiles_array'][$key]['name'] = $value->name;
		}
		if($flag == 0) {
			return $arry_data;
		} else {
			return json_encode($arry_data);
		}
	}

	/* Save the view */
	public function actionSave() {
		$this->requirePostRequest();
		$request = Craft::$app->getRequest();
		$reportingView = new Views();
		
		$reportingView->gaAccountId = $request->getBodyParam('gaAccountId');
        $reportingView->gaAccountName = $request->getBodyParam('gaAccountName');
		$reportingView->gaPropertyId =  $request->getBodyParam('gaPropertyId');
		$reportingView->gaPropertyName = $request->getBodyParam('gaPropertyName');
		$reportingView->gaViewId = $request->getBodyParam('gaViewId');
		$reportingView->gaViewName = $request->getBodyParam('gaViewName');
        $reportingView->chartName = $request->getBodyParam('chartName');
		$reportingView->chartType = $request->getBodyParam('chartType');
		$reportingView->order = $request->getBodyParam('order');
		$reportingView->status = $request->getBodyParam('status');
		$reportingView->dimensionKey = $request->getBodyParam('dimensionKey');
		$reportingView->dimensionValue = $request->getBodyParam('dimensionValue');
		$reportingView->metricsKey = $request->getBodyParam('metricsKey');
		$reportingView->metricsValue = $request->getBodyParam('metricsValue');
		
		$dimensions_metrics_data = $this->actionMetrics();
		$dimension = $dimensions_metrics_data['dimensions'];
		$metrics = $dimensions_metrics_data['metrics'];
		
		if($reportingView->validate()) { // code that sends model off to be saved
			$check_order = CraftRecords::find()
									->where(['gaAccountId' => $reportingView->gaAccountId])
									->andWhere(['>=','order',$reportingView->order])
									->andWhere(['dateDeleted' => null])
									->select('id')->asArray()->all();

			if(count($check_order) > 0) {
				$ids = array_column($check_order, 'id');
				CraftRecords::updateAll(['order' => new \yii\db\Expression('`order` + 1')], ['in', 'id', $ids]);
			}

			$record = new CraftRecords();
			$record->gaAccountId = $reportingView->gaAccountId;
			$record->account = $reportingView->gaAccountName;
			$record->gaPropertyId = $reportingView->gaPropertyId;
			$record->property = $reportingView->gaPropertyName;
			$record->gaViewId = $reportingView->gaViewId;
			$record->views = $reportingView->gaViewName;
			$record->chartName = $reportingView->chartName;
			$record->chartType = $reportingView->chartType;
			$record->order = $reportingView->order;
			$record->status = $reportingView->status;
			$record->dimensionKey = $reportingView->dimensionKey;
			$record->dimensionValue = $reportingView->dimensionValue;
			$record->metricsKey = $reportingView->metricsKey;
			$record->metricsValue = $reportingView->metricsValue;
			
			if($record->save()) {
				Craft::$app->getSession()->setNotice(Craft::t('smart-google-analytics', 'View Saved Successfully'));
				return $this->redirect('settings/plugins/smart-google-analytics#settings-tab-View');
			}
		}
		else {
			$data = $this->actionDisplay();
			$accounts = $data['accounts'];
			$properties = $data['properties'];
			$profiles = $data['views'];
			return $this->renderTemplate('smart-google-analytics/views/add',[
				'reportingView' => $reportingView,
				'accounts' => $accounts,
				'properties' => $properties,
				'profiles'=> $profiles,
				'dimensions' => $dimension,
				'metrics' => $metrics,
			]);
		}
	}

	/* Delete the record */
	public function actionDeleteView(string $viewId){
		$record = CraftRecords::findOne($viewId);
		$record->softDelete();
		Craft::$app->getSession()->setNotice(Craft::t('smart-google-analytics', 'View Deleted Successfully'));
		return $this->redirect('settings/plugins/smart-google-analytics#settings-tab-View');
	}

	/* Fetch the record for edit */
	public function actionEditView(string $viewId){
		
		$dimensions_metrics_data = $this->actionMetrics();
		$dimension = $dimensions_metrics_data['dimensions'];
		$metrics = $dimensions_metrics_data['metrics'];
		
		$record = CraftRecords::findOne($viewId);
		$analytics_services = $this->actionDetails();
		$accounts=$analytics_services->management_accounts->listManagementAccounts();
		$properties = $analytics_services->management_webproperties
		->listManagementWebproperties($record->gaAccountId);
		$profiles = $analytics_services->management_profiles
		->listManagementProfiles($record->gaAccountId, $record->gaPropertyId);
		
		$reportingView = new Views();
		return $this->renderTemplate('smart-google-analytics/views/edit',[
			'record' => $record,
			'reportingView' => $reportingView,
			'accounts' => $accounts,
			'properties' => $properties,
			'profiles'=> $profiles,
			'dimensions' => $dimension,
			'metrics' => $metrics,
		]);
	}

	/* Save the edited view */
	public function actionSaveViewEditData(){
		$this->requirePostRequest();
		$request = Craft::$app->getRequest();
		$id = $request->getBodyParam('recordId');
		$reportingView = new Views();
		$record = CraftRecords::findOne($id);
		$reportingView->gaAccountId = $request->getBodyParam('gaAccountId');
        $reportingView->gaAccountName = $request->getBodyParam('gaAccountName');
		$reportingView->gaPropertyId =  $request->getBodyParam('gaPropertyId');
		$reportingView->gaPropertyName = $request->getBodyParam('gaPropertyName');
		$reportingView->gaViewId = $request->getBodyParam('gaViewId');
		$reportingView->gaViewName = $request->getBodyParam('gaViewName');
        $reportingView->chartName = $request->getBodyParam('chartName');
		$reportingView->chartType = $request->getBodyParam('chartType');
		$reportingView->order = $request->getBodyParam('order');
		$reportingView->status = $request->getBodyParam('status');
		$reportingView->dimensionKey = $request->getBodyParam('dimensionKey');
		$reportingView->dimensionValue = $request->getBodyParam('dimensionValue');
		$reportingView->metricsKey = $request->getBodyParam('metricsKey');
		$reportingView->metricsValue = $request->getBodyParam('metricsValue');
		
		$dimensions_metrics_data = $this->actionMetrics();
		$dimension = $dimensions_metrics_data['dimensions'];
		$metrics = $dimensions_metrics_data['metrics'];

		if($reportingView->validate()) { // code that sends model off to be saved
			$neworder = $reportingView->order;
			$current_order = CraftRecords::find()->where(['=', 'id', $id])->asArray()->one();
			$oldorder = $current_order['order'];
			
			if($oldorder > $neworder) {
				$orders = CraftRecords::find()
									->where(['!=', 'id', $id])
									->andWhere(['gaAccountId' => $reportingView->gaAccountId])
									->andWhere(['>=','order',$neworder])
									->andWhere(['<','order',$oldorder])
									->andWhere(['dateDeleted' => null])
									->orderBy('order')
									->select('id')->asArray()->all();
				
				if(count($orders) > 0) {
					$ids = array_column($orders, 'id');
					CraftRecords::updateAll(['order' => new \yii\db\Expression('`order` + 1')], ['in', 'id', $ids]);
				}
			} else if($oldorder < $neworder){
				$orders = CraftRecords::find()
									->where(['!=', 'id', $id])
									->andWhere(['gaAccountId' => $reportingView->gaAccountId])
									->andWhere(['<=','order',$neworder])
									->andWhere(['>','order',$oldorder])
									->andWhere(['dateDeleted' => null])
									->orderBy('order')
									->select('id')->asArray()->all();

				if(count($orders) > 0) {
					$ids = array_column($orders, 'id');
					CraftRecords::updateAll(['order' => new \yii\db\Expression('`order` - 1')], ['in', 'id', $ids]);
				}
			}
			
			$record->gaAccountId = $reportingView->gaAccountId;
			$record->account = $reportingView->gaAccountName;
			$record->gaPropertyId = $reportingView->gaPropertyId;
			$record->property = $reportingView->gaPropertyName;
			$record->gaViewId = $reportingView->gaViewId;
			$record->views = $reportingView->gaViewName;
			$record->chartName = $reportingView->chartName;
			$record->chartType = $reportingView->chartType;
			$record->order = $reportingView->order;
			$record->status = $reportingView->status;
			$record->dimensionKey = ($reportingView->chartType != 'STAT') ? $reportingView->dimensionKey : '-';
			$record->dimensionValue = ($reportingView->chartType != 'STAT') ? $reportingView->dimensionValue : '-';
			$record->metricsKey = $reportingView->metricsKey;
			$record->metricsValue = $reportingView->metricsValue;
			if($record->save()) {
				Craft::$app->getSession()->setNotice(Craft::t('smart-google-analytics', 'View Edited Successfully'));
				return $this->redirect('settings/plugins/smart-google-analytics#settings-tab-View');
			}
		} else {
			$data = $this->actionDisplay();
			$accounts = $data['accounts'];
			$properties = $data['properties'];
			$profiles = $data['views'];
			return $this->renderTemplate('smart-google-analytics/views/edit',[
				'record' => $record,
				'reportingView' => $reportingView,
				'accounts' => $accounts,
				'properties' => $properties,
				'profiles'=> $profiles,
				'dimensions' => $dimension,
				'metrics' => $metrics,
			]);
		}
	}
}


