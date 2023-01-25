<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DataFormat;
use Doctrine\Persistence\ManagerRegistry;
use Lcobucci\JWT\Token;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AccountController extends AbstractController
{
    private $baseUrl = 'http://localhost:4200';

    #[Route('/create/account', name: 'create_account')]
    public function createAccount(ManagerRegistry $doctrine, Request $request, DataFormat $df, UserPasswordHasherInterface $userPasswordHasher, SerializerInterface $serializer, MailerInterface $mailer): Response
    {
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $userEmail = $doctrine->getRepository(User::class)->findOneBy(['email' => $request->get('email')]);

            if (!$userEmail) {
                $user = new User();
                $user->setUsername($request->get('username'));
                $user->setEmail($request->get('email'));
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,    
                        $request->get('password')
                    )
                );
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());
                $doctrine->getManager()->persist($user);
                $doctrine->getManager()->flush();

                // $url = $this->baseUrl.'/create/account/'.$token->getToken();

                // $email = (new TemplatedEmail())
                //     ->from(new Address('model@model.com.br', 'Model'))
                //     ->to($user->getEmail())
                //     ->subject('Please Confirm your Email')
                //     ->htmlTemplate('registration/confirmation_email.html.twig')
                //     ->context(['url' => $url])
                // ;

                // $mailer->send($email);
    
                $con->commit();

                $serialized = $serializer->serialize([
                    'message'   => 'Usuário criado com sucesso.',
                    'status'    => true
                ],'json');
                return JsonResponse::fromJsonString($serialized);
            } else {
                $con->rollback();
                $serialized = $serializer->serialize([
                    'message'   => 'Usuário já está em uso.',
                    'status'    => false
                ],'json');
                return JsonResponse::fromJsonString($serialized);
            }
        } catch (\Exception $e) {
            $con->rollback();
            $serialized = $serializer->serialize([
                'message'   => 'Erro no sistema.',
                'status'    => false
            ],'json');
            return JsonResponse::fromJsonString($serialized);
        }
    }

    #[Route('/get/valid/user', name: 'get_valid_user')]
    public function getValidUser(ManagerRegistry $doctrine, Request $request, DataFormat $df, SerializerInterface $serializer): Response
    {
        $request = $df->transformJsonBody($request);
        
        try {
            $user = $doctrine->getRepository(User::class)->findOneBy(['username' => $request->get('username')]);

            if($user && $user->isValid()){
                $serialized = $serializer->serialize([
                    'data'      => $user,
                    'status'    => true
                ],'json');
                return JsonResponse::fromJsonString($serialized);
            } else {
                $serialized = $serializer->serialize([
                    'message' => 'Usuário inválido.',
                    'status' => false
                ],'json');
                return JsonResponse::fromJsonString($serialized);
            }
        } catch (\Exception $e) {
            $serialized = $serializer->serialize([
                'message' => 'Erro no sistema.',
                'status' => false
            ],'json');
            return JsonResponse::fromJsonString($serialized);
        }
    }

    #[Route('/api/get/user/info', name: 'get_user_info')]
    public function getUserInfo(SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $serialized = $serializer->serialize([
                'data' => $user,
                'status' => true
            ],'json');
            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            $serialized = $serializer->serialize([
                'message' => 'Erro no sistema.',
                'status' => false
            ],'json');
            return JsonResponse::fromJsonString($serialized);
        }
    }

    #[Route('/api/get/active/accounts', name: 'get_active_accounts')]
    public function getActiveAccounts(ManagerRegistry $doctrine, Request $request, DataFormat $df, SerializerInterface $serializer): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $request = $df->transformJsonBody($request);

        try {
            $accounts = $doctrine->getRepository(User::class)->findBy(['username' => $request->get('accounts')]);
            $serialized = $serializer->serialize([
                'data' => $accounts,
                'status' => true
            ],'json');
            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            $serialized = $serializer->serialize([
                'message' => 'Erro no sistema.',
                'status' => false
            ],'json');
            return JsonResponse::fromJsonString($serialized);
        }
    }

    #[Route('/api/get/selected/account', name: 'get_selected_account')]
    public function getSelectedAccount(ManagerRegistry $doctrine, Request $request, DataFormat $df, SerializerInterface $serializer): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $request = $df->transformJsonBody($request);

        try {
            $accounts = $doctrine->getRepository(User::class)->find($request->get('id'));
            $serialized = $serializer->serialize([
                'data' => $accounts,
                'status' => true
            ],'json');
            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            $serialized = $serializer->serialize([
                'message' => 'Erro no sistema.',
                'status' => false
            ],'json');
            return JsonResponse::fromJsonString($serialized);
        }
    }

}
