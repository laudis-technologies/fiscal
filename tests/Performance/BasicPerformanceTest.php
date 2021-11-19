<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <https://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Fiscal\Tests\Performance;

use Laudis\Fiscal\FiscalRepository;
use PDO;
use Phinx\Console\PhinxApplication;
use Phinx\Wrapper\TextWrapper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BasicPerformanceTest extends TestCase
{
    private FiscalRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $pdo = new PDO('mysql:host=mariadb;dbname=test', 'root', 'test');
        $this->repo = new FiscalRepository($pdo);
    }

    /**
     * @dataProvider provideScaleSlugs
     */
    public function testAllAtOnce(string $scale): void
    {
        $versions = $this->repo->loadVersions([$scale]);
        self::assertGreaterThan(10, $versions->get($scale)->count());
    }

    public function provideScaleSlugs(): array
    {
        $pdo = new PDO('mysql:host=mariadb;dbname=test', 'root', 'test');
        $result = $pdo->query('SELECT scales.slug FROM scales')->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) < 10) {
            $app = new PhinxApplication();
            $wrap = new TextWrapper($app);

            $wrap->setOption('configuration', __DIR__.'/../../phinx.php');
            $wrap->getSeed();
            if ($wrap->getExitCode() !== 0) {
                throw new RuntimeException('Seed failed');
            }
            $result = $pdo->query('SELECT scales.slug FROM scales')->fetchAll(PDO::FETCH_ASSOC);
        }

        $scales = array_column($result, 'slug');
        $tbr = [];
        foreach ($scales as $scale) {
            $tbr[$scale] = [$scale];
        }

        return $tbr;
    }
}
