<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;


class UserController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/user/registration', methods: ['POST'])]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(!isset($data['nombre'])) {
            return $this->json (['estado' => 'error',
                                'mensaje'=>'El campo nombre es obligatorio'
                                ], 400);
        }

        if(!isset($data['email'])) {
            return $this->json (['estado' => 'error',
                                'mensaje'=>'El campo email es obligatorio'
                                ], 400);
        }

        if(!isset($data['clave'])) {
            return $this->json (['estado' => 'error',
                                'mensaje'=>'El campo clave es obligatorio'
                                ], 400);
        }

        $existe = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        
        if($existe) {
            return $this->json(['estado' => 'error',
                                'mensaje' => "El email {$data['email']} ya está siendo usado por otro usuario"], 400);
        }

        $entity = new User();
        $entity->setNombre($data['nombre']);
        $entity->setEmail($data['email']);
        $entity->setPassword($passwordHasher->hashPassword($entity, $data['clave']));
        $entity->setRoles(['ROLE_USER']);
        
        $this->em->persist($entity);
        $this->em->flush();
        
        return $this->json(['estado' => 'ok',
                            'mensaje' => 'Se creó el registro exitosamente'
                            ], Response::HTTP_CREATED); // 201
    }
}
