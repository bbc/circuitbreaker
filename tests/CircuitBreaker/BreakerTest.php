<?php

namespace betandr\CircuitBreaker\Tests;

use betandr\CircuitBreaker\Breaker;
use betandr\CircuitBreaker\Persistence\ArrayPersistence;

class BreakerTest extends \PHPUnit_Framework_TestCase
{

    public function testIsClosedOnConstruction()
    {
        $breaker = Breaker::build('testBreaker', new ArrayPersistence);
        $this->assertTrue($breaker->isClosed(), 'Breaker should be closed when constructed');
    }

    public function testBreakerOpensWhenThresholdReached()
    {
        $threshold = 1;
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('threshold' => $threshold));

        $breaker->failure();

        $this->assertTrue($breaker->isOpen(), 'Breaker should be open when threshold reached');
    }

    public function testBreakerDoesntOpenUntilThresholdReached()
    {
        $threshold = 2;
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('threshold' => $threshold));

        $breaker->failure();

        $this->assertTrue($breaker->isClosed(), 'Breaker should be closed before threshold reached');
    }

    public function testOpenBreakerClosesWhenSuccessRegistered()
    {
        $threshold = 1;
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('threshold' => $threshold));

        $breaker->failure();
        $this->assertTrue($breaker->isOpen(), 'Breaker should be open when threshold reached');

        $breaker->success();
        $this->assertTrue($breaker->isClosed(), 'Breaker should re-close when success registered');
    }

    public function testSettersOverrideParams()
    {
        $threshold = 99;
        $timeout = 999;
        $willRetry = false;

        $breaker = Breaker::build(
            'testBreaker',
            new ArrayPersistence,
            array(
                'timeout' => $timeout,
                'threshold' => $threshold,
                'retry' => $willRetry
            )
        );

        $newThreshold = 1;
        $newTimeout = 1;
        $newWillRetry = true;

        $breaker->setTimeout($newThreshold);
        $breaker->setThreshold($newTimeout);
        $breaker->setWillRetryAfterTimeout($newWillRetry);

        $this->assertEquals($newTimeout, $breaker->getTimeout(), 'Timeout should be set in params');
        $this->assertEquals($newThreshold, $breaker->getThreshold(), 'Threshold should be set in params');
        $this->assertTrue($breaker->getWillRetryAfterTimeout(), 'Will Retry should be set in params');
    }

    public function testOpeningBreaker()
    {
        $breaker = Breaker::build('testBreaker', new ArrayPersistence);

        $breaker->open();
        $this->assertTrue($breaker->isOpen(), 'Breaker should be open when explicitly opened');
    }

    public function testReClosingBreaker()
    {
        $breaker = Breaker::build('testBreaker', new ArrayPersistence);

        $breaker->open();
        $this->assertTrue($breaker->isOpen(), 'Breaker should be open when explicitly opened');

        $breaker->close();
        $this->assertTrue($breaker->isClosed(), 'Breaker should be closed when explicitly closed');
    }

    public function testResettingBreaker()
    {
        $breaker = Breaker::build('testBreaker', new ArrayPersistence);

        $breaker->open();
        $this->assertTrue($breaker->isOpen(), 'Breaker should be open when explicitly opened');

        $breaker->reset();
        $this->assertTrue($breaker->isClosed(), 'Breaker should be closed when reset');
    }

    public function testDefaultThresholdValue()
    {
        $breaker = Breaker::build('testBreaker', new ArrayPersistence);

        $expectedThreshold = 25;
        $this->assertEquals($expectedThreshold, $breaker->getThreshold(), 'Threshold should be '.$expectedThreshold.' by default');
    }

    public function testDefaultTimeoutValue()
    {
        $breaker = Breaker::build('testBreaker', new ArrayPersistence);

        $expectedTimeout = 6000;
        $this->assertEquals($expectedTimeout, $breaker->getTimeout(), 'Timeout should be '.$expectedTimeout.' by default');
    }

    public function testSettingThresholdViaParams()
    {
        $threshold = 99;
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('threshold' => $threshold));

        $this->assertEquals($threshold, $breaker->getThreshold(), 'Threshold should be set in params');
    }

    public function testSettingTimeoutViaParams()
    {
        $timeout = 999;
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('timeout' => $timeout));

        $this->assertEquals($timeout, $breaker->getTimeout(), 'Timeout should be set in params');
    }

    public function testNonIntegerValueWillNotAffectThreshold()
    {
        $defaultThreshold = 25;
        $threshold = "INVALID_VALUE";
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('threshold' => $threshold));

        $this->assertEquals($defaultThreshold, $breaker->getThreshold(), 'Threshold be default if invalid value used');
    }

    public function testNonIntegerValueWillNotAffectTimeout()
    {
        $defaultTimeout = 6000;
        $timeout = "INVALID_VALUE";
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('timeout' => $timeout));

        $this->assertEquals($defaultTimeout, $breaker->getTimeout(), 'Threshold be default if invalid value used');
    }

    public function testNameIsSetCorrectly()
    {
        $breakerName = 'testBreaker'.time();

        $breaker = Breaker::build($breakerName, new ArrayPersistence);
        $this->assertEquals($breakerName, $breaker->getName(), 'Breaker name should be set by constructor');
    }

    public function testNonBooleanValueWillNotAffectWillRetry()
    {
        $willRetry = "INVALID_VALUE";

        $breaker = Breaker::build(
            'testBreaker',
            new ArrayPersistence,
            array(
                'retry' => $willRetry
            )
        );

        $this->assertTrue($breaker->getWillRetryAfterTimeout(), 'Will Retry should be true if non-boolean value used');
    }

    public function testSettingAllParams()
    {
        $threshold = 99;
        $timeout = 999;
        $willRetry = false;

        $breaker = Breaker::build(
            'testBreaker',
            new ArrayPersistence,
            array(
                'timeout' => $timeout,
                'threshold' => $threshold,
                'retry' => $willRetry
            )
        );

        $this->assertEquals($timeout, $breaker->getTimeout(), 'Timeout should be set in params');
        $this->assertEquals($threshold, $breaker->getThreshold(), 'Threshold should be set in params');
        $this->assertFalse($breaker->getWillRetryAfterTimeout(), 'Will Retry should be set in params');
    }

    public function testSettingWillRetryViaParams()
    {
        $willRetry = false;
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('retry' => $willRetry));

        $this->assertFalse($breaker->getWillRetryAfterTimeout(), 'Will retry should be set in params');
    }

    public function testLastFailureTime()
    {
        $threshold = 1;
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('threshold' => $threshold));

        $this->assertNull($breaker->getLastFailureTime(), 'Last failure should be null for new breaker');

        $breaker->failure();

        $this->assertNotNull($breaker->getLastFailureTime(), 'Last failure should NOT be null after failure');
    }

    public function testThatOnClosedWillReturnTrueIfOpenAndTimeoutExpired()
    {
        $timeout = 0; // immediate timeout
        $threshold = 1;
        $breaker = Breaker::build(
            'testBreaker',
            new ArrayPersistence,
            array(
                'timeout' => $timeout,
                'threshold' => $threshold
            )
        );

        $this->assertTrue($breaker->isClosed(), 'Breaker should be closed closed by default');

        $breaker->failure();

        $this->assertTrue($breaker->isClosed(), 'Breaker should appear to be closed on timeout');
    }

    public function testThatOnClosedWillNeverReturnTrueIfWillRetryIsFalse()
    {
        $threshold = 1;
        $willRetry = false;
        $breaker = Breaker::build('testBreaker', new ArrayPersistence, array('threshold' => $threshold));

        $this->assertTrue($breaker->isClosed(), 'Breaker should be closed closed by default');

        $breaker->failure();

        $this->assertTrue($breaker->isOpen(), 'Breaker should be open when failure threshold reached and retry is false');
    }
}
