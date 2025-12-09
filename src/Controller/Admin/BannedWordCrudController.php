<?php

namespace App\Controller\Admin;

use App\Entity\BannedWord;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BannedWordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BannedWord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Banned Word')
            ->setEntityLabelInPlural('Banned Words')
            ->setSearchFields(['word']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('word');
        yield DateTimeField::new('dateCreated')->hideOnForm();
    }
}
