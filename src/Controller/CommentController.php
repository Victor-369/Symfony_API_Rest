<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Utilidades\Utilidades;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Request;

class CommentController extends AbstractController
{
    private $em;
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    // Collects all comments from the logged in user.
    #[Route('api/comments', methods: ['GET'])]
    public function comment_list(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            $userId = Utilidades::getUserIdToken($this->em, $request);
            $datos = $this->em->getRepository(Comment::class)->findBy(['id_user' => $userId]);
            $serializedData = $serializer->normalize($datos);
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => $serializedData
                                ], 200);
        }
    }

    // Collects all comments from the logged in user, for a specific product.
    #[Route('api/comments/product/{id}', methods: ['GET'])]
    public function comments_listByProduct(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            $userId = Utilidades::getUserIdToken($this->em, $request);
            $datos = $this->em->getRepository(Comment::class)->findBy(['id_product' => $id, 'id_user' => $userId]);
            $serializedData = $serializer->normalize($datos);
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => $serializedData
                                ], 200);
        }
    }

    // Collects all comments from a specific user.
    #[Route('api/comments/user/{id}', methods: ['GET'])]
    public function comments_listByUser(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            // Comprueba que exista el usuario
            $existe = $this->em->getRepository(User::class)->findOneBy(['id' => $id]);            
            if(!$existe) {
                return $this->json(['estado' => 'error',
                                    'mensaje' => "El usuario con id '{$id}' no existe"], 400);
            }

            $datos = $this->em->getRepository(Comment::class)->findBy(['id_user' => $id]);
            $serializedData = $serializer->normalize($datos);
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => $serializedData
                                ], 200);
        }
    }

    // Create comment for a specific product.
    #[Route('api/comments/product/{id}', methods: ['POST'])]
    public function comments_create(int $id, Request $request): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            $data = json_decode($request->getContent(), true);

            if(!$data['comentario']) {
                return $this->json (['estado' => 'error',
                                    'mensaje' => 'El campo comentario es obligatorio'
                                    ], 400);
            }

            // Checks that product exists.
            $existe = $this->em->getRepository(Product::class)->findOneBy(['id' => $id]);            
            if(!$existe) {
                return $this->json(['estado' => 'error',
                                    'mensaje' => "El producto con id '{$id}' no existe"], 400);
            }
            
            $userId = Utilidades::getUserIdToken($this->em, $request);
            $fechaHora = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', time()));

            $entity = new Comment();
            $entity->setText($data['comentario']);
            $entity->setIdProduct($id);
            $entity->setIdUser($userId);            
            $entity->setCreatedAt($fechaHora);
            
            $this->em->persist($entity);
            $this->em->flush();
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => 'Comentario creado'
                                ], 200);
        }
    }

    #[Route('api/comments/{id}', methods: ['DELETE'])]
    public function product_delete(Request $request, int $id): JsonResponse
    {
        $esValido = Utilidades::checkToken($this->em, $request);

        if(!$esValido) {
            return $this->json(['estado' => 'error',
                                'mensaje' => 'Las credenciales ingresadas no son válidas'
                            ], Response::HTTP_BAD_REQUEST);
        } else {
            $comentario = $this->em->getRepository(Comment::class)->findOneBy(['id' => $id]);
            
            if(!$comentario) {
                return $this->json(['estado' => 'error',
                                    'mensaje' => "El comentario con id '$id' no existe"], 400);
            }

            if($comentario->getDeletedAt()) {
                return $this->json(['estado' => 'error',
                                    'mensaje' => "El comentario con id '$id' ya estaba borrado"], 400);
            }
            
            $fechaHora = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', time()));
            $comentario->setDeletedAt($fechaHora);
            
            $this->em->persist($comentario);
            $this->em->flush();
    
            return $this->json(['estado' => 'Ok',
                                'mensaje' => 'Comentario eliminado'
                                ], 200);
        }
    }
}
