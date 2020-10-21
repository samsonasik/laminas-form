<?php

/**
 * @see       https://github.com/laminas/laminas-form for the canonical source repository
 * @copyright https://github.com/laminas/laminas-form/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-form/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Form\View\Helper;

use DirectoryIterator;
use Laminas\Captcha;
use Laminas\Form\Element\Captcha as CaptchaElement;
use Laminas\Form\View\Helper\FormCaptcha as FormCaptchaHelper;

use function class_exists;
use function extension_loaded;
use function function_exists;
use function getenv;
use function is_dir;
use function mkdir;
use function str_replace;
use function sys_get_temp_dir;
use function unlink;

class FormCaptchaTest extends CommonTestCase
{
    protected $testDir    = null;
    protected $tmpDir     = null;

    protected function setUp(): void
    {
        if (! class_exists(Captcha\Dumb::class)) {
            $this->markTestSkipped(
                'laminas-captcha-related tests are skipped until the component '
                . 'is forwards-compatible with laminas-servicemanager v3'
            );
        }

        $this->helper = new FormCaptchaHelper();
        parent::setUp();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // remove captcha images
        if (null !== $this->testDir) {
            foreach (new DirectoryIterator($this->testDir) as $file) {
                if (! $file->isDot() && ! $file->isDir()) {
                    unlink($file->getPathname());
                }
            }
        }
    }

    /**
     * Determine system TMP directory
     *
     * @return string
     * @throws Laminas_File_Transfer_Exception if unable to determine directory
     */
    protected function getTmpDir()
    {
        if (null === $this->tmpDir) {
            $this->tmpDir = sys_get_temp_dir();
        }

        return $this->tmpDir;
    }

    public function getElement()
    {
        $element = new CaptchaElement('foo');

        return $element;
    }

    public function testRaisesExceptionIfElementHasNoCaptcha()
    {
        $element = $this->getElement();
        $this->expectException('Laminas\Form\Exception\ExceptionInterface');
        $this->expectExceptionMessage('captcha');
        $this->helper->render($element);
    }

    public function testPassingElementWithDumbCaptchaRendersCorrectly()
    {
        $captcha = new Captcha\Dumb();
        $element = $this->getElement();
        $element->setCaptcha($captcha);
        $element->setAttribute('id', 'foo');
        $markup = $this->helper->render($element);
        $this->assertStringContainsString($captcha->getLabel(), $markup);
        $this->assertMatchesRegularExpression(
            '#<[^>]*(id="' . $element->getAttribute('id') . '")[^>]*(type="text")[^>]*>#',
            $markup
        );
        $this->assertMatchesRegularExpression(
            '#<[^>]*(id="' . $element->getAttribute('id') . '-hidden")[^>]*(type="hidden")[^>]*>#',
            $markup
        );
    }

    public function testPassingElementWithFigletCaptchaRendersCorrectly()
    {
        $captcha = new Captcha\Figlet();
        $element = $this->getElement();
        $element->setCaptcha($captcha);
        $element->setAttribute('id', 'foo');
        $markup = $this->helper->render($element);
        $this->assertStringContainsString(
            '<pre>' . $captcha->getFiglet()->render($captcha->getWord()) . '</pre>',
            $markup
        );
        $this->assertMatchesRegularExpression(
            '#<[^>]*(id="' . $element->getAttribute('id') . '")[^>]*(type="text")[^>]*>#',
            $markup
        );
        $this->assertMatchesRegularExpression(
            '#<[^>]*(id="' . $element->getAttribute('id') . '-hidden")[^>]*(type="hidden")[^>]*>#',
            $markup
        );
    }

    public function testPassingElementWithImageCaptchaRendersCorrectly()
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is not available.');

            return;
        }
        if (! function_exists('imagepng')) {
            $this->markTestSkipped('Image CAPTCHA requires PNG support');
        }
        if (! function_exists('imageftbbox')) {
            $this->markTestSkipped('Image CAPTCHA requires FT fonts support');
        }

        $this->testDir = $this->getTmpDir() . '/Laminas_test_images';
        if (! is_dir($this->testDir)) {
            @mkdir($this->testDir);
        }

        $captcha = new Captcha\Image([
            'imgDir'       => $this->testDir,
            'font'         => __DIR__. '/Captcha/_files/Vera.ttf',
        ]);
        $element = $this->getElement();
        $element->setCaptcha($captcha);
        $element->setAttribute('id', 'foo');

        $markup = $this->helper->render($element);

        $this->assertStringContainsString('<img ', $markup);
        $this->assertStringContainsString(str_replace('/', '&#x2F;', $captcha->getImgUrl()), $markup);
        $this->assertStringContainsString($captcha->getId(), $markup);
        $this->assertMatchesRegularExpression(
            '#<img[^>]*(id="' . $element->getAttribute('id') . '-image")[^>]*>#',
            $markup
        );
        $this->assertMatchesRegularExpression(
            '#<input[^>]*(id="' . $element->getAttribute('id') . '")[^>]*(type="text")[^>]*>#',
            $markup
        );
        $this->assertMatchesRegularExpression(
            '#<input[^>]*(id="' . $element->getAttribute('id') . '-hidden")[^>]*(type="hidden")[^>]*>#',
            $markup
        );
    }

    public function testPassingElementWithReCaptchaRendersCorrectly()
    {
        if (! getenv('TESTS_LAMINAS_FORM_RECAPTCHA_SUPPORT')) {
            $this->markTestSkipped('Enable TESTS_LAMINAS_FORM_RECAPTCHA_SUPPORT to test PDF render');
        }

        $captcha = new Captcha\ReCaptcha();
        $captcha->setPubKey(getenv('TESTS_LAMINAS_FORM_RECAPTCHA_PUBLIC_KEY'));
        $captcha->setPrivKey(getenv('TESTS_LAMINAS_FORM_RECAPTCHA_PRIVATE_KEY'));

        $element = $this->getElement();
        $element->setCaptcha($captcha);
        $markup = $this->helper->render($element);
        $this->assertStringContainsString(
            'data-sitekey="' . getenv('TESTS_LAMINAS_FORM_RECAPTCHA_PUBLIC_KEY') . '"',
            $markup
        );
    }
}
