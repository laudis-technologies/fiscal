<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <https://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Fiscal;

use Laudis\TypedEnum\TypedEnum;

/**
 * @method static IndexType EURO()
 * @method static IndexType PERCENTAGE()
 * @method static IndexType CONSTANT()
 *
 * @template T = string
 * @extends TypedEnum<T>
 */
final class IndexType extends TypedEnum
{
    private const EURO = 'euro';
    private const PERCENTAGE = 'percentage';
    private const CONSTANT = 'constant';
}
