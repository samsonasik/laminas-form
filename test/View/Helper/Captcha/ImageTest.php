<?php

/**
 * @see       https://github.com/laminas/laminas-form for the canonical source repository
 * @copyright https://github.com/laminas/laminas-form/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-form/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Form\View\Helper\Captcha;

use DirectoryIterator;
use Laminas\Captcha\Image as ImageCaptcha;
use Laminas\Form\Element\Captcha as CaptchaElement;
use Laminas\Form\View\Helper\Captcha\Image as ImageCaptchaHelper;
use LaminasTest\Form\View\Helper\CommonTestCase;

use function class_exists;
use function extension_loaded;
use function function_exists;
use function is_dir;
use function mkdir;
use function sys_get_temp_dir;
use function unlink;

class ImageTest extends CommonTestCase
{
    protected $tmpDir;
    protected $testDir;

    protected function setUp(): void
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

        if (! class_exists(ImageCaptcha::class)) {
            $this->markTestSkipped(
                'laminas-captcha-related tests are skipped until the component '
                . 'is forwards-compatible with laminas-servicemanager v3'
            );
        }

        $this->testDir = $this->getTmpDir() . '/Laminas_test_images';
        if (! is_dir($this->testDir)) {
            @mkdir($this->testDir);
        }

        $this->helper  = new ImageCaptchaHelper();
        $this->captcha = new ImageCaptcha([
            'imgDir'       => $this->testDir,
            'font'         => __DIR__. '/_files/Vera.ttf',
        ]);
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
        if (! isset($this->testDir)) {
            parent::tearDown();
            return;
        }

        foreach (new DirectoryIterator($this->testDir) as $file) {
            if (! $file->isDot() && ! $file->isDir()) {
                unlink($file->getPathname());
            }
        }
        parent::tearDown();
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
        $element->setCaptcha($this->captcha);
        return $element;
    }

    public function testMissingCaptchaAttributeThrowsDomainException()
    {
        $element = new CaptchaElement('foo');

        $this->expectException('Laminas\Form\Exception\DomainException');
        $this->helper->render($element);
    }

    public function testRendersHiddenInputForId()
    {
        $element = $this->getElement();
        $markup  = $this->helper->render($element);
        $this->assertMatchesRegularExpression('#(name="' . $element->getName() . '\&\#x5B\;id\&\#x5D\;").*?(type="hidden")#', $markup);
        $this->assertMatchesRegularExpression(
            '#(name="' . $element->getName() . '\&\#x5B\;id\&\#x5D\;").*?(value="' . $this->captcha->getId() . '")#',
            $markup
        );
    }

    public function testRendersTextInputForInput()
    {
        $element = $this->getElement();
        $markup  = $this->helper->render($element);
        $this->assertMatchesRegularExpression('#(name="' . $element->getName() . '\&\#x5B\;input\&\#x5D\;").*?(type="text")#', $markup);
    }

    public function testRendersImageTagPriorToInputByDefault()
    {
        $element = $this->getElement();
        $markup  = $this->helper->render($element);
        $this->assertMatchesRegularExpression('#<img[^>]+><input#', $markup);
    }

    public function testCanRenderImageTagFollowingInput()
    {
        $this->helper->setCaptchaPosition('prepend');
        $element = $this->getElement();
        $markup  = $this->helper->render($element);
        $this->assertMatchesRegularExpression('#<input[^>]+><img#', $markup);
    }
}
