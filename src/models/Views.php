<?php
/**
 * Smart Google Analytics plugin for Craft CMS 3.x
 *
 * Smart Google Analytics
 *
 * @link      https://www.zealousweb.com
 * @copyright Copyright (c) 2021 zealousweb
 */

namespace zealouswebcraftcms\smartgoogleanalytics\models;
use zealouswebcraftcms\smartgoogleanalytics\SmartGoogleAnalytics;

use Craft;
use craft\base\Model;

/**
 * SmartGoogleAnalytics Views Model
 *
 * This is a model used to define the Views settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    zealousweb
 * @package   SmartGoogleAnalytics
 * @since     1.0.0
 */

class Views extends Model   
{
    /**
     * @var string|null
     */

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var string Google Analytics Account ID
    */
    public $gaAccountId; 

    /**
     * @var string Google Analytics Account Name
     */
    public $gaAccountName;

    /**
     * @var string Google Analytics Property ID
    */
    public $gaPropertyId;

    /**
     * @var string Google Analytics Property Name
     */
    public $gaPropertyName;

    /**
     * @var string Google Analytics View ID
     */
    public $gaViewId;
    
    /**
     * @var string Google Analytics View Name
     */
    public $gaViewName;

    /**
     * @var string Chart Name
     */
    public $chartName;

    /**
     * @var string Chart Type
     */
    public $chartType;

    /**
    * @var string Order
    */
    public $order;

    /**
     * @var string Status
    */
    public $status;

    /**
     * @var string Dimension Key
    */
    public $dimensionKey; 

    /** 
     * @var string Dimension Value
     */
    public $dimensionValue;

    /**
     * @var string Metrics Key
    */
    public $metricsKey; 

    /**
     * @var string Metrics Value
     */
    public $metricsValue;

    /**
     * @var UploadedFile[]|null
    */
   
    /**
     * @inheritdoc
    */
    public function rules()
    {
        $rules = [
            [['chartName'],'required','message' => 'Chart Name can not be blank'],
            [['chartType'],'required','message' => 'Chart Type can not be blank'],
            [['order'],'required','message' => 'Order can not be blank'],
            [['status'],'required','message' => 'Status can not be blank'],
            [['metricsValue'],'required','message' => 'Metrics can not be blank'],
            [['dimensionValue'],'required','message' => 'Dimension can not be blank'],
            [['id'], 'number', 'integerOnly' => true],
           
        ];
        return $rules;
    }
}
