<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CawlGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('api_key', TextType::class, [
                'label' => 'waaz_sylius_cawl_plugin.form.gateway_configuration.api_key',
                'constraints' => [
                    new NotBlank([
                        'message' => 'waaz_sylius_cawl_plugin.form.gateway_configuration.api_key.not_blank',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('api_secret', TextType::class, [
                'label' => 'waaz_sylius_cawl_plugin.form.gateway_configuration.api_secret',
                'constraints' => [
                    new NotBlank([
                        'message' => 'waaz_sylius_cawl_plugin.form.gateway_configuration.api_secret.not_blank',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('merchant_id', TextType::class, [
                'label' => 'waaz_sylius_cawl_plugin.form.gateway_configuration.merchant_id',
                'constraints' => [
                    new NotBlank([
                        'message' => 'waaz_sylius_cawl_plugin.form.gateway_configuration.merchant_id.not_blank',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('sandbox', CheckboxType::class, [
                'label' => 'waaz_sylius_cawl_plugin.form.gateway_configuration.sandbox',
                'data' => true,
                'required' => false,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'waaz_sylius_cawl_plugin_gateway_configuration';
    }
}
