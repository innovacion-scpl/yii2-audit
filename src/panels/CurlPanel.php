<?php
/**
 * Allows the user to log the performed cURL requests for later viewing.
 * Since these cannot be tracked automatically, the panel provides 2 utility functions: `curlStart()` and `curlEnd()`
 */

namespace bedezign\yii2\audit\panels;

use Yii;
use bedezign\yii2\audit\components\panels\DataStoragePanel;
use yii\grid\GridViewAsset;
use yii\data\ArrayDataProvider;

/**
 * Class CurlPanel
 * @package bedezign\yii2\audit\src\panels
 */
class CurlPanel extends DataStoragePanel
{
    /**
     * @var bool    Enable verbose logging on the cURL handle and store the complete connection log
     */
    public $log = true;

    /**
     * @var bool    Store the returned headers in text
     */
    public $headers = true;

    /**
     * @var bool    Store the content of the request. If enabled this will set CURLOPT_RETURNTRANSFER
     */
    public $content = true;

    private $_logHandles = [];

    public function init()
    {
        parent::init();
        $this->module->registerFunction('curlExec', [$this, 'doRequest']);
        $this->module->registerFunction('curlBegin', [$this, 'trackRequest']);
        $this->module->registerFunction('curlEnd', [$this, 'finalizeRequest']);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return Yii::t('audit', 'cURL');
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->getName() . ' <small>(' . count($this->data) . ')</small>';
    }

    public function doRequest($handle, $url = null, $postData = null)
    {
        $this->trackRequest($handle, $url, $postData);
        $result = curl_exec($handle);
        $this->finalizeRequest($handle);
        return $result;
    }

    /**
     * @param $data
     */
    public function trackRequest($handle, $url = null, $postData = null)
    {
        $id = $this->id($handle);

        $this->data[$id] = [];

        if ($url)
            $this->data[$id]['starting_url'] = $url;

        if ($postData)
            $this->data[$id]['post'] = $postData;

        if ($this->headers)
            curl_setopt($handle, CURLOPT_HEADERFUNCTION, [$this, 'captureHeader']);

        if ($this->log) {
            curl_setopt($handle, CURLOPT_VERBOSE, true);
            curl_setopt($handle, CURLOPT_STDERR, $this->_logHandles[$id] = fopen('php://temp', 'rw+'));
        }

        if ($this->content)
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);

        return true;
    }

    public function finalizeRequest($handle)
    {
        $id = $this->id($handle);

        $info = curl_getinfo($handle);
        if (is_array($info)) {
            $info['effective_url'] = $info['url'];
            unset($info['url']);
            $this->data[$id] = array_merge($this->data[$id], $info);
        }

        if ($this->log && isset($this->_logHandles[$id])) {
            $file = $this->_logHandles[$id];
            rewind($file);
            $this->data[$id]['log'] = stream_get_contents($file);
            fclose($file);
            unset($this->_logHandles[$id]);
        }

        if ($this->content)
            $this->data[$id]['content']= curl_multi_getcontent($handle);

        // Cleanup empty things
        $this->data[$id] = array_filter($this->data[$id]);

        $errorNumber = curl_errno($handle);
        $error       = curl_error($handle);
        if ($errorNumber || strlen($error)) {
            $this->data[$id]['error'] = $errorNumber;
            $this->data[$id]['errorMessage'] = $error;

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return $this->data ? array_values($this->data) : null;
    }

    /**
     * @inheritdoc
     */
    public function getDetail()
    {
        $dataProvider = new ArrayDataProvider();
        $dataProvider->allModels = $this->data;

        return Yii::$app->view->render('panels/curl/index', [
            'panel'        => $this,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function registerAssets($view)
    {
        GridViewAsset::register($view);
        $view->registerJs('$(".audit_curl_post_toggle").click(function() {$(this).next().next().toggle().next().toggle(); });');
    }

    public function captureHeader($handle, $header)
    {
        $id = $this->id($handle);
        if (!isset($this->data[$id]['headers']))
            $this->data[$id]['headers'] = [];

        $this->data[$id]['headers'][] = $header;
        return strlen($header);
    }

    protected function id($resource)
    {
        if (!is_resource($resource))
            return false;

        return array_pop(explode('#', (string)$resource));
    }
}