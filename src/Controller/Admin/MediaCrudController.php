<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MediaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Media::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Media')
            ->setEntityLabelInPlural('Media')
            ->setSearchFields(['name', 'path'])
            ->setDefaultSort(['dateCreated' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('path');
        yield TextField::new('name');
        yield TextField::new('adapter');
        yield DateTimeField::new('dateCreated')->hideOnForm();
    }
}
