<?php

namespace App\Controller\Admin;

use App\Entity\Server;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Server::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Server')
            ->setEntityLabelInPlural('Servers')
            ->setSearchFields(['name', 'slug', 'discordID'])
            ->setDefaultSort(['dateCreated' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isEnabled')
            ->add('isPublic')
            ->add('premiumStatus')
            ->add('categories');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield BooleanField::new('isEnabled');
        yield TextField::new('name');
        yield TextField::new('slug');
        yield TextField::new('discordID')->setLabel('Discord ID');
        yield AssociationField::new('user');
        yield IntegerField::new('bumpPoints');
        yield ChoiceField::new('premiumStatus')
            ->setChoices([
                'Standard' => Server::STATUS_STANDARD,
                'Ruby' => Server::STATUS_RUBY,
                'Topaz' => Server::STATUS_TOPAZ,
                'Emerald' => Server::STATUS_EMERALD,
            ]);
        yield TextField::new('summary')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();
        yield AssociationField::new('categories');
        yield BooleanField::new('isPublic');
        yield BooleanField::new('isActive');
        yield BooleanField::new('botHumanCheck')->hideOnIndex();
        yield DateTimeField::new('dateCreated')->hideOnForm();
        yield DateTimeField::new('dateBumped')->hideOnForm();
    }
}
