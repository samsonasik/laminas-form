<?php

/**
 * @see       https://github.com/laminas/laminas-form for the canonical source repository
 * @copyright https://github.com/laminas/laminas-form/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-form/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Form\Element;

use Laminas\Form\Element\Csrf as CsrfElement;
use LaminasTest\Form\TestAsset\CustomTraversable;
use PHPUnit\Framework\TestCase;

use function get_class;

class CsrfTest extends TestCase
{
    public function testProvidesInputSpecificationThatIncludesValidatorsBasedOnAttributes()
    {
        $element = new CsrfElement('foo');

        $inputSpec = $element->getInputSpecification();
        $this->assertArrayHasKey('validators', $inputSpec);
        $this->assertIsArray($inputSpec['validators']);

        $expectedClasses = [
            'Laminas\Validator\Csrf',
        ];
        foreach ($inputSpec['validators'] as $validator) {
            $class = get_class($validator);
            $this->assertContains($class, $expectedClasses, $class);
            switch ($class) {
                case 'Laminas\Validator\Csrf':
                    $this->assertEquals('foo', $validator->getName());
                    break;
                default:
                    break;
            }
        }
    }

    public function testAllowSettingCustomCsrfValidator()
    {
        $element = new CsrfElement('foo');
        $validatorMock = $this->createMock('Laminas\Validator\Csrf');
        $element->setCsrfValidator($validatorMock);
        $this->assertEquals($validatorMock, $element->getCsrfValidator());
    }

    public function testAllowSettingCsrfValidatorOptions()
    {
        $element = new CsrfElement('foo');
        $element->setCsrfValidatorOptions(['timeout' => 777]);
        $validator = $element->getCsrfValidator();
        $this->assertEquals('foo', $validator->getName());
        $this->assertEquals(777, $validator->getTimeout());
    }

    public function testAllowSettingCsrfOptions()
    {
        $element = new CsrfElement('foo');
        $element->setOptions([
            'csrf_options' => [
                'timeout' => 777,
                'salt' => 'MySalt',
            ],
        ]);
        $validator = $element->getCsrfValidator();
        $this->assertEquals('foo', $validator->getName());
        $this->assertEquals(777, $validator->getTimeOut());
        $this->assertEquals('MySalt', $validator->getSalt());
    }

    public function testSetOptionsTraversable()
    {
        $element = new CsrfElement('foo');
        $element->setOptions(new CustomTraversable([
            'csrf_options' => [
                'timeout' => 777,
                'salt' => 'MySalt',
            ],
        ]));
        $validator = $element->getCsrfValidator();
        $this->assertEquals('foo', $validator->getName());
        $this->assertEquals(777, $validator->getTimeOut());
        $this->assertEquals('MySalt', $validator->getSalt());
    }
}
