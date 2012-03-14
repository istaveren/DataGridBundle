<?php

// src/Blogger/BlogBundle/Tests/Entity/BlogTest.php

namespace Sorien\DataGridBundle\Tests\Grid\Source;

use Propel\PropelBundle\PropelBundle;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Sorien\DataGridBundle\Grid\Source\ModelSource;

class CompanyTest extends WebTestCase
{

  /**
   * setup before testing
   */
  public static function setUpBeforeClass()
  {
    $kernel = static::createKernel();
    $kernel->boot();

    parent::setUpBeforeClass();
  }

  /**
   * teardown after the test
   */
  public static function tearDownAfterClass()
  {
    parent::tearDownAfterClass();
  }

  /**
   * @group UNITTESTONLY
   */
  public function testModelSourceWithModel()
  {
    // instatiate model

    // instatiate modelSource with model

    // test outcome
  }


  /**
   * @group UNITTESTONLY
   */
  public function testModelSourceWithCriteria()
  {
    // instatiate crietria

    // instatiate modelSource with criteria

    // test outcome
  }
}
