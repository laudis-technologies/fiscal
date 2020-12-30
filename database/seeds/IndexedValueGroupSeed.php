<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Phinx\Seed\AbstractSeed;

final class IndexedValueGroupSeed extends AbstractSeed
{
    public function run(): void
    {
        $this->table('indexed_value_groups')
            ->insert([
                'name' => 'auto-generated',
                'slug' => 'auto-generated',
            ])
            ->save();
    }
}
