<?php
namespace Gamegos\Util\Collection;

/* Imports from PHP Core */
use Countable;
use IteratorAggregate;
use ArrayAccess;

/**
 * Interface for Collection Classes
 * @package gamegos/util
 * @author  Safak Ozpinar <safak@gamegos.com>
 */
interface CollectionInterface extends Countable, IteratorAggregate, ArrayAccess
{
    /**
     * Get the class name of objects this collection can store.
     * @return string
     */
    public function getItemClass();

    /**
     * Add an object to this collection.
     * @param \Gamegos\Util\Collection\CollectableInterface $object
     */
    public function add(CollectableInterface $object);

    /**
     * Add all objects from another collection to this collection.
     * @param \Gamegos\Util\Collection\CollectionInterface $collection
     */
    public function addAll(CollectionInterface $collection);

    /**
     * Check if this collection contains a specific object.
     * @param  \Gamegos\Util\Collection\CollectableInterface $object
     * @return boolean
     */
    public function contains(CollectableInterface $object);

    /**
     * Check if this collection has objects.
     * @return bool
     */
    public function isEmpty();

    /**
     * Remove an object from this collection.
     * @param \Gamegos\Util\Collection\CollectableInterface $object
     */
    public function remove(CollectableInterface $object);

    /**
     * Remove objects contained in another collection from this collection.
     * @param \Gamegos\Util\Collection\Collection $collection
     *        The collection that contains objects to be removed from this collection.
     */
    public function removeAll(CollectionInterface $collection);

    /**
     * Remove all objects except for those contained in another collection from this collection.
     * @param \Gamegos\Util\Collection\Collection $collection
     *        The collection that contains excluded objects.
     */
    public function removeAllExcept(CollectionInterface $collection);

    /**
     * Remove all objects from this collection.
     */
    public function clear();

    /**
     * Get collection items as array.
     * @return array
     */
    public function toArray();
}
