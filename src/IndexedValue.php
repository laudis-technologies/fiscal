<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Fiscal;

final class IndexedValue
{
    private float $value;
    private IndexType $type;
    private string $slug;
    private int $id;
    private string $name;
    private int $precision;

    public function __construct(int $id, string $slug, string $name, float $value, IndexType $type, int $precision)
    {
        $this->value = $value;
        $this->type = $type;
        $this->slug = $slug;
        $this->id = $id;
        $this->name = $name;
        $this->precision = $precision;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getType(): IndexType
    {
        return $this->type;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }
}
