<?php

namespace App\Controller\Admin;

use App\Entity\AdminEvent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdminEventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminEvent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Admin Event')
            ->setEntityLabelInPlural('Admin Events')
            ->setSearchFields(['eventType', 'message'])
            ->setDefaultSort(['dateCreated' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('eventType')->setLabel('Event Type');
        yield AssociationField::new('user');
        yield TextField::new('message');
        yield DateTimeField::new('dateCreated');
    }
}
