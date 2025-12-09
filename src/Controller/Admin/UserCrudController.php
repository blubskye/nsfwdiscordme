<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setSearchFields(['discordUsername', 'discordEmail', 'discordID'])
            ->setDefaultSort(['dateCreated' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isEnabled');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield BooleanField::new('isEnabled');
        yield TextField::new('discordID')->setLabel('Discord ID');
        yield TextField::new('discordUsername')->setLabel('Username');
        yield TextField::new('discordDiscriminator')->setLabel('Discriminator');
        yield TextField::new('discordEmail')->setLabel('Email');
        yield TextField::new('discordAvatar')->setLabel('Avatar')->hideOnIndex();
        yield DateTimeField::new('dateCreated')->hideOnForm();
        yield DateTimeField::new('dateLastLogin')->setLabel('Last Login')->hideOnForm();
    }
}
