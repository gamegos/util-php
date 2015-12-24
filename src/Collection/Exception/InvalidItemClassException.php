<?php
namespace Gamegos\Util\Collection\Exception;

/* Imports from PHP core */
use InvalidArgumentException;

/**
 * Invalid Item Class Exception
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class InvalidItemClassException extends InvalidArgumentException
{
    /**
     * Argument is not string.
     * @var int
     */
    const NOT_STRING = 1;

    /**
     * Argument is not name of an implementing class or sub-interface of Gamegos\Util\Collection\CollectableInterface.
     * @var int
     */
    const NOT_COLLECTABLE = 2;
}
