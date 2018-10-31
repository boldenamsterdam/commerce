<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\elements\db;

use Craft;
use craft\commerce\base\Gateway;
use craft\commerce\base\GatewayInterface;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\Customer;
use craft\commerce\models\OrderStatus;
use craft\commerce\Plugin;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use DateTime;
use yii\db\Connection;

/**
 * OrderQuery represents a SELECT SQL statement for orders in a way that is independent of DBMS.
 *
 * @method Order[]|array all($db = null)
 * @method Order|array|null one($db = null)
 * @method Order|array|null nth(int $n, Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class OrderQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @var string The order number of the resulting entry.
     */
    public $number;

    /**
     * @var string The email address the resulting emails must have.
     */
    public $email;

    /**
     * @var bool The completion status that the resulting orders must have.
     */
    public $isCompleted;

    /**
     * @var mixed The Date Ordered date that the resulting orders must have.
     */
    public $dateOrdered;

    /**
     * @var mixed The Updated On date that the resulting orders must have.
     */
    public $updatedOn;

    /**
     * @var mixed The Expiry Date that the resulting orders must have.
     */
    public $expiryDate;

    /**
     * @var mixed The date the order was paid.
     */
    public $datePaid;

    /**
     * @var int The Order Status ID that the resulting orders must have.
     */
    public $orderStatusId;

    /**
     * @var bool The completion status that the resulting orders must have.
     */
    public $customerId;

    /**
     * @var int The gateway ID that the resulting orders must have.
     */
    public $gatewayId;

    /**
     * @var bool The payment status the resulting orders must belong to.
     */
    public $isPaid;

    /**
     * @var bool The payment status the resulting orders must belong to.
     */
    public $isUnpaid;

    /**
     * @var PurchasableInterface|PurchasableInterface[] The resulting orders must contain these Purchasables.
     */
    public $hasPurchasables;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'commerce_orders.id';
        }

        parent::__construct($elementType, $config);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'updatedAfter':
                $this->updatedAfter($value);
                break;
            case 'updatedBefore':
                $this->updatedBefore($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Sets the [[number]] property.
     *
     * @param string|null $value The property value
     * @return static self reference
     */
    public function number(string $value = null)
    {
        $this->number = $value;
        return $this;
    }

    /**
     * Sets the [[email]] property.
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     */
    public function email(string $value)
    {
        $this->email = $value;
        return $this;
    }

    /**
     * Sets the [[isCompleted]] property.
     *
     * @param bool $value The property value
     * @return static self reference
     */
    public function isCompleted(bool $value = true)
    {
        $this->isCompleted = $value;
        return $this;
    }

    /**
     * Sets the [[dateOrdered]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function dateOrdered($value)
    {
        $this->dateOrdered = $value;
        return $this;
    }

    /**
     * Sets the [[datePaid]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function datePaid($value)
    {
        $this->datePaid = $value;
        return $this;
    }

    /**
     * Sets the [[expiryDate]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function expiryDate($value)
    {
        $this->expiryDate = $value;
        return $this;
    }

    /**
     * Sets the [[updatedAfter]] property.
     *
     * @param string|DateTime $value The property value
     * @return static self reference
     * @deprecated in 2.0. Use [[dateUpdated()]] instead.
     */
    public function updatedAfter($value)
    {
        Craft::$app->getDeprecator()->log(__METHOD__, __METHOD__ . ' is deprecated. Use dateUpdated() instead.');

        if ($value instanceof DateTime) {
            $value = $value->format(DateTime::W3C);
        }

        $this->dateUpdated = ArrayHelper::toArray($this->dateUpdated);
        $this->dateUpdated[] = '>=' . $value;

        return $this;
    }

    /**
     * Sets the [[updatedBefore]] property.
     *
     * @param string|DateTime $value The property value
     * @return static self reference
     * @deprecated in 2.0. Use [[dateUpdated()]] instead.
     */
    public function updatedBefore($value)
    {
        Craft::$app->getDeprecator()->log(__METHOD__, __METHOD__ . ' is deprecated. Use dateUpdated() instead.');

        if ($value instanceof DateTime) {
            $value = $value->format(DateTime::W3C);
        }

        $this->dateUpdated = ArrayHelper::toArray($this->dateUpdated);
        $this->dateUpdated[] = '<' . $value;

        return $this;
    }

    /**
     * Sets the [[orderStatus]] property.
     *
     * @param string|string[]|OrderStatus|null $value The property value
     * @return static self reference
     */
    public function orderStatus($value)
    {
        if ($value instanceof OrderStatus) {
            $this->orderStatusId = $value->id;
        } else if ($value !== null) {
            $this->orderStatusId = (new Query())
                ->select(['id'])
                ->from(['{{%commerce_orderstatuses}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->orderStatusId = null;
        }

        return $this;
    }

    /**
     * Sets the [[orderStatusId]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function orderStatusId($value)
    {
        $this->orderStatusId = $value;
        return $this;
    }

    /**
     * Sets the [[customer]] property.
     *
     * @param Customer|null $value The property value
     * @return static self reference
     */
    public function customer(Customer $value = null)
    {
        if ($value) {
            $this->customerId = $value->id;
        } else {
            $this->customerId = null;
        }

        return $this;
    }

    /**
     * Sets the [[customerId]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function customerId($value)
    {
        $this->customerId = $value;
        return $this;
    }

    /**
     * Sets the [[gateway]] property.
     *
     * @param GatewayInterface|null $value The property value
     * @return static self reference
     */
    public function gateway(GatewayInterface $value = null)
    {
        if ($value) {
            /** @var Gateway $value */
            $this->gatewayId = $value->id;
        } else {
            $this->gatewayId = null;
        }

        return $this;
    }

    /**
     * Sets the [[gatewayId]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     */
    public function gatewayId($value)
    {
        $this->gatewayId = $value;
        return $this;
    }

    /**
     * Sets the [[user]] property.
     *
     * @param User|int $value The property value
     * @return static self reference
     */
    public function user($value)
    {
        if ($value instanceof User) {
            $customer = Plugin::getInstance()->getCustomers()->getCustomerByUserId($value->id);
            $this->customerId = $customer->id ?? null;
        } else if ($value !== null) {
            $customer = Plugin::getInstance()->getCustomers()->getCustomerByUserId($value);
            $this->customerId = $customer->id ?? null;
        } else {
            $this->customerId = null;
        }

        return $this;
    }

    /**
     * Sets the [[isPaid]] property.
     *
     * @param bool $value The property value
     * @return static self reference
     */
    public function isPaid(bool $value = true)
    {
        $this->isPaid = $value;
        return $this;
    }

    /**
     * Sets the [[isUnpaid]] property.
     *
     * @param bool $value The property value
     * @return static self reference
     */
    public function isUnpaid(bool $value = true)
    {
        $this->isUnpaid = $value;
        return $this;
    }

    /**
     * Sets the [[hasPurchasables]] property.
     *
     * @param PurchasableInterface|PurchasableInterface[]|null $value The property value
     * @return static self reference
     */
    public function hasPurchasables($value)
    {
        $this->hasPurchasables = $value;

        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('commerce_orders');

        $this->query->select([
            'commerce_orders.id',
            'commerce_orders.number',
            'commerce_orders.couponCode',
            'commerce_orders.orderStatusId',
            'commerce_orders.dateOrdered',
            'commerce_orders.email',
            'commerce_orders.isCompleted',
            'commerce_orders.datePaid',
            'commerce_orders.currency',
            'commerce_orders.paymentCurrency',
            'commerce_orders.lastIp',
            'commerce_orders.orderLanguage',
            'commerce_orders.message',
            'commerce_orders.returnUrl',
            'commerce_orders.cancelUrl',
            'commerce_orders.billingAddressId',
            'commerce_orders.shippingAddressId',
            'commerce_orders.shippingMethodHandle',
            'commerce_orders.gatewayId',
            'commerce_orders.paymentSourceId',
            'commerce_orders.customerId',
            'commerce_orders.dateUpdated'
        ]);

        if ($this->number) {
            $this->subQuery->andWhere(['commerce_orders.number' => $this->number]);
        }

        if ($this->email) {
            $this->subQuery->andWhere(Db::parseParam('commerce_orders.email', $this->email));
        }

        if ($this->isCompleted) {
            $this->subQuery->andWhere(Db::parseParam('commerce_orders.isCompleted', $this->isCompleted));
        }

        if ($this->dateOrdered) {
            $this->subQuery->andWhere(Db::parseDateParam('commerce_orders.dateOrdered', $this->dateOrdered));
        }

        if ($this->datePaid) {
            $this->subQuery->andWhere(Db::parseDateParam('commerce_orders.datePaid', $this->datePaid));
        }

        if ($this->expiryDate) {
            $this->subQuery->andWhere(Db::parseDateParam('commerce_orders.expiryDate', $this->expiryDate));
        }

        if ($this->dateUpdated) {
            $this->subQuery->andWhere(Db::parseDateParam('commerce_orders.dateUpdated', $this->dateUpdated));
        }

        if ($this->orderStatusId) {
            $this->subQuery->andWhere(Db::parseParam('commerce_orders.orderStatusId', $this->orderStatusId));
        }

        if ($this->customerId) {
            $this->subQuery->andWhere(Db::parseParam('commerce_orders.customerId', $this->customerId));
        }

        if ($this->gatewayId) {
            $this->subQuery->andWhere(Db::parseParam('commerce_orders.gatewayId', $this->gatewayId));
        }

        if ($this->isPaid) {
            $this->subQuery->andWhere(Db::parseParam('commerce_orders.totalPaid', '>= commerce_orders.totalPrice'));
        }

        if ($this->isUnpaid) {
            $this->subQuery->andWhere(Db::parseParam('commerce_orders.totalPaid', '< commerce_orders.totalPrice'));
        }

        if ($this->hasPurchasables) {
            $purchasableIds = [];

            if (!is_array($this->hasPurchasables)) {
                $this->hasPurchasables = [$this->hasPurchasables];
            }

            foreach ($this->hasPurchasables as $purchasable) {
                if ($purchasable instanceof PurchasableInterface) {
                    $purchasableIds[] = $purchasable->getId();
                } else if (is_numeric($purchasable)) {
                    $purchasableIds[] = $purchasable;
                }
            }

            // Remove any blank purchasable IDs (if any)
            $purchasableIds = array_filter($purchasableIds);

            $this->subQuery->innerJoin('{{%commerce_lineitems}} lineitems', '[[lineitems.orderId]] = [[commerce_orders.id]]');
            $this->subQuery->andWhere(['in', '[[lineitems.purchasableId]]', $purchasableIds]);
        }

        return parent::beforePrepare();
    }
}
