<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusCawlPlugin\Unit\Form\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Waaz\SyliusCawlPlugin\Form\Type\CawlGatewayConfigurationType;

final class CawlGatewayConfigurationTypeTest extends TestCase
{
    private CawlGatewayConfigurationType $formType;

    protected function setUp(): void
    {
        $this->formType = new CawlGatewayConfigurationType();
    }

    public function testItBuildsFormWithCorrectFields(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->expects($this->exactly(6))
            ->method('add')
            ->willReturnSelf();

        $this->formType->buildForm($builder, []);
    }

    public function testItHasCorrectBlockPrefix(): void
    {
        $this->assertSame('waaz_sylius_cawl_plugin_gateway_configuration', $this->formType->getBlockPrefix());
    }
}
