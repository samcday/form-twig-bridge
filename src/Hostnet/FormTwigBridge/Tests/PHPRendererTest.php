<?php
namespace Hostnet\FormTwigBridge\Tests;
use Symfony\Component\Validator\Constraints\NotBlank;

use Hostnet\FormTwigBridge\PHPRenderer;

use Hostnet\FormTwigBridge\TwigEnvironmentBuilder;

use Hostnet\FormTwigBridge\Builder;

class PHPRendererTest extends \PHPUnit_Framework_TestCase
{
  private $csrf;

  public function setUp()
  {
    $this->csrf =
      $this
          ->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface',
            array('generateCsrfToken', 'isCsrfTokenValid'));

    $this->csrf->expects($this->any())->method('generateCsrfToken')
         ->will($this->returnValue('foo'));
  }

  public function test__construct()
  {
    try {
      new PHPRenderer(new \Twig_Environment());
      $this->fail('Should require the form extension');
    } catch(\DomainException $e) {
    }
  }

  public function testRenderEnctype()
  {
    // Not a special form - empty result
    $environment = $this->mockEnvironment();
    $form_view = $this->mockForm()->createView();
    $renderer = new PHPRenderer($environment);
    $this->assertEquals('', $renderer->renderEnctype($form_view));

    // Lets test a file upload
    $builder = new Builder();
    $factory = $builder->setCsrfProvider($this->csrf)->buildFormFactory();
    $form = $factory->createBuilder()->add('picture', 'file')->getForm();
    $renderer = new PHPRenderer($environment);
    $html = 'enctype="multipart/form-data"';
    $this->assertEquals($html, $renderer->renderEnctype($form->createView()));
  }

  public function testRenderWidget()
  {
    $environment = $this->mockEnvironment();
    $form_view = $this->mockForm()->createView();
    $renderer = new PHPRenderer($environment);
    $html =
      '<div id="form"><div><label for="form_naam" class="required">Naam</label><input type="text" id="form_naam" name="form[naam]" required="required" /></div><input type="hidden" id="form__token" name="form[_token]" value="foo" /></div>';
    $this->assertEquals($html, $renderer->renderWidget($form_view));
  }

  public function testRenderErrors()
  {
    // Unbound form - empty result
    $environment = $this->mockEnvironment();
    $form = $this->mockForm();
    $renderer = new PHPRenderer($environment);
    $this->assertEquals('', $renderer->renderErrors($form->createView()));

    // Lets bind it, give some errors
    $form->bind(array());
    $renderer = new PHPRenderer($environment);
    $html = '<ul><li>The CSRF token is invalid. Please try to resubmit the form.</li></ul>';
    $this->assertEquals($html, $renderer->renderErrors($form->createView()));
  }

  public function testRenderLabel()
  {
    $environment = $this->mockEnvironment();
    $form_view = $this->mockForm()->createView();
    $field = $form_view->children['naam'];
    $renderer = new PHPRenderer($environment);
    $html = '<label for="form_naam" class="required">Naam</label>';
    $this->assertEquals($html, $renderer->renderLabel($field));
  }

  public function testRenderRowAndRest()
  {
    $environment = $this->mockEnvironment();
    $form = $this->mockForm()->createView();
    $renderer = new PHPRenderer($environment);
    $html =
      '<div><label for="form_naam" class="required">Naam</label><input type="text" id="form_naam" name="form[naam]" required="required" /></div>';
    $this->assertEquals($html, $renderer->renderRow($form->children['naam']));

    // good opportunity to test renderRest as well. Renders all the other fields
    $html = '<input type="hidden" id="form__token" name="form[_token]" value="foo" />';
    $this->assertEquals($html, $renderer->renderRest($form));
  }

  private function mockEnvironment()
  {
    $builder = new TwigEnvironmentBuilder();
    $builder->setCsrfProvider($this->csrf);
    return $builder->build();
  }

  private function mockForm()
  {
    $builder = new Builder();
    $factory = $builder->setCsrfProvider($this->csrf)->buildFormFactory();
    $options = array('constraints' => array(new NotBlank()));
    return $factory->createBuilder()->add('naam', 'text', $options)->getForm();
  }
}
