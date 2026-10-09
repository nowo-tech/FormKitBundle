<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\StatelessCsrfTokenIdExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class StatelessCsrfTokenIdExtensionTest extends TestCase
{
    public function testSetsCsrfTokenIdDefault(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('csrf_token_id', 'form_name');

        $extension = new StatelessCsrfTokenIdExtension('submit');
        $extension->configureOptions($resolver);

        self::assertSame('submit', $resolver->resolve()['csrf_token_id']);
        self::assertSame('custom', $resolver->resolve(['csrf_token_id' => 'custom'])['csrf_token_id']);
        self::assertSame('submit', $extension->getTokenId());
    }

    public function testExtendedTypesComeFromTheServiceTag(): void
    {
        self::assertSame([], [...StatelessCsrfTokenIdExtension::getExtendedTypes()]);
    }

    public function testOnlyTargetedTypeGetsStatelessTokenId(): void
    {
        if (!interface_exists(CsrfTokenManagerInterface::class)) {
            self::markTestSkipped('symfony/security-csrf not installed.');
        }

        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new CsrfExtension($this->createMock(CsrfTokenManagerInterface::class)))
            ->addExtension(new PreloadedExtension([], [PublicNewsletterTestType::class => [new StatelessCsrfTokenIdExtension('submit')]]))
            ->getFormFactory();

        self::assertSame('submit', $factory->create(PublicNewsletterTestType::class)->getConfig()->getOption('csrf_token_id'));
        self::assertNull($factory->create(FormType::class)->getConfig()->getOption('csrf_token_id'));
        self::assertSame('explicit', $factory->create(PublicNewsletterTestType::class, null, ['csrf_token_id' => 'explicit'])->getConfig()->getOption('csrf_token_id'));
    }
}

/**
 * @extends AbstractType<mixed>
 */
final class PublicNewsletterTestType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }
}
