<?php

use Aleph\Core\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Core\Exception class.
 *
 * @group core
 */
class CoreExceptionTest extends TestCase
{
    /**
     * Includes Aleph\Core\Exception class.
     */
    public static function setUpBeforeClass()
    {
        require_once(__DIR__ . '/../Core/Exception.php');
    }

    /**
     * Checks obtaining information from an exception.
     *
     * @covers Exception::getData
     */
    public function testData()
    {
        $exc = new Exception('error', 'some data');
        $this->assertEquals($exc->getData(), 'some data');
    }

    /**
     * Checks obtaining information from an exception.
     *
     * @covers Exception::getDataOrMessage
     */
    public function testDataOrMessage()
    {
        $exc = new Exception('error', 'some data');
        $this->assertEquals($exc->getDataOrMessage(), 'some data');
        $exc = new Exception('error');
        $this->assertEquals($exc->getDataOrMessage(), 'error');
    }
}