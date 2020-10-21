<?php

/**
 * @see       https://github.com/laminas/laminas-form for the canonical source repository
 * @copyright https://github.com/laminas/laminas-form/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-form/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Form\View\Helper;

use ArrayObject;
use Laminas\Form\Element;
use Laminas\Form\View\Helper\FormLabel as FormLabelHelper;

use function sprintf;

class FormLabelTest extends CommonTestCase
{
    protected function setUp(): void
    {
        $this->helper = new FormLabelHelper();
        parent::setUp();
    }

    public function testCanEmitStartTagOnly()
    {
        $markup = $this->helper->openTag();
        $this->assertEquals('<label>', $markup);
    }

    public function testOpenTagWithWrongElementRaisesException()
    {
        $element = new ArrayObject();
        $this->expectException('Laminas\Form\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('ArrayObject');
        $this->helper->openTag($element);
    }

    public function testPassingArrayToOpenTagRendersAttributes()
    {
        $attributes = [
            'class'     => 'email-label',
            'data-type' => 'label',
        ];
        $markup = $this->helper->openTag($attributes);

        foreach ($attributes as $key => $value) {
            $this->assertStringContainsString(sprintf('%s="%s"', $key, $value), $markup);
        }
    }

    public function testCanEmitCloseTagOnly()
    {
        $markup = $this->helper->closeTag();
        $this->assertEquals('</label>', $markup);
    }

    public function testPassingElementToOpenTagWillUseNameInForAttributeIfNoIdPresent()
    {
        $element = new Element('foo');
        $markup = $this->helper->openTag($element);
        $this->assertStringContainsString('for="foo"', $markup);
    }

    public function testPassingElementToOpenTagWillUseIdInForAttributeWhenPresent()
    {
        $element = new Element('foo');
        $element->setAttribute('id', 'bar');
        $markup = $this->helper->openTag($element);
        $this->assertStringContainsString('for="bar"', $markup);
    }

    public function testPassingElementToInvokeWillRaiseExceptionIfNoNameOrIdAttributePresent()
    {
        $element = new Element();
        $this->expectException('Laminas\Form\Exception\DomainException');
        $this->expectExceptionMessage('id');
        $markup = $this->helper->__invoke($element);
    }

    public function testPassingElementToInvokeWillRaiseExceptionIfNoLabelAttributePresent()
    {
        $element = new Element('foo');
        $this->expectException('Laminas\Form\Exception\DomainException');
        $this->expectExceptionMessage('label');
        $markup = $this->helper->__invoke($element);
    }

    public function testPassingElementToInvokeGeneratesLabelMarkup()
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');
        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('>The value for foo:<', $markup);
        $this->assertStringContainsString('for="foo"', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('</label>', $markup);
    }

    public function testPassingElementAndContentToInvokeUsesContentForLabel()
    {
        $element = new Element('foo');
        $markup = $this->helper->__invoke($element, 'The value for foo:');
        $this->assertStringContainsString('>The value for foo:<', $markup);
        $this->assertStringContainsString('for="foo"', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('</label>', $markup);
    }

    public function testPassingElementAndContentAndFlagToInvokeUsesLabelAttribute()
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');
        $markup = $this->helper->__invoke($element, '<input type="text" id="foo" />', FormLabelHelper::PREPEND);
        $this->assertStringContainsString('>The value for foo:<input', $markup);
        $this->assertStringContainsString('for="foo"', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('></label>', $markup);
        $this->assertStringContainsString('<input type="text" id="foo" />', $markup);
    }

    public function testCanAppendLabelContentUsingFlagToInvoke()
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');
        $markup = $this->helper->__invoke($element, '<input type="text" id="foo" />', FormLabelHelper::APPEND);
        $this->assertStringContainsString('"foo" />The value for foo:</label>', $markup);
        $this->assertStringContainsString('for="foo"', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('><input type="text" id="foo" />', $markup);
    }

    public function testsetLabelAttributes()
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');
        $element->setLabelAttributes(['id' => 'bar']);
        $markup = $this->helper->__invoke($element, '<input type="text" id="foo" />', FormLabelHelper::APPEND);
        $this->assertStringContainsString('"foo" />The value for foo:</label>', $markup);
        $this->assertStringContainsString('id="bar" for="foo"', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('><input type="text" id="foo" />', $markup);
    }

    public function testPassingElementAndContextAndFlagToInvokeRaisesExceptionForMissingLabelAttribute()
    {
        $element = new Element('foo');
        $this->expectException('Laminas\Form\Exception\DomainException');
        $this->expectExceptionMessage('label');
        $markup = $this->helper->__invoke($element, '<input type="text" id="foo" />', FormLabelHelper::APPEND);
    }

    public function testCallingFromViewHelperCanHandleOpenTagAndCloseTag()
    {
        $helper = $this->helper;
        $markup = $helper()->openTag();
        $this->assertEquals('<label>', $markup);
        $markup = $helper()->closeTag();
        $this->assertEquals('</label>', $markup);
    }

    public function testCanTranslateContent()
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');

        $mockTranslator = $this->createMock('Laminas\I18n\Translator\Translator');
        $mockTranslator->expects($this->once())
                       ->method('translate')
                       ->willReturn('translated content');

        $this->helper->setTranslator($mockTranslator);
        $this->assertTrue($this->helper->hasTranslator());

        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('>translated content<', $markup);
    }

    public function testTranslatorMethods()
    {
        $translatorMock = $this->createMock('Laminas\I18n\Translator\Translator');
        $this->helper->setTranslator($translatorMock, 'foo');

        $this->assertEquals($translatorMock, $this->helper->getTranslator());
        $this->assertEquals('foo', $this->helper->getTranslatorTextDomain());
        $this->assertTrue($this->helper->hasTranslator());
        $this->assertTrue($this->helper->isTranslatorEnabled());

        $this->helper->setTranslatorEnabled(false);
        $this->assertFalse($this->helper->isTranslatorEnabled());
    }

    public function testLabelIsEscapedByDefault()
    {
        $element = new Element('foo');
        $element->setLabel('The value <a>for</a> foo:');
        $markup = $this->helper->__invoke($element);
        $this->assertStringNotContainsString('<a>for</a>', $markup);
    }

    public function testCanDisableLabelHtmlEscape()
    {
        $element = new Element('foo');
        $element->setLabel('The value <a>for</a> foo:');
        $element->setLabelOptions(['disable_html_escape' => true]);
        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('<a>for</a>', $markup);
    }

    public function testAlwaysWrapIsDisabledByDefault()
    {
        $element = new Element('foo');
        $this->assertEmpty($element->getLabelOption('always_wrap'));
    }

    public function testCanSetAlwaysWrap()
    {
        $element = new Element('foo');
        $element->setLabelOption('always_wrap', true);
        $this->assertTrue($element->getLabelOption('always_wrap'));
    }
}
