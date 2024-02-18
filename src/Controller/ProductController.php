<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Utilidades\Utilidades;

class ProductController extends AbstractController {
    private $em;
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('api/products', methods: ['GET'])]
    public function product_getList(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            $datos = $this->em->getRepository(Product::class)->findAll();
            $serializedData = $serializer->normalize($datos);
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => $serializedData
                                ], 200);
        }
    }

    #[Route('api/products/{id}', methods: ['GET'])]
    public function product_getById(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {            
            $datos = $this->em->getRepository(Product::class)->find($id);
            $serializedData = $serializer->normalize($datos);

            return $this->json(['estado' => 'Ok',
                                'mensaje' => $serializedData
                                ], 200);
        }
    }
}
