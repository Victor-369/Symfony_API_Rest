<?php
namespace App\Utilidades;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Entity\User;

class Utilidades {
    public static function checkToken(EntityManagerInterface $emi, Request $request): bool {
        // Decodificar el token
        $apiToken = $request->headers->get('X-AUTH-TOKEN');
        $decode = JWT::decode($apiToken, new Key($_ENV['JWT_SECRET'], 'HS512'));
        
        // Comprobar si usuario existe
        $user = $emi->getRepository(User::class)->findOneBy(['id' => $decode->aud]);
    
        if(!$user) {
            $esValido = false;
        } else {
            $esValido = true;
        }
    
        return $esValido;
    }
}

