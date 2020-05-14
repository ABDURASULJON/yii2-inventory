<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "product_out".
 *
 * @property int $id
 * @property string|null $invoice
 * @property int|null $userId
 * @property int|null $productId
 * @property int|null $qtyOut
 * @property date $datePublished
 */
class ProductOut extends ActiveRecord
{
    public static $unitCategories = [
        'Pcs' => 'Pcs',
        'Pack' => 'Pack',
        'Kg' => 'Kg',
        'Dus' => 'Dus',
    ];
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_out';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice', 'userId', 'productId', 'qtyOut'], 'required'],
            [['userId', 'productId', 'qtyOut'], 'integer'],
            [['invoice'], 'string', 'max' => 45],
            [['datePublished'], 'default', 'value' => date('Y-m-d')],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'invoice' => 'Invoice',
            'userId' => 'User',
            'productId' => 'Product',
            'qtyOut' => 'Qty',
            'datePublished' => 'Date Published',
        ];
    }
    
    public function search() {
        $query = self::find()
            ->andFilterWhere(['like', 'invoice', $this->invoice])
            ->andFilterWhere(['like', 'userId', $this->userId])
            ->andFilterWhere(['like', 'productId', $this->productId])
            ->andFilterWhere(['like', 'qtyOut', $this->qtyOut]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 8
            ]
        ]);

        return $dataProvider;
    }
    
    public function getProducts(){
        return $this->hasOne(Products::className(), ['id' => 'productId']);
    }
    
    public function getInvoiceData() {
        $query = self::find()->max('invoice');
        $noInvoice = (int) substr($query, 3, 3);
        $noInvoice++;
        $charInvoice = "POT";
        $newInvoice = $charInvoice . sprintf("%03s", $noInvoice);
        
        return $newInvoice;
    }
    
    public function getUser(){
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }
    
    public function sumProduct($dataQty, $dataProduct){
        $modelProduct = Products::findOne(['id' => $dataProduct]);
        $modelProduct->stockOut += $dataQty;
        $modelProduct->stockFinal = $modelProduct->stockFirst + $modelProduct->stockIn - $modelProduct->stockOut;
        return $modelProduct->save();
    }
}
