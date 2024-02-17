<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
// Para generar el token
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AccessController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('api/user/registration', methods: ['POST'])]
    public function registration(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(!isset($data['nombre'])) {
            return $this->json (['estado' => 'error',
                                'mensaje' => 'El campo nombre es obligatorio'
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

    #[Route('/api/user/login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(!isset($data['email'])) {
            return $this->json (['estado' => 'error',
                                'mensaje' => 'El campo correo es obligatorio'
                                ], 400);
        }

        if(!isset($data['clave'])) {
            return $this->json (['estado' => 'error',
                                'mensaje' => 'El campo clave es obligatorio'
                                ], 400);
        }
        
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if(!$user) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                                ], Response::HTTP_BAD_REQUEST);
        }

        if($passwordHasher->isPasswordValid($user, $data['clave'])) {
            $payload = [
                        // URL de la aplicación
                        'iss' => "http://" . dirname ($_SERVER['SERVER_NAME'] . "" . $_SERVER['PHP_SELF']) . "/",
                        // Identificador único del usuario
                        'aud' => $user->getId(),
                        // fecha cuando fue creado en formato timestamp
                        'iat' => time(),
                        // fecha de expiración
                        'exp' => strtotime('+1 minute', time())
                        ];

            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS512');

            return $this->json(['nombre' => $user->getNombre(),
                                'token' => $jwt
                                ]);
        } else {
            return $this->json (['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                                ], Response::HTTP_BAD_REQUEST); //400
        }
    }
}
