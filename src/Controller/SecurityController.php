<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use OpenApi\Annotations as OA;

#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/registration', name: 'registration', methods: 'POST')]
    /** @OA\Post(
     *     path="/api/registration",
     *     summary="Inscription d'un nouvel utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données de l'utilisateur à inscrire",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="adresse@email.com"),
     *             @OA\Property(property="password", type="string", example="Mot de passe"),
     *             @OA\Property(property="firstname", type="string", example="Prénom"),
     *             @OA\Property(property="lastname", type="string", example="Nom de famille"),
     *             @OA\Property(property="guestNumber", type="integer", example=4),
     *             @OA\Property(property="allergy", type="string", example="Aucune")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur inscrit avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", type="string", example="Nom d'utilisateur"),
     *             @OA\Property(property="apiToken", type="string", example="31a023e212f116124a36af14ea0c1c3806eb9378"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string", example="ROLE_USER"))
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        $user->setUuid(Uuid::v4()->toRfc4122());
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setRoles(['ROLE_USER']);

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $user->getPassword())
        );

        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: 'POST')]
    /** @OA\Post(
     *     path="/api/login",
     *     summary="Connexion d'un utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données de connexion",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="adresse@email.com"),
     *             @OA\Property(property="password", type="string", example="Mot de passe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", type="string", example="Nom d'utilisateur"),
     *             @OA\Property(property="apiToken", type="string", example="31a023e212f116124a36af14ea0c1c3806eb9378"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string", example="ROLE_USER"))
     *         )
     *     )
     * )
     */

    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Missing or invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/account/me', name: 'me', methods: 'GET')]
    /** @OA\Get(
     *     path="/api/account/me",
     *     summary="Récupérer les informations de l'utilisateur connecté",
     *     @OA\Response(
     *         response=200,
     *         description="Informations de l'utilisateur",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example="1"),
     *             @OA\Property(property="email", type="string", example="adresse@email.com"),
     *             @OA\Property(property="firstname", type="string", example="Prénom"),
     *             @OA\Property(property="lastname", type="string", example="Nom de famille"),
     *             @OA\Property(property="guestNumber", type="integer", example=4),
     *             @OA\Property(property="allergy", type="string", example="Aucune")
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(
                ['message' => 'Unauthorized'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = $this->serializer->serialize(
            $user,
            'json',
            ['groups' => ['user:read']]
        );

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/account/edit', name: 'edit', methods: 'PUT')]
    /** @OA\Put(
     *     path="/api/account/edit",
     *     summary="Modifier les informations de l'utilisateur connecté",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="adresse@email.com"),
     *             @OA\Property(property="password", type="string", example="Mot de passe"),
     *             @OA\Property(property="firstname", type="string", example="Prénom"),
     *             @OA\Property(property="lastname", type="string", example="Nom de famille"),
     *             @OA\Property(property="guestNumber", type="integer", example=4),
     *             @OA\Property(property="allergy", type="string", example="Aucune"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Informations mises à jour avec succès"
     *     )
     * )
     */
    public function edit(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(
                ['message' => 'Unauthorized'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = $request->toArray();

        $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $user,
                'groups' => ['user:write']
            ]
        );

        if (!empty($data['password'])) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );
        }

        $user->setUpdatedAt(new DateTimeImmutable());

        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}