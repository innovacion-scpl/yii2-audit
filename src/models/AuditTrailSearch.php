<?php

namespace bedezign\yii2\audit\models;


use bedezign\yii2\audit\components\DbHelper;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\VarDumper;

/**
 * AuditTrailSearch
 * @package bedezign\yii2\audit\models
 */
class AuditTrailSearch extends AuditTrail
{
    /**
     * @return array
     */
    public function rules()
    {
        // Note: The model is used by both the entry and the trail index pages, hence the separate use of `id` and `entry_id`
        return [
            [['id', 'user_id', 'entry_id', 'action', 'model', 'model_id', 'field', 'old_value', 'new_value', 'created'], 'safe'],
        ];
    }

    /**
     * @return array
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * @param $params
     * @param null $query
     * @return ActiveDataProvider
     */
    public function search($params, $query = null)
    {
        $query = $query ? $query : $this->find();
        $query->select($this->safeAttributes());
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ]
        ]);

        
        // load the search form data and validate
        if (!($this->load($params) && $this->validate())) {
            return $this->changeToArrayDataProvider($dataProvider); //cambio de ActiveDataProvider a ArrayDataProvider
        }
        
        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['entry_id' => $this->entry_id]);
        $query->andFilterWhere(['user_id' => $this->user_id]);
        $query->andFilterWhere(['action' => $this->action]);
        $query->andFilterWhere([DbHelper::likeOperator(AuditTrail::class), 'model', $this->model]);
        $query->andFilterWhere(['model_id' => $this->model_id]);
        if (is_array($this->field)) {
            $query->andFilterWhere(['in', 'field', $this->field]);
        } else {
            $query->andFilterWhere(['field' => $this->field]);
        }
        $query->andFilterWhere(['like', DbHelper::convertIfNeeded(AuditTrail::class, 'created', 'text'), $this->created]);

    
        return $this->changeToArrayDataProvider($dataProvider);
    }

    public function changeToArrayDataProvider($dataProvider){
        //Codigo para que se vean los delete y create como updates
        $arrayDataProvider = new ArrayDataProvider([
            'allModels' => $dataProvider->query->orderBy(['id' => SORT_DESC])->all(),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
        foreach ($arrayDataProvider->allModels as $key => $create) {
        // Yii::error(VarDumper::dumpAsString([$create->field, $create->action, $create->new_value, $create->old_value]));
        // Yii::error(VarDumper::dumpAsString($create));
            foreach ($arrayDataProvider->allModels as $key2 => $delete) {
                if($create->field == $delete->field && $create->action == 'CREATE' && $delete->action == 'DELETE' && $create->entry_id == $delete->entry_id && $create->model_id == $delete->model_id){
                    $create->old_value = $delete->old_value;
                    $create->action = 'UPDATE';
                    unset($arrayDataProvider->allModels[$key2]);
                }
            }
        
        }
        foreach ($arrayDataProvider->allModels as $key => $delete){
            if($delete->new_value == $delete->old_value){
                unset($arrayDataProvider->allModels[$key]);
            }
        }
        //Fin de cambio
        return $arrayDataProvider;

    }

    /**
     * @return array
     */
    static public function actionFilter()
    {
        return \yii\helpers\ArrayHelper::map(
            self::find()->select('action')->groupBy('action')->orderBy('action ASC')->all(),
            'action',
            'action'
        );
    }
}
