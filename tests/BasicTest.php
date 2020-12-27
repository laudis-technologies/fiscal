<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Fiscal\Tests;

use Exception;
use JsonException;
use Laudis\Fiscal\FiscalRepository;
use Laudis\Fiscal\IndexedValue;
use Laudis\Fiscal\IndexType;
use PDO;
use PHPUnit\Framework\TestCase;

final class BasicTest extends TestCase
{
    private FiscalRepository $repo;

    public function setUp(): void
    {
        parent::setUp();
        $this->repo = new FiscalRepository(new PDO('mysql:host=mariadb;dbname=test', 'root', 'test'));
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testIntegration(): void
    {
        $scales = $this->repo->loadScalesWithSlugs('2020-01-01', ['e', 'f']);
        $e = $scales->get('e');
        $f = $scales->get('f');

        self::assertEquals(31.35, $e->calculate(167));
        self::assertEquals(88, $f->calculate(250));
        self::assertEquals(
            '{"scale":[{"limitId":1,"factorId":3,"limitValue":110,"limitedValue":110,"previousLimit":0,"netValue":110,"factorValue":0.55,"ruleResult":60.50000000000001,"aggregation":60.50000000000001},{"limitId":2,"factorId":4,"limitValue":220,"limitedValue":220,"previousLimit":110,"netValue":110,"factorValue":0.25,"ruleResult":27.5,"aggregation":88},{"limitId":null,"factorId":null,"limitValue":1.7976931348623157e+308,"limitedValue":250,"previousLimit":220,"netValue":30,"factorValue":0,"ruleResult":0,"aggregation":88}],"indexedValues":{"3":{"id":3,"slug":"c","value":0.55,"type":"percentage","name":"c"},"1":{"id":1,"slug":"a","value":110,"type":"euro","name":"a"},"4":{"id":4,"slug":"d","value":0.25,"type":"percentage","name":"d"},"2":{"id":2,"slug":"b","value":220,"type":"euro","name":"b"}},"input":250,"output":88}', json_encode($f->explain(250), JSON_THROW_ON_ERROR));
        self::assertEquals('{"scale":[{"limitId":1,"factorId":3,"limitValue":110,"limitedValue":110,"previousLimit":0,"netValue":110,"factorValue":0.55,"ruleResult":60.50000000000001,"aggregation":60.50000000000001},{"limitId":2,"factorId":4,"limitValue":220,"limitedValue":220,"previousLimit":110,"netValue":110,"factorValue":0.25,"ruleResult":27.5,"aggregation":88},{"limitId":null,"factorId":null,"limitValue":1.7976931348623157e+308,"limitedValue":250,"previousLimit":220,"netValue":30,"factorValue":0,"ruleResult":0,"aggregation":88}],"indexedValues":{"3":{"id":3,"slug":"c","value":0.55,"type":"percentage","name":"c"},"1":{"id":1,"slug":"a","value":110,"type":"euro","name":"a"},"4":{"id":4,"slug":"d","value":0.25,"type":"percentage","name":"d"},"2":{"id":2,"slug":"b","value":220,"type":"euro","name":"b"}},"input":250,"output":88}', json_encode($f->explain(250), JSON_THROW_ON_ERROR));
        self::assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testBySlug(): void
    {
        $scales = $this->repo->loadIndexedValuesWithSlugs('2020-01-01', ['a', 'b', 'c', 'd']);
        self::assertEquals([
            'a' => new IndexedValue(1, 'a', 'a', 110.0, IndexType::EURO()),
            'b' => new IndexedValue(2, 'b', 'b', 220.0, IndexType::EURO()),
            'c' => new IndexedValue(3, 'c', 'c', 0.55, IndexType::PERCENTAGE()),
            'd' => new IndexedValue(4, 'd', 'd', 0.25, IndexType::PERCENTAGE()),
        ], $scales->toArray());
    }
}
