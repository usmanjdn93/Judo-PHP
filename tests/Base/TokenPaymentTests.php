<?php
/**
 * @author Oleg Fetisov <ofetisov@corevalue.net>
 */

namespace Tests\Base;

use PHPUnit_Framework_TestCase;
use Tests\Builders\CardPaymentBuilder;
use Tests\Builders\TokenPaymentBuilder;
use Tests\Builders\TokenPreauthBuilder;
use Tests\Helpers\AssertionHelper;
use Tests\Helpers\ConfigHelper;

abstract class TokenPaymentTests extends PHPUnit_Framework_TestCase
{
    const CONSUMER_REFERENCE = '1234512345';
    protected $cardToken;
    protected $consumerToken;

    /**
     * @return TokenPaymentBuilder|TokenPreauthBuilder
     */
    abstract protected function getBuilder();

    public function setUp()
    {
        $builder = new CardPaymentBuilder();
        $cardPayment = $builder->setAttribute('yourConsumerReference', self::CONSUMER_REFERENCE)
            ->build(ConfigHelper::getConfig());

        $result = $cardPayment->create();

        AssertionHelper::assertSuccessfulPayment($result);

        $this->cardToken = $result['cardDetails']['cardToken'];
        $this->consumerToken = $result['consumer']['consumerToken'];
    }

    public function testValidTokenPayment()
    {
        $tokenPayment = $this->getBuilder()
            ->build(ConfigHelper::getConfig());
        $result = $tokenPayment->create();

        AssertionHelper::assertSuccessfulPayment($result);
    }

    public function testDeclinedTokenPayment()
    {
        $tokenPayment = $this->getBuilder()
            ->setAttribute('cv2', '666')
            ->build(ConfigHelper::getConfig());

        $result = $tokenPayment->create();

        AssertionHelper::assertDeclinedPayment($result);
    }

    public function testTokenPaymentAndWithoutToken()
    {
        $this->setExpectedException('\Judopay\Exception\ValidationError', 'Missing required fields');

        $tokenPayment = $this->getBuilder()
            ->unsetAttribute('consumerToken')
            ->build(ConfigHelper::getConfig());

        $tokenPayment->create();
    }

    public function testTokenPaymentWithoutCv2AndWithoutToken()
    {
        $this->setExpectedException('\Judopay\Exception\ValidationError', 'Missing required fields');

        $tokenPayment = $this->getBuilder()
            ->unsetAttribute('consumerToken')
            ->unsetAttribute('cv2')
            ->build(ConfigHelper::getConfig());

        $tokenPayment->create();
    }

    public function testTokenPaymentWithNegativeAmount()
    {
        $tokenPayment = $this->getBuilder()
            ->setAttribute('amount', -1)
            ->build(ConfigHelper::getConfig());

        try {
            $tokenPayment->create();
        } catch (\Exception $e) {
            AssertionHelper::assertApiExceptionWithModelErrors($e, 1, 1);

            return;
        }
    }

    public function testTokenPaymentWithZeroAmount()
    {
        $tokenPayment = $this->getBuilder()
            ->setAttribute('amount', 0)
            ->build(ConfigHelper::getConfig());

        try {
            $tokenPayment->create();
        } catch (\Exception $e) {
            AssertionHelper::assertApiExceptionWithModelErrors($e, 1, 1);

            return;
        }
    }

    public function testTokenPaymentWithoutCurrency()
    {
        $tokenPayment = $this->getBuilder()
            ->unsetAttribute('currency')
            ->build(ConfigHelper::getConfig());

        $result = $tokenPayment->create();

        AssertionHelper::assertSuccessfulPayment($result);
    }

    public function testTokenPaymentWithUnknownCurrency()
    {
        $tokenPayment = $this->getBuilder()
            ->setAttribute('currency', 'ZZZ')
            ->build(ConfigHelper::getConfig());

        $result = $tokenPayment->create();

        AssertionHelper::assertSuccessfulPayment($result);
    }

    public function testTokenPaymentWithoutReference()
    {
        $this->setExpectedException('\Judopay\Exception\ValidationError', 'Missing required fields');

        $tokenPayment = $this->getBuilder()
            ->unsetAttribute('yourConsumerReference')
            ->build(ConfigHelper::getConfig());

        $tokenPayment->create();
    }

    public function testDeclinedTokenPaymentWithoutCv2()
    {
        $tokenPayment = $this->getBuilder()
            ->unsetAttribute('cv2')
            ->build(ConfigHelper::getConfig());

        $result = $tokenPayment->create();

        AssertionHelper::assertDeclinedPayment($result);
    }

    public function testTokenPaymentWithoutCv2AndWithNegativeAmount()
    {
        $tokenPayment = $this->getBuilder()
            ->setAttribute('amount', -1)
            ->unsetAttribute('cv2')
            ->build(ConfigHelper::getConfig());

        try {
            $tokenPayment->create();
        } catch (\Exception $e) {
            AssertionHelper::assertApiExceptionWithModelErrors($e, 1, 1);

            return;
        }
    }

    public function testTokenPaymentWithoutCv2AndWithZeroAmount()
    {
        $tokenPayment = $this->getBuilder()
            ->setAttribute('amount', 0)
            ->unsetAttribute('cv2')
            ->build(ConfigHelper::getConfig());

        try {
            $tokenPayment->create();
        } catch (\Exception $e) {
            AssertionHelper::assertApiExceptionWithModelErrors($e, 1, 1);

            return;
        }
    }

    public function testTokenPaymentWithoutCv2AndWithoutCurrency()
    {
        $tokenPayment = $this->getBuilder()
            ->unsetAttribute('currency')
            ->unsetAttribute('cv2')
            ->build(ConfigHelper::getConfig());

        $result = $tokenPayment->create();

        AssertionHelper::assertDeclinedPayment($result);
    }

    public function testTokenPaymentWithoutCv2AndWithUnknownCurrency()
    {
        $tokenPayment = $this->getBuilder()
            ->setAttribute('currency', 'ZZZ')
            ->unsetAttribute('cv2')
            ->build(ConfigHelper::getConfig());

        $result = $tokenPayment->create();

        AssertionHelper::assertDeclinedPayment($result);
    }

    public function testTokenPaymentWithoutCv2AndWithoutReference()
    {
        $this->setExpectedException('\Judopay\Exception\ValidationError', 'Missing required fields');

        $tokenPayment = $this->getBuilder()
            ->unsetAttribute('yourConsumerReference')
            ->unsetAttribute('cv2')
            ->build(ConfigHelper::getConfig());

        $tokenPayment->create();
    }
}
