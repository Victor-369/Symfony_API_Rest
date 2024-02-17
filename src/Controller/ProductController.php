<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Entity\Product;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController {
    private $em;
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('api/products', methods: ['GET'])]
    public function product_list(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');

        // Decodificar el token
        $decode = JWT::decode($apiToken, new Key($_ENV['JWT_SECRET'], 'HS512'));
        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $decode->aud]);

        if(!$user){
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                                ], Response::HTTP_BAD_REQUEST);
        }

        $datos = $this->em->getRepository(Product::class)->findAll();
        $serializedData = $serializer->normalize($datos);

        return $this->json(['estado' => 'Ok',
                            'mensaje' => $serializedData
                            ], 200);
    }
}
