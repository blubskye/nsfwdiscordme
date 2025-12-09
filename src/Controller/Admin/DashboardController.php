<?php

namespace App\Controller\Admin;

use App\Entity\AdminEvent;
use App\Entity\BannedServer;
use App\Entity\BannedUser;
use App\Entity\BannedWord;
use App\Entity\Category;
use App\Entity\Media;
use App\Entity\Server;
use App\Entity\ServerTeamMember;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('nsfwdiscord.me')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('Stats', 'fa fa-chart-bar', 'admin_stats');
        yield MenuItem::linkToRoute('Bump Points', 'fa fa-arrow-alt-circle-up', 'admin_bumps');

        yield MenuItem::section('Content');
        yield MenuItem::linkToCrud('Servers', 'fa fa-server', Server::class);
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
        yield MenuItem::linkToCrud('Team Members', 'fa fa-users', ServerTeamMember::class);
        yield MenuItem::linkToCrud('Media', 'fa fa-image', Media::class);
        yield MenuItem::linkToCrud('Categories', 'fa fa-tags', Category::class);

        yield MenuItem::section('Moderation');
        yield MenuItem::linkToCrud('Banned Servers', 'fa fa-ban', BannedServer::class);
        yield MenuItem::linkToCrud('Banned Users', 'fa fa-ban', BannedUser::class);
        yield MenuItem::linkToCrud('Banned Words', 'fa fa-ban', BannedWord::class);

        yield MenuItem::section('Logs');
        yield MenuItem::linkToCrud('Admin Events', 'fa fa-calendar-alt', AdminEvent::class);
    }
}
