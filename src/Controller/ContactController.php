<?php
namespace App\Controller;

use App\Form\Model\ContactModel;
use App\Form\Type\ContactType;
use Symfony\Component\HttpFoundation\Request;
use App\Services\RecaptchaService;
use GuzzleHttp\Exception\GuzzleException;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'contact_')]
class ContactController extends Controller
{
    const TO_EMAIL = 'sean@headzoo.io';

    /**
     * @param Request          $request
     * @param RecaptchaService $recaptchaService
     * @param Swift_Mailer     $mailer
     *
     * @return Response
     * @throws GuzzleException
     */
    #[Route('/contact', name: 'index')]
    public function indexAction(Request $request, RecaptchaService $recaptchaService, Swift_Mailer $mailer): Response
    {
        $model = new ContactModel();
        $form  = $this->createForm(ContactType::class, $model);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $recaptchaToken = $request->request->get('g-recaptcha-response');
            if ($recaptchaService->verify($recaptchaToken)) {
                $message = (new Swift_Message())
                    ->setFrom($model->getEmail())
                    ->setTo(self::TO_EMAIL)
                    ->setSubject('[nsfwdiscordme contact] ' . $model->getSubject())
                    ->setBody($model->getMessage());
                $mailer->send($message);

                $this->addFlash('success', 'Thank you! Your message has been sent.');

                return new RedirectResponse($this->generateUrl('contact_index'));
            }
        }

        return $this->render(
            'contact/index.html.twig',
            [
                'form'  => $form->createView(),
                'title' => 'Contact Us'
            ]
        );
    }
}
