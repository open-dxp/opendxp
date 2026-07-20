<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Model\DataObject;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\AbstractModel;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;
use ReflectionProperty;

/**
 * Projects can register a custom DAO for a DataObject class (e.g.
 * App\Model\Product\Dao extending Concrete\Dao) and override getById() with
 * additional loading logic. Such overrides must keep being invoked when the
 * object is loaded, no matter how the core loads the standard case.
 *
 * The fixture DAO lives in the OpenDxp\Model\DataObject\Unittest namespace,
 * where the DAO class detection also finds it for nested classes (Listing,
 * objectbrick containers, ...). setUp() therefore captures the default DAO
 * mappings before the fixture class exists and pins them, so only the
 * Unittest model class itself resolves to the fixture.
 *
 * @group model.dataobject.custom-dao
 */
class CustomDaoGetByIdTest extends ModelTestCase
{
    /**
     * Default DAO mappings for Unittest and its nested classes, captured once
     * per process before the fixture DAO class is defined.
     *
     * @var array<string, string>
     */
    private static array $defaultUnittestDaoClasses = [];

    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        RuntimeCache::clear();

        $cacheProperty = new ReflectionProperty(AbstractModel::class, 'daoClassCache');

        if (!class_exists(Unittest\Dao::class, false)) {
            // first run in this process: resolve the DAO mappings of the
            // Unittest class and everything a full load touches while the
            // fixture DAO does not exist yet, and remember the results
            $warmup = TestHelper::createEmptyObject('custom-dao-warmup-');
            $warmup->save();
            RuntimeCache::clear();
            DataObject::getById($warmup->getId(), ['force' => true]);

            foreach ($cacheProperty->getValue() as $modelClass => $daoClass) {
                if ($modelClass === Unittest::class || str_starts_with($modelClass, Unittest::class . '\\')) {
                    self::$defaultUnittestDaoClasses[$modelClass] = $daoClass;
                }
            }
        }

        require_once __DIR__ . '/../../Support/Fixtures/UnittestCustomDao.php';

        // pin the default mappings for the nested classes and drop only the
        // Unittest entry, so the detection picks up the fixture for the
        // model class alone
        $cache = $cacheProperty->getValue();
        foreach (self::$defaultUnittestDaoClasses as $modelClass => $daoClass) {
            $cache[$modelClass] = $daoClass;
        }
        unset($cache[Unittest::class]);
        $cacheProperty->setValue(null, $cache);

        Unittest\Dao::$getByIdCalls = [];
    }

    protected function tearDown(): void
    {
        // restore the default mappings and drop anything that resolved to the
        // fixture, so it cannot leak into other tests
        $cacheProperty = new ReflectionProperty(AbstractModel::class, 'daoClassCache');
        $cache = $cacheProperty->getValue();
        foreach ($cache as $modelClass => $daoClass) {
            if (ltrim((string) $daoClass, '\\') === Unittest\Dao::class) {
                unset($cache[$modelClass]);
            }
        }
        foreach (self::$defaultUnittestDaoClasses as $modelClass => $daoClass) {
            $cache[$modelClass] = $daoClass;
        }
        $cache[Unittest::class] = self::$defaultUnittestDaoClasses[Unittest::class] ?? Concrete\Dao::class;
        $cacheProperty->setValue(null, $cache);

        Unittest\Dao::$getByIdCalls = [];
        RuntimeCache::clear();
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testCustomDaoGetByIdOverrideIsInvokedOnLoad(): void
    {
        $obj = TestHelper::createEmptyObject('custom-dao-');
        $obj->setInput('custom-dao-marker');
        $obj->save();
        $id = $obj->getId();

        $this->assertInstanceOf(
            Unittest\Dao::class,
            $obj->getDao(),
            'The fixture DAO must be picked up by the DAO class detection for this scenario'
        );

        RuntimeCache::clear();
        Unittest\Dao::$getByIdCalls = [];

        $loaded = DataObject::getById($id, ['force' => true]);

        $this->assertInstanceOf(Unittest::class, $loaded);
        $this->assertSame('custom-dao-marker', $loaded->getInput());
        $this->assertContains(
            $id,
            Unittest\Dao::$getByIdCalls,
            'A project-specific getById() override on the DAO must keep being invoked when the object is loaded'
        );
    }
}
