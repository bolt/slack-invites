<?php

namespace Bolt\Site\SlackInvites\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Invite form type.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class InviteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'email',
                Type\EmailType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Email([
                            'checkMX'   => true,
                            'checkHost' => true,
                        ])
                    ],
                    'attr' => [
                        'placeholder' => 'email.placeholder'
                    ],
                ]
            )
            ->add('submit', Type\ButtonType::class, ['label' => 'button.invite'])
        ;
    }
}
