<?php declare(strict_types=1);
/**
 * This file is part of Swoft.
 *
 * @link     https://swoft.org
 * @document https://swoft.org/docs
 * @contact  group@swoft.org
 * @license  https://github.com/swoft-cloud/swoft/blob/master/LICENSE
 */
namespace Swoft\Contract;

/**
 * Interface RouterInterface - base interface for service router
 *
 * @since 2.0
 */
interface RouterInterface
{
    /**
     * Found route
     */
    public const FOUND = 1;

    /**
     * Not found
     */
    public const NOT_FOUND = 2;
}
