<?php

namespace App\Controller\Admin;

use App\Entity\BannedUser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BannedUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BannedUser::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Banned User')
            ->setEntityLabelInPlural('Banned Users')
            ->setSearchFields(['discordID', 'discordUsername', 'reason'])
            ->setDefaultSort(['dateCreated' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('discordID')->setLabel('Discord ID');
        yield TextField::new('discordUsername')->setLabel('Username');
        yield TextField::new('discordDiscriminator')->setLabel('Discriminator');
        yield TextareaField::new('reason');
        yield DateTimeField::new('dateCreated')->hideOnForm();
    }
}
