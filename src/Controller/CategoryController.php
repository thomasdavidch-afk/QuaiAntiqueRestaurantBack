<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Récupérer toutes les catégories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des catégories retournée avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref=@Model(type=Category::class, groups={"category:read"}))
     *         )
     *     )
     * )
     */
    #[Route('', name: 'api_categories_get_all', methods: ['GET'])]
    public function getAll(CategoryRepository $categoryRepository, SerializerInterface $serializer): JsonResponse
    {
        $categories = $categoryRepository->findAll();
        $json = $serializer->serialize($categories, 'json', ['groups' => 'category:read']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{uuid}",
     *     summary="Récupérer une catégorie par son UUID",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="L'UUID de la catégorie",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Catégorie trouvée",
     *         @OA\JsonContent(ref=@Model(type=Category::class, groups={"category:read"}))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Catégorie non trouvée"
     *     )
     * )
     */
    #[Route('/{uuid}', name: 'api_categories_get_one', methods: ['GET'])]
    public function getOne(string $uuid, CategoryRepository $categoryRepository, SerializerInterface $serializer): JsonResponse
    {
        $category = $categoryRepository->findOneBy(['uuid' => $uuid]);
        if (!$category) {
            return new JsonResponse(['message' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $json = $serializer->serialize($category, 'json', ['groups' => 'category:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Créer une nouvelle catégorie",
     *     tags={"Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", example="Entrées")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Catégorie créée avec succès",
     *         @OA\JsonContent(ref=@Model(type=Category::class, groups={"category:read"}))
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Droits insuffisants (ROLE_ADMIN requis)"),
     *     security={{"X-AUTH-TOKEN": {}}}
     * )
     */
    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['title'])) {
            return new JsonResponse(['message' => 'Le titre de la catégorie est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setTitle($data['title']); // Utilisation de setTitle()
        
        // Note : L'UUID et le createdAt sont gérés automatiquement dans le __construct() de l'entité

        $em->persist($category);
        $em->flush();

        $json = $serializer->serialize($category, 'json', ['groups' => 'category:read']);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{uuid}",
     *     summary="Modifier une catégorie",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="L'UUID de la catégorie à modifier",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Entrées chaudes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Catégorie modifiée avec succès",
     *         @OA\JsonContent(ref=@Model(type=Category::class, groups={"category:read"}))
     *     ),
     *     @OA\Response(response=404, description="Catégorie non trouvée"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Droits insuffisants (ROLE_ADMIN requis)"),
     *     security={{"X-AUTH-TOKEN": {}}}
     * )
     */
    #[Route('/{uuid}', name: 'api_categories_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $uuid, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $category = $categoryRepository->findOneBy(['uuid' => $uuid]);
        if (!$category) {
            return new JsonResponse(['message' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['title'])) {
            $category->setTitle($data['title']); // Utilisation de setTitle()
            
            // Si vous avez un setter setUpdatedAt() dans votre entité, décommentez la ligne suivante :
            // $category->setUpdatedAt(new \DateTimeImmutable());
        }

        $em->flush();

        $json = $serializer->serialize($category, 'json', ['groups' => 'category:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{uuid}",
     *     summary="Supprimer une catégorie",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="L'UUID de la catégorie à supprimer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Catégorie supprimée avec succès"
     *     ),
     *     @OA\Response(response=404, description="Catégorie non trouvée"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Droits insuffisants (ROLE_ADMIN requis)"),
     *     security={{"X-AUTH-TOKEN": {}}}
     * )
     */
    #[Route('/{uuid}', name: 'api_categories_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $uuid, CategoryRepository $categoryRepository, EntityManagerInterface $em): JsonResponse
    {
        $category = $categoryRepository->findOneBy(['uuid' => $uuid]);
        if (!$category) {
            return new JsonResponse(['message' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($category);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}