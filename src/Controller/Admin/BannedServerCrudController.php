<?php

namespace App\Controller\Admin;

use App\Entity\BannedServer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BannedServerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BannedServer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Banned Server')
            ->setEntityLabelInPlural('Banned Servers')
            ->setSearchFields(['discordID', 'reason'])
            ->setDefaultSort(['dateCreated' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('discordID')->setLabel('Discord ID');
        yield TextareaField::new('reason');
        yield DateTimeField::new('dateCreated')->hideOnForm();
    }
}
