<?php
namespace Gamegos\Util\Tests\Collection;

/* Imports from PHP core */
use ArrayIterator;

/* Imports from PHPUnit */
use PHPUnit_Framework_TestCase as TestCase;

/* Imports from Gamegos\Util */
use Gamegos\Util\Collection\Collection;
use Gamegos\Util\Collection\CollectionInterface;
use Gamegos\Util\Collection\CollectableInterface;
use Gamegos\Util\Collection\Exception\InvalidItemClassException;
use Gamegos\Util\Collection\Exception\InvalidCollectableException;

/**
 * Test class for Gamegos\Util\Collection\Collection
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class CollectionTest extends TestCase
{
    public function nonStringArgumentsProvider()
    {
        return [
            'null'       => [null],
            'bool-true'  => [true],
            'bool-false' => [false],
            'int'        => [1],
            'int-zero'   => [0],
            'float'      => [1.1],
            'object'     => [(object) ['foo' => 'bar']],
            'resource'   => [fopen('php://memory', 'r')],
        ];
    }

    /**
     * @dataProvider nonStringArgumentsProvider
     * @testdox Constructor should throw InvalidItemClassException for non-string argument
     */
    public function testConstructorShouldThrowExceptionForNonStringArgument($itemClass)
    {
        $this->setExpectedException(InvalidItemClassException::class, null, InvalidItemClassException::NOT_STRING);
        $collection = new Collection($itemClass);
    }

    /**
     * @testdox Constructor should throw InvalidItemClassException for invalid class
     */
    public function testConstructorShouldThrowExceptionForInvalidClass()
    {
        $this->setExpectedException(InvalidItemClassException::class, null, InvalidItemClassException::NOT_COLLECTABLE);
        $collection = new Collection('stdClass');
    }

    public function testConstructorShouldSetItemClass()
    {
        $collectableClass = $this->createCollectableClass();
        $collection       = new Collection($collectableClass);
        $this->assertSame($collectableClass, $collection->getItemClass());
    }

    /**
     * @testdox Overall: add(), remove(), contains(), isEmpty(), count(), offsetExists(), offsetGet()
     */
    public function testOverall()
    {
        $itemClass = $this->createCollectableClass();
        $testItems = [
            $this->getMock($itemClass),
            $this->getMock($itemClass),
            $this->getMock($itemClass),
        ];

        $collection = $this->getMockForCollection($itemClass);

        // The collection should be empty initially.
        $this->assertTrue($collection->isEmpty());

        $count = 0;
        foreach ($testItems as $object) {
            // The collection size should equal to $count before 'add' operation.
            $this->assertCount($count, $collection); // via Countable::count()

            // Add the item into the collection.
            $collection->add($object);

            // The collection should contain the new item.
            $this->assertContains($object, $collection);       // via Traversable interface
            $this->assertTrue($collection->contains($object)); // via contains()

            $hash = $collection->getItemHash($object);

            // The collection should have the hash of the new item via ArrayAccess::offsetExists()
            $this->assertArrayHasKey($hash, $collection);

            // The new item should be accessed by its hash via ArrayAccess::offsetGet()
            $this->assertSame($object, $collection[$hash]);

            // The collection size should equal to $count + 1 after 'add' operation.
            $this->assertCount(++$count, $collection);

            // The collection should NOT be empty after 'add' operation.
            $this->assertFalse($collection->isEmpty());
        }

        $count = count($testItems);
        foreach ($testItems as $object) {
            // The collection size should equal to $count before 'remove' operation.
            $this->assertCount($count, $collection);

            // The collection should NOT be empty before 'remove' operation.
            $this->assertFalse($collection->isEmpty());

            // Remove the item from the collection.
            $collection->remove($object);

            // The collection should NOT contain the removed item.
            $this->assertNotContains($object, $collection);     // via Traversable interface
            $this->assertFalse($collection->contains($object)); // via contains()

            $hash = $collection->getItemHash($object);

            // The collection should NOT have the hash of the removed item via ArrayAccess::offsetExists()
            $this->assertArrayNotHasKey($hash, $collection);

            // The removed item should be NOT accessed by its hash via ArrayAccess::offsetGet()
            $this->assertNull($collection[$hash]);

            // The collection size should equal to $count - 1 after 'remove' operation.
            $this->assertCount(--$count, $collection);
        }
        // The collection should be empty after all the items are removed.
        $this->assertTrue($collection->isEmpty());
    }

    /**
     * @testdox add() should call getItemHash() and offsetSet()
     */
    public function testAddShouldCallOffsetSet()
    {
        $itemClass  = $this->createCollectableClass();
        $collection = $this->getMockForCollection($itemClass, ['getItemHash', 'offsetSet']);
        $item       = $this->getMock($itemClass);
        $hash       = 'FOO';

        $collection->expects($this->once())->method('getItemHash')->with($this->identicalTo($item))->willReturn($hash);
        $collection->expects($this->once())->method('offsetSet')->with(
            $this->identicalTo($hash),
            $this->identicalTo($item)
        );

        $collection->add($item);
    }

    /**
     * @testdox add() should throw InvalidCollectableException for invalid collectable object
     */
    public function testAddShouldThrowInvalidCollectableException()
    {
        $collection    = $this->getMockForCollection($this->createCollectableClass());
        $invalidObject = $this->createCollectableObject();

        $this->setExpectedException(InvalidCollectableException::class);
        $collection->add($invalidObject);
    }

    /**
     * @testdox addAll() and removeAll()
     */
    public function testAddAllAndRemoveAll()
    {
        $dummyData   = $this->createTraversableCollection(3);
        $collectionA = $dummyData['collection'];
        $collectionB = $this->getMockForCollection($collectionA->getItemClass());

        $countBefore = count($collectionB);
        $collectionB->addAll($collectionA);

        foreach ($dummyData['items'] as $item) {
            // Assert the new item via contains() method.
            $this->assertTrue($collectionB->contains($item));
            // Assert the new item via Traversable interface.
            $this->assertContains($item, $collectionB);
        }
        $this->assertCount($countBefore + 3, $collectionB);

        $countBefore = count($collectionB);
        $collectionB->removeAll($collectionA);

        foreach ($dummyData['items'] as $item) {
            // Assert the removed item via contains() method.
            $this->assertFalse($collectionB->contains($item));
            // Assert the removed item via Traversable interface.
            $this->assertNotContains($item, $collectionB);
        }
        $this->assertCount($countBefore - 3, $collectionB);
    }

    /**
     * @testdox addAll() should call add() in iteration
     */
    public function testAddAllShouldCallAddInIteration()
    {
        $dummyData   = $this->createTraversableCollection(3);
        $collectionA = $dummyData['collection'];

        // collectionB can contain the items of $collectionA and will not cover 'add' method.
        $collectionB = $this->getMockForCollection($collectionA->getItemClass(), ['add']);
        foreach ($dummyData['items'] as $i => $item) {
            $collectionB->expects($this->at($i))->method('add')->with($this->identicalTo($item));
        }

        $collectionB->addAll($collectionA);
    }

    /**
     * @testdox addAll() should throw InvalidCollectableException for invalid collectable object
     */
    public function testAddAllShouldThrowInvalidCollectableException()
    {
        $collectionA = $this->createTraversableCollection(3)['collection'];
        // Create a collection with another dummy collectable class.
        $collectionB = $this->getMockForCollection($this->createCollectableClass());
        $this->setExpectedException(InvalidCollectableException::class);
        $collectionB->addAll($collectionA);
    }

    /**
     * @testdox contains() should throw InvalidCollectableException for invalid collectable object
     */
    public function testContainsShouldThrowInvalidCollectableException()
    {
        $collection    = $this->getMockForCollection($this->createCollectableClass());
        $invalidObject = $this->createCollectableObject();

        $this->setExpectedException(InvalidCollectableException::class);
        $collection->contains($invalidObject);
    }

    /**
     * @testdox contains() should call offsetExists()
     */
    public function testContainsShouldCallOffsetExists()
    {
        $itemClass  = $this->createCollectableClass();
        $collection = $this->getMockForCollection($itemClass, ['getItemHash', 'offsetExists']);

        $item       = $this->getMock($itemClass);
        $hash       = 'FOO';

        $collection->expects($this->once())->method('getItemHash')->with($this->identicalTo($item))->willReturn($hash);
        $collection->expects($this->once())->method('offsetExists')->with($this->identicalTo($hash));

        $collection->contains($item);
    }

    /**
     * @testdox remove() should throw InvalidCollectableException for invalid collectable object
     */
    public function testRemoveShouldThrowInvalidCollectableException()
    {
        $collection    = $this->getMockForCollection($this->createCollectableClass());
        $invalidObject = $this->createCollectableObject();

        $this->setExpectedException(InvalidCollectableException::class);
        $collection->remove($invalidObject);
    }

    /**
     * @testdox remove() should call offsetUnset()
     */
    public function testRemoveShouldCallOffsetUnset()
    {
        $itemClass  = $this->createCollectableClass();
        $collection = $this->getMockForCollection($itemClass, ['getItemHash', 'offsetUnset']);
        $item       = $this->getMock($itemClass);
        $hash       = 'FOO';

        $collection->expects($this->once())->method('getItemHash')->with($this->identicalTo($item))->willReturn($hash);
        $collection->expects($this->once())->method('offsetUnset')->with($this->identicalTo($hash));

        $collection->remove($item);
    }

    /**
     * @testdox removeAll() should call remove() in iteration
     */
    public function testRemoveAllShouldCallRemoveInIteration()
    {
        $dummyData   = $this->createTraversableCollection(3);
        $collectionA = $dummyData['collection'];

        // collectionB can contain the items of $collectionA and will not cover 'add' method.
        $collectionB = $this->getMockForCollection($collectionA->getItemClass(), ['remove']);
        foreach ($dummyData['items'] as $i => $item) {
            $collectionB->expects($this->at($i))->method('remove')->with($this->identicalTo($item));
        }

        $collectionB->removeAll($collectionA);
    }

    /**
     * @testdox removeAll() should throw InvalidCollectableException for invalid collectable object
     */
    public function testRemoveAllShouldThrowInvalidCollectableException()
    {
        $collectionA = $this->createTraversableCollection(3)['collection'];
        // collectionB can contain a different type of collectables.
        $collectionB = $this->getMockForCollection($this->createCollectableClass());
        $this->setExpectedException(InvalidCollectableException::class);
        $collectionB->removeAll($collectionA);
    }

    /**
     * @testdox removeAllExcept()
     */
    public function testRemoveAllExcept()
    {
        // Create dummy items.
        $itemClass = $this->createCollectableClass();
        $items     = [];
        for ($i = 0; $i < 5; $i++) {
            $items[$i] = $this->getMock($itemClass);
        }

        // @codeCoverageIgnoreStart
        $collectionA = $this->getMockForCollection($itemClass);
        // Add items #0, #1 and #2  to collectionA.
        for ($i = 0; $i < 3; $i++) {
            $collectionA->add($items[$i]);
        }
        $collectionB = $this->getMockForCollection($itemClass);
        // Add items #1, #2, #3 and #4  to collectionB.
        for ($i = 1; $i < 5; $i++) {
            $collectionB->add($items[$i]);
        }
        // @codeCoverageIgnoreEnd

        $collectionB->removeAllExcept($collectionA);

        $intersection = array_slice($items, 1, 2);

        $this->assertSubset($intersection, $collectionB);

        // The collection should contain the intersection items only.
        $this->assertSameSize($intersection, $collectionB);
        $addIndex = 0;
        foreach ($collectionB as $object) {
            $this->assertContains($object, $intersection);
            $this->assertArrayHasKey($addIndex, $intersection);
            $this->assertSame($intersection[$addIndex++], $object);
        }
    }

    /**
     * @testdox removeAllExcept() should throw InvalidCollectableException for invalid collectable object
     */
    public function testRemoveAllExceptShouldThrowInvalidCollectableException()
    {
        $collectionA = $this->createTraversableCollection(3)['collection'];
        // collectionB can contain a different type of collectables.
        $collectionB = $this->getMockForCollection($this->createCollectableClass());
        $this->setExpectedException(InvalidCollectableException::class);
        $collectionB->removeAllExcept($collectionA);
    }

    /**
     * @testdox clear() should remove all of the items
     */
    public function testClear()
    {
        $itemClass = $this->createCollectableClass();
        $items     = [];
        // @codeCoverageIgnoreStart
        $collection = $this->getMockForCollection($itemClass);
        for ($i = 0; $i < 3; $i++) {
            $items[$i] = $this->getMock($itemClass);
            $collection->add($items[$i]);
        }
        // @codeCoverageIgnoreEnd

        $collection->clear();

        // Collection should NOT contain any item.
        foreach ($items as $item) {
            $hash = $collection->getItemHash($item);
            // The collection should NOT contain the object.
            $this->assertFalse($collection->contains($item)); // by contains() method
            $this->assertNotContains($item, $collection);     // by Traversable interface

            // The collection should NOT have the hash.
            $this->assertArrayNotHasKey($hash, $collection); // by ArrayAccess::offsetExists()

            // The hash should reference to null.
            $this->assertNull($collection[$hash]); // by ArrayAccess::offsetGet()
        }

        // isEmpty() should return true.
        $this->assertTrue($collection->isEmpty());
        // Collection size should be 0.
        $this->assertCount(0, $collection);
    }

    /**
     * @testdox toArray() should export items as array
     */
    public function testToArray()
    {
        $itemClass = $this->createCollectableClass();
        $items     = [];
        // @codeCoverageIgnoreStart
        $collection = $this->getMockForCollection($itemClass);
        for ($addIndex = 0; $addIndex < 3; $addIndex++) {
            $items[$addIndex] = $this->getMock($itemClass);
            $collection->add($items[$addIndex]);
        }
        // @codeCoverageIgnoreEnd

        $array = $collection->toArray();

        // toArray() should return an array.
        $this->assertInternalType('array', $array);

        // The exported array should equal to the collection in many ways.
        $this->assertSetEquality($collection, $array, true, true);
    }

    /**
     * @testdox getIterator() should return an instance of ArrayIterator
     */
    public function testGetIteratorShouldReturnArrayIterator()
    {
        $collection = $this->getMockForCollection($this->createCollectableClass());
        $this->assertInstanceOf(ArrayIterator::class, $collection->getIterator());
    }

    /**
     * Create a dummy object that implements CollectableInterface.
     * @return \Gamegos\Util\Collection\CollectableInterface
     */
    protected function createCollectableObject()
    {
        return $this->prophesize(CollectableInterface::class)->reveal();
    }

    /**
     * Create a dummy class name that implements CollectableInterface.
     * @return string
     */
    protected function createCollectableClass()
    {
        return get_class($this->createCollectableObject());
    }

    /**
     * Create a dummy traversable collection with filled some items.
     * Returns created collection and items.
     * Returned collection has getIterator() and getItemClass() methods only.
     * @param  int $numItems
     * @return array [ 'collection' => CollectionInterface, 'items' => CollectableInterface[] ]
     */
    protected function createTraversableCollection($numItems)
    {
        $class = $this->createCollectableClass();
        $items = [];
        for ($i = 0; $i < $numItems; $i++) {
            $items[] = $this->getMock($class);
        }

        $collection = $this->getMock(CollectionInterface::class);
        $collection->method('getIterator')->willReturn(new ArrayIterator($items));
        $collection->method('getItemClass')->willReturn($class);

        return [
            'collection' => $collection,
            'items'      => $items,
        ];
    }

    /**
     * Create a collection instance without coveraging the constructor.
     * getItemClass() method is mocked initially.
     * @param  string $itemClass
     * @param  array $methods Additional methods to be mocked
     * @return \Gamegos\Util\Collection\Collection
     */
    protected function getMockForCollection($itemClass, $methods = [])
    {
        $methods    = array_unique(array_merge(['getItemClass'], $methods));
        $collection = $this->getMock(Collection::class, $methods, [], '', false, false);
        $collection->method('getItemClass')->willReturn($itemClass);
        return $collection;
    }

    /**
     * Assert that two sets are equal.
     * @param array|Collection  $setA
     * @param array|Collection  $setB
     * @param boolean $assertSameKeys to assert that each key in one set exists
     *                                and references the same object in another.
     * @param boolean $assertSameOrder to assert that items in two sets are in the same order on iteration.
     */
    protected function assertSetEquality($setA, $setB, $assertSameKeys = false, $assertSameOrder = false)
    {
        $this->assertSubset($setA, $setB, $assertSameKeys);
        $this->assertSubset($setB, $setA, $assertSameKeys);
        if ($assertSameOrder) {
            $this->assertSameOrder($setA, $setB);
        }
    }

    /**
     * Assert that a set is a subset of another set.
     * @param array|Collection $subset
     * @param array|Collection $superset
     * @param boolean $assertKeys to assert that each key in the subset exists
     *                            and references the same object in the superset.
     */
    protected function assertSubset($subset, $superset, $assertKeys = false)
    {
        $this->assertLessThanOrEqual(count($superset), count($subset));

        foreach ($subset as $key => $item) {
            $this->assertContains($item, $superset);
            if ($assertKeys) {
                $this->assertArrayHasKey($key, $superset);
                $this->assertSame($item, $superset[$key]);
                if ($superset instanceof Collection) {
                    $this->assertSame($superset->getItemHash($item), $key);
                }
            }
            if ($superset instanceof CollectionInterface) {
                $this->assertTrue($superset->contains($item));
            }
        }
    }

    /**
     * Assert that items in two sets are in the same order on iteration.
     * @param array|Collection $setA
     * @param array|Collection $setB
     */
    protected function assertSameOrder($setA, $setB)
    {
        $itemsA = [];
        foreach ($setA as $object) {
            $itemsA[] = $object;
        }
        $itemsB = [];
        foreach ($setB as $object) {
            $itemsB[] = $object;
        }
        $this->assertSame($itemsB, $itemsA);
    }
}
