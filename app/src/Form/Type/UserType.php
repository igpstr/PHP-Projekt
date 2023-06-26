<?php
/**
 * User type.
 */

namespace App\Form\Type;

use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\User;
//use App\Form\DataTransformer\UserDataTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class UserType.
 */
class UserType extends AbstractType
{
//    /**
//     * User data transformer.
//     *
//     * @var UserDataTransformer
//     */
//    private UserDataTransformer $tagsDataTransformer;
//
//    /**
//     * Constructor.
//     *
//     * @param UserDataTransformer $tagsDataTransformer User data transformer
//     */
//    public function __construct(UserDataTransformer $tagsDataTransformer)
//    {
//        $this->tagsDataTransformer = $tagsDataTransformer;
//    }
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Builds the form.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array<string, mixed> $options Form options
     *
     * @see FormTypeExtensionInterface::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'nick',
            TextType::class,
            [
                'label' => 'label.nick',
                'required' => true,
                'attr' => ['max_length' => 255],
            ]);
        $builder->add(
            'email',
            TextType::class,
            [
                'label' => 'label.email',
                'required' => true,
                'attr' => ['max_length' => 255],
            ]);
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
        $builder->add(
            'roles',
            ChoiceType::class,
            [
                'label' => 'Roles',
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'required' => true,
            ]
        );
        }
        else {
            // Set the role to ROLE_USER for non-admin users
            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $user = $event->getData();
                $user->setRoles([UserRole::ROLE_USER->value]);
            });
        }
        $builder->add(
            'password',
            TextType::class,
            [
                'label' => 'label.password',
                'required' => true,
                'attr' => ['max_length' => 128],
            ]
        );

//        $builder->get('tags')->addModelTransformer(
//            $this->tagsDataTransformer
//        );
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }

    /**
     * Returns the prefix of the template block name for this type.
     *
     * The block prefix defaults to the underscored short class name with
     * the "Type" suffix removed (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string
    {
        return 'user';
    }
}
