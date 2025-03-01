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
 * Interface DefinitionInterface
 *
 * @since 2.0
 */
interface DefinitionInterface
{
    /**
     * Bean definitions
     *
     * @return array
     *
     * [
     *  'bean name' => [
     *      'class' => MyBean::class
     *      ...
     *  ]
     * ]
     */
    public function beans(): array;
}
