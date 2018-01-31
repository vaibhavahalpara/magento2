<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup;

use Magento\Setup\Model\Declaration\Schema\Dto\Columns\Timestamp;
use Magento\Setup\Model\Declaration\Schema\Dto\Constraints\Internal;
use Magento\Setup\Model\Declaration\Schema\Dto\Constraints\Reference;
use Magento\Setup\Model\Declaration\Schema\SchemaConfig;
use Magento\TestFramework\Deploy\CliCommand;
use Magento\TestFramework\Deploy\TestModuleManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\SetupTestCase;

/**
 * The purpose of this test is verifying initial InstallSchema, InstallData scripts.
 */
class DeclarativeSchemaBuilderTest extends SetupTestCase
{
    /**
     * @var  TestModuleManager
     */
    private $moduleManager;

    /**
     * @var SchemaConfig
     */
    private $schemaConfig;

    /**
     * @var CliCommand
     */
    private $cliCommad;

    public function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->schemaConfig = $objectManager->create(SchemaConfig::class);
        $this->moduleManager = $objectManager->get(TestModuleManager::class);
        $this->cliCommad = $objectManager->get(CliCommand::class);
    }

    /**
     * Tests primary key constraint conversion from XML and renamed functionality.
     *
     * @moduleName Magento_TestSetupDeclarationModule1
     */
    public function testSchemaBuilder()
    {
        $this->cliCommad->install(
            ['Magento_TestSetupDeclarationModule1']
        );
        $dbSchema = $this->schemaConfig->getDeclarationConfig();
        $schemaTables = $dbSchema->getTables();
        $this->assertArrayHasKey('reference_table', $dbSchema->getTables());
        $this->assertArrayHasKey('test_table', $dbSchema->getTables());
        //Test primary key and renaming
        $referenceTable = $schemaTables['reference_table'];
        /**
        * @var Internal $primaryKey
        */
        $primaryKey = $referenceTable->getPrimaryConstraint();
        $columns = $primaryKey->getColumns();
        $this->assertEquals('tinyint_ref', reset($columns)->getName());
        //Test column
        $testTable = $schemaTables['test_table'];
        /**
        * @var Timestamp $timestampColumn
        */
        $timestampColumn = $testTable->getColumnByName('timestamp');
        $this->assertEquals('CURRENT_TIMESTAMP', $timestampColumn->getOnUpdate());
        //Test disabled
        $this->assertArrayNotHasKey('varbinary_rename', $testTable->getColumns());
        //Test foreign key
        /**
        * @var Reference $foreignKey
        */
        $foreignKey = $testTable->getConstraintByName('some_foreign_key');
        $this->assertEquals('NO ACTION', $foreignKey->getOnDelete());
        $this->assertEquals('tinyint_ref', $foreignKey->getReferenceColumn()->getName());
    }
}
