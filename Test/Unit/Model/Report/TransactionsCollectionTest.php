<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Braintree\Test\Unit\Model\Report;

use Magento\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Braintree\Model\Report\FilterMapper;
use Magento\Braintree\Model\Report\TransactionsCollection;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

/**
 * Class TransactionsCollectionTest
 *
 * Test for class \Magento\Braintree\Model\Report\TransactionsCollection
 */
class TransactionsCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BraintreeAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $braintreeAdapterMock;

    /**
     * @var EntityFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityFactoryMock;

    /**
     * @var FilterMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterMapperMock;

    /**
     * @var DocumentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionMapMock;

    /**
     * Setup
     */
    protected function setUp()
    {
        $this->transactionMapMock = $this->getMockBuilder(DocumentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityFactoryMock = $this->getMockBuilder(EntityFactoryInterface::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->filterMapperMock = $this->getMockBuilder(FilterMapper::class)
            ->setMethods(['getFilter'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->braintreeAdapterMock = $this->getMockBuilder(BraintreeAdapter::class)
            ->setMethods(['search'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get items
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testGetItems()
    {
        $this->filterMapperMock->expects($this->once())
            ->method('getFilter')
            ->willReturn(new BraintreeSearchNodeStub());

        $this->braintreeAdapterMock->expects($this->once())
            ->method('search')
            ->willReturn(['transaction1', 'transaction2']);

        $this->entityFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->transactionMapMock);

        $collection = new TransactionsCollection(
            $this->entityFactoryMock,
            $this->braintreeAdapterMock,
            $this->filterMapperMock
        );

        $collection->addFieldToFilter('orderId', ['like' => '0']);
        $items = $collection->getItems();
        $this->assertCount(2, $items);
        $this->assertInstanceOf(DocumentInterface::class, $items[1]);
    }

    /**
     * Get empty result
     */
    public function testGetItemsEmptyCollection()
    {
        $this->filterMapperMock->expects($this->once())
            ->method('getFilter')
            ->willReturn(new BraintreeSearchNodeStub());

        $this->braintreeAdapterMock->expects($this->once())
            ->method('search')
            ->willReturn(null);

        $this->entityFactoryMock->expects($this->never())
            ->method('create')
            ->willReturn($this->transactionMapMock);

        $collection = new TransactionsCollection(
            $this->entityFactoryMock,
            $this->braintreeAdapterMock,
            $this->filterMapperMock
        );

        $collection->addFieldToFilter('orderId', ['like' => '0']);
        $items = $collection->getItems();
        $this->assertEquals(0, count($items));
    }

    /**
     * Get items with limit
     */
    public function testGetItemsWithLimit()
    {
        $transations = range(1, TransactionsCollection::TRANSACTION_MAXIMUM_COUNT + 10);

        $this->filterMapperMock->expects($this->once())
            ->method('getFilter')
            ->willReturn(new BraintreeSearchNodeStub());

        $this->braintreeAdapterMock->expects($this->once())
            ->method('search')
            ->willReturn($transations);

        $this->entityFactoryMock->expects($this->exactly(TransactionsCollection::TRANSACTION_MAXIMUM_COUNT))
            ->method('create')
            ->willReturn($this->transactionMapMock);

        $collection = new TransactionsCollection(
            $this->entityFactoryMock,
            $this->braintreeAdapterMock,
            $this->filterMapperMock
        );
        $collection->setPageSize(TransactionsCollection::TRANSACTION_MAXIMUM_COUNT);

        $collection->addFieldToFilter('orderId', ['like' => '0']);
        $items = $collection->getItems();
        $this->assertEquals(TransactionsCollection::TRANSACTION_MAXIMUM_COUNT, count($items));
        $this->assertInstanceOf(DocumentInterface::class, $items[1]);
    }

    /**
     * Get items with limit
     */
    public function testGetItemsWithNullLimit()
    {
        $transations = range(1, TransactionsCollection::TRANSACTION_MAXIMUM_COUNT + 10);

        $this->filterMapperMock->expects($this->once())
            ->method('getFilter')
            ->willReturn(new BraintreeSearchNodeStub());

        $this->braintreeAdapterMock->expects($this->once())
            ->method('search')
            ->willReturn($transations);

        $this->entityFactoryMock->expects($this->exactly(TransactionsCollection::TRANSACTION_MAXIMUM_COUNT))
            ->method('create')
            ->willReturn($this->transactionMapMock);

        $collection = new TransactionsCollection(
            $this->entityFactoryMock,
            $this->braintreeAdapterMock,
            $this->filterMapperMock
        );
        $collection->setPageSize(null);

        $collection->addFieldToFilter('orderId', ['like' => '0']);
        $items = $collection->getItems();
        $this->assertEquals(TransactionsCollection::TRANSACTION_MAXIMUM_COUNT, count($items));
        $this->assertInstanceOf(DocumentInterface::class, $items[1]);
    }

    /**
     * Add fields to filter
     *
     * @dataProvider addToFilterDataProvider
     */
    public function testAddToFilter($field, $condition, $filterMapperCall, $expectedCondition)
    {
        $this->filterMapperMock->expects(static::exactly($filterMapperCall))
            ->method('getFilter')
            ->with($field, $expectedCondition)
            ->willReturn(new BraintreeSearchNodeStub());

        $collection = new TransactionsCollection(
            $this->entityFactoryMock,
            $this->braintreeAdapterMock,
            $this->filterMapperMock
        );

        static::assertInstanceOf(
            TransactionsCollection::class,
            $collection->addFieldToFilter($field, $condition)
        );
    }

    /**
     * addToFilter DataProvider
     *
     * @return array
     */
    public function addToFilterDataProvider()
    {
        return [
            ['orderId', ['like' => 1], 1, ['like' => 1]],
            ['type', 'sale', 1, ['eq' => 'sale']],
            [['type', 'orderId'], [], 0, []],
        ];
    }
}
