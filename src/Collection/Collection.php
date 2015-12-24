<?php
namespace Gamegos\Util\Collection;

/* Imports from PHP Core */
use ArrayIterator;
use OutOfBoundsException;

/* Imports from Gamegos\Util */
use Gamegos\Util\Collection\Exception\InvalidItemClassException;
use Gamegos\Util\Collection\Exception\InvalidCollectableException;

/**
 * Generic Collection Implementation
 * @package gamegos/util
 * @author  Safak Ozpinar <safak@gamegos.com>
 */
class Collection implements CollectionInterface
{
    /**
     * Class name of storable objects.
     * @var string
     */
    private $itemClass = '';

    /**
     * Collection items
     * @var array
     */
    private $items = [];

    /**
     * Constructor
     * @param  string $itemClass Class/interface name of storable objects.
     * @throws \Gamegos\Util\Collection\Exception\InvalidItemClassException
     *         <br> • Item class is not string.
     *         <br> • Item class is not a sub-interface or implementing class of CollectableInterface.
     */
    public function __construct($itemClass)
    {
        if (!is_string($itemClass)) {
            $message = sprintf('Item class must be string, %s given.', gettype($itemClass));
            throw new InvalidItemClassException($message, InvalidItemClassException::NOT_STRING);
        }

        if (is_subclass_of($itemClass, CollectableInterface::class)) {
            $this->itemClass = $itemClass;
        } else {
            $message = sprintf('Item class must extend or implement %s interface!', CollectableInterface::class);
            throw new InvalidItemClassException($message, InvalidItemClassException::NOT_COLLECTABLE);
        }
    }

    /**
     * Get a unique identifier for the object.
     * This identifier is used as index of the object in the collection.
     * @param  \Gamegos\Util\Collection\CollectableInterface $object
     * @return string
     */
    public function getItemHash(CollectableInterface $object)
    {
        return spl_object_hash($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return $this->itemClass;
    }

    /**
     * {@inheritdoc}
     * @throws \Gamegos\Util\Collection\Exception\InvalidCollectableException
     */
    public function add(CollectableInterface $object)
    {
        $this->offsetSet($this->getItemHash($object), $object);
    }

    /**
     * {@inheritdoc}
     * @throws \Gamegos\Util\Collection\Exception\InvalidCollectableException
     */
    public function addAll(CollectionInterface $collection)
    {
        foreach ($collection as $object) {
            $this->add($object);
        }
    }

    /**
     * {@inheritdoc}
     * @throws \Gamegos\Util\Collection\Exception\InvalidCollectableException
     */
    public function contains(CollectableInterface $object)
    {
        $this->validateClass($object);
        return $this->offsetExists($this->getItemHash($object));
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * {@inheritdoc}
     * @throws \Gamegos\Util\Collection\Exception\InvalidCollectableException
     * @throws \OutOfBoundsException If the specified object does not exist.
     */
    public function remove(CollectableInterface $object)
    {
        $this->validateClass($object);
        $this->offsetUnset($this->getItemHash($object));
    }

    /**
     * {@inheritdoc}
     * @throws \Gamegos\Util\Collection\Exception\InvalidCollectableException
     * @throws \OutOfBoundsException If the specified object does not exist.
     */
    public function removeAll(CollectionInterface $collection)
    {
        foreach ($collection as $object) {
            $this->remove($object);
        }
    }

    /**
     * {@inheritdoc}
     * @throws \Gamegos\Util\Collection\Exception\InvalidCollectableException
     */
    public function removeAllExcept(CollectionInterface $collection)
    {
        // Find the intersection set of the collections.
        $intersection = [];
        foreach ($collection as $object) {
            if ($this->contains($object)) {
                $intersection[] = $object;
            }
        }
        // Clear the collection and add.
        $this->clear();
        foreach ($intersection as $object) {
            $this->internalSet($this->getItemHash($object), $object);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->items = [];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : null;
    }

    /**
     * {@inheritdoc}
     * @throws \Gamegos\Util\Collection\Exception\InvalidCollectableException
     */
    public function offsetSet($key, $object)
    {
        $this->validateClass($object);
        $this->internalSet($key, $object);
    }

    /**
     * {@inheritdoc}
     * @throws \OutOfBoundsException If the specified object does not exist.
     */
    public function offsetUnset($key)
    {
        if ($this->offsetExists($key)) {
            unset($this->items[$key]);
        } else {
            throw new OutOfBoundsException("Item #{$key} not found in the collection.");
        }
    }

    /**
     * Internal set operation.
     * @param string $hash
     * @param \Gamegos\Util\Collection\CollectableInterface $object
     */
    private function internalSet($hash, CollectableInterface $object)
    {
        $this->items[$hash] = $object;
    }

    /**
     * Validate class name of a collectable object.
     * @param  \Gamegos\Util\Collection\CollectableInterface $object
     * @throws \Gamegos\Util\Collection\Exception\InvalidCollectableException
     */
    private function validateClass(CollectableInterface $object)
    {
        if (!is_a($object, $this->getItemClass())) {
            $message = sprintf('%s can contain only %s instances!', get_class($this), $this->getItemClass());
            throw new InvalidCollectableException($message);
        }
    }
}
