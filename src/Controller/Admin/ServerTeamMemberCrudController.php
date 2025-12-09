<?php

namespace App\Controller\Admin;

use App\Entity\ServerTeamMember;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServerTeamMemberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServerTeamMember::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Team Member')
            ->setEntityLabelInPlural('Team Members')
            ->setSearchFields(['discordUsername', 'discordID']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('discordID')->setLabel('Discord ID');
        yield TextField::new('discordUsername')->setLabel('Username');
        yield TextField::new('discordDiscriminator')->setLabel('Discriminator');
        yield TextField::new('role');
        yield AssociationField::new('server');
        yield AssociationField::new('user');
    }
}
