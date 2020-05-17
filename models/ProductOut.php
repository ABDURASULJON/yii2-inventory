<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

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
    public $fullName;
    public $nameProduct;
    public $typeProduct;
    public $unit;
    public $price;
    public $active;
    public $fromDate;
    public $toDate;
    
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
//            [['invoice', 'userId', 'productId', 'qtyOut'], 'required'],
            [['userId', 'productId', 'qtyOut'], 'integer'],
            [['invoice'], 'string', 'max' => 45],
            [['datePublished'], 'default', 'value' => date('Y-m-d')],
            [['datePublished', 'fullName', 'nameProduct', 'typeProduct', 'unit', 'price', 'active', 'fromDate', 'toDate'], 'safe'],
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
        $fromDate = $this->fromDate;
        $toDate = $this->toDate;
        if($fromDate == '') {
            $fromDate = '';
            $toDate = '';
        }else if($toDate == ''){
            $toDate = $this->fromDate;
        }
        $query = self::find()
            ->joinWith('user')
            ->joinWith('products')
            ->andFilterWhere(['like', 'product_out.invoice', $this->invoice])
            ->andFilterWhere(['like', 'user.fullName', $this->fullName])
            ->andFilterWhere(['like', 'products.nameProduct', $this->nameProduct])
            ->andFilterWhere(['like', 'products.typeProduct', $this->typeProduct])
            ->andFilterWhere(['like', 'products.unit', $this->unit])
            ->andFilterWhere(['=', 'product_out.qtyOut', $this->qtyOut])
            ->andFilterWhere(['=', 'products.price', $this->price])
            ->andFilterWhere(['like', 'products.active', $this->active])
            ->andFilterWhere(['between', 'product_out.datePublished', $fromDate, $toDate]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 8
            ],
            'sort' => [
                'attributes' => [
                    'invoice' => 'invoice',
                    'user.fullName' => 'user.fullName',
                    'products.nameProduct' => 'products.nameProduct',
                    'products.typeProduct' => 'products.typeProduct',
                    'products.unit' => 'products.unit',
                    'qtyOut' => 'qtyOut',
                    'products.price' => 'products.price',
                    'products.imageProduct' => 'products.imageProduct',
                    'datePublished' => 'datePublished',
                    'products.active' => 'products.active'
                ]
            ],
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
    
    public static function getListInvoice() {
        return ArrayHelper::map(self::find()->all(), 'id', 'invoice');
    }
    
    public function createTransaction($dataUpdate = false, $dataInvoice = null){
        if ($dataUpdate){
            $modelTransaction = Transaction::findOne(['codeTrx' => $dataInvoice]);
        }else{
            $modelTransaction = new Transaction();
            $modelTransaction->invoice = Transaction::getInvoiceData();
        }
        $modelTransaction->userId = $this->userId;
        $modelTransaction->codeTrx = $this->invoice;
        $modelTransaction->stockFirst = $this->products->stockFirst;
        $modelTransaction->qtyTrx = $this->qtyOut * (-1);
        $modelTransaction->stockFinal = $this->products->stockFinal;
        
        return $modelTransaction->save();
    }
    
}
