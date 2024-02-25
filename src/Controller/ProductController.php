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

    #[Route('api/products', methods: ['POST'])]
    public function product_create(Request $request): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            $data = json_decode($request->getContent(), true);

            if(!$data['nombre']) {
                return $this->json (['estado' => 'error',
                                    'mensaje' => 'El campo nombre es obligatorio'
                                    ], 400);
            }

            $existe = $this->em->getRepository(Product::class)->findOneBy(['nombre' => $data['nombre']]);
            
            if($existe) {
                return $this->json(['estado' => 'error',
                                    'mensaje' => "El producto '{$data['nombre']}' ya existe"], 400);
            }

            $entity = new Product();
            $entity->setNombre($data['nombre']);

            $fechaHora = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', time()));
            $entity->setCreatedAt($fechaHora);
            
            $this->em->persist($entity);
            $this->em->flush();
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => 'Producto creado'
                                ], 200);
        }
    }

    #[Route('api/products/{id}', methods: ['PUT'])]
    public function product_modify(Request $request, int $id): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            $data = json_decode($request->getContent(), true);

            if(!$data['nombre']) {
                return $this->json (['estado' => 'error',
                                    'mensaje' => 'El campo nombre es obligatorio'
                                    ], 400);
            }

            $producto = $this->em->getRepository(Product::class)->findOneBy(['id' => $id]);
            
            if(!$producto) {
                return $this->json(['estado' => 'error',
                                    'mensaje' => "El producto con id '$id' no existe"], 400);
            }

            $producto->setNombre($data['nombre']);
            
            $fechaHora = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', time()));
            $producto->setModifiedAt($fechaHora);
            
            $this->em->persist($producto);
            $this->em->flush();
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => 'Producto modificado'
                                ], 200);
        }
    }

    #[Route('api/products/{id}', methods: ['DELETE'])]
    public function product_delete(Request $request, int $id): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            $producto = $this->em->getRepository(Product::class)->findOneBy(['id' => $id]);
            
            if(!$producto) {
                return $this->json(['estado' => 'error',
                                    'mensaje' => "El producto con id '$id' no existe"], 400);
            }
            
            $fechaHora = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', time()));
            $producto->setDeletedAt($fechaHora);
            
            $this->em->persist($producto);
            $this->em->flush();
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => 'Producto eliminado'
                                ], 200);
        }
    }
}
