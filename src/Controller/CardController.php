<?php

namespace App\Controller;

use App\Entity\Food;
use App\Repository\CategoryRepository;
use App\Repository\FoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use OpenApi\Annotations as OA; // <-- Import de OpenApi Annotations

#[Route('/api', name: 'api_')]
class CardController extends AbstractController
{
    // ==========================================
    // 1. LECTURE (Accès Public)
    // ==========================================

    /**
     * Route publique pour afficher toute la carte (Catégories et Plats associés)
     * GET /api/card
     * 
     * @OA\Get(
     *     path="/api/card",
     *     summary="Afficher toute la carte",
     *     tags={"Card"},
     *     @OA\Response(
     *         response=200,
     *         description="Retourne toutes les catégories de la carte (Entrées, Plats, Desserts) avec leurs plats associés."
     *     )
     * )
     */
    #[Route('/card', name: 'get_card', methods: ['GET'])]
    public function getCard(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();

        return $this->json($categories, 200, [], ['groups' => 'category:read']);
    }

    /**
     * Route publique pour voir le détail d'un plat spécifique via son UUID
     * GET /api/card/foods/{uuid}
     * 
     * @OA\Get(
     *     path="/api/card/foods/{uuid}",
     *     summary="Détail d'un plat spécifique",
     *     tags={"Card"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="L'UUID du plat à afficher",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du plat retournés avec succès."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plat introuvable."
     *     )
     * )
     */
    #[Route('/card/foods/{uuid}', name: 'get_card_food', methods: ['GET'])]
    public function showFood(string $uuid, FoodRepository $foodRepository): JsonResponse
    {
        $food = $foodRepository->findOneBy(['uuid' => $uuid]);
        if (!$food) {
            return $this->json(['message' => 'Plat introuvable.'], 404);
        }

        return $this->json($food, 200, [], ['groups' => 'food:read']);
    }

    // ==========================================
    // 2. ÉCRITURE (Admin Uniquement)
    // ==========================================

    /**
     * Ajouter un nouveau plat sur la carte
     * POST /api/card/foods
     * 
     * @OA\Post(
     *     path="/api/card/foods",
     *     summary="Ajouter un nouveau plat (Admin)",
     *     tags={"Card"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "price", "categoryUuid"},
     *             @OA\Property(property="title", type="string", example="Feuilleté de chèvre"),
     *             @OA\Property(property="description", type="string", example="Un délicieux feuilleté croustillant au chèvre chaud..."),
     *             @OA\Property(property="price", type="integer", example=950, description="Prix en centimes (ex: 950 pour 9.50€)"),
     *             @OA\Property(property="categoryUuid", type="string", example="3c6f4b23-10ba-4c92-bd88-510fc569ea4a")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Plat créé avec succès."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données obligatoires manquantes ou invalides."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Catégorie associée introuvable."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit (Droits Admin requis)."
     *     )
     * )
     */
    #[Route('/card/foods', name: 'create_card_food', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createFood(
        Request $request, 
        CategoryRepository $categoryRepository, 
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // On récupère le UUID de la catégorie peu importe la clé utilisée par le JS
        $categoryUuid = $data['categoryUuid'] ?? $data['category_id'] ?? $data['category'] ?? null;
        $title = $data['title'] ?? null;
        $price = $data['price'] ?? null;

        if (empty($title) || empty($price) || empty($categoryUuid)) {
            return $this->json(['message' => 'Le titre, le prix et la catégorie sont obligatoires.'], 400);
        }

        $category = $categoryRepository->findOneBy(['uuid' => $categoryUuid]);
        if (!$category) {
            return $this->json(['message' => 'La catégorie spécifiée n\'existe pas.'], 404);
        }

        $food = new Food();
        $food->setUuid(Uuid::v4()->toRfc4122());
        $food->setTitle($title);
        $food->setDescription($data['description'] ?? null);
        $food->setPrice((int)$price);
        $food->setCategory($category); 

        $em->persist($food);
        $em->flush();

        return $this->json($food, 201, [], ['groups' => 'food:read']);
    }

    /**
     * Modifier un plat existant sur la carte
     * PUT /api/card/foods/{uuid}
     * 
     * @OA\Put(
     *     path="/api/card/foods/{uuid}",
     *     summary="Modifier un plat existant (Admin)",
     *     tags={"Card"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="L'UUID du plat à modifier",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Nouveau titre de plat"),
     *             @OA\Property(property="description", type="string", example="Nouvelle description de plat"),
     *             @OA\Property(property="price", type="integer", example=1050),
     *             @OA\Property(property="categoryUuid", type="string", example="3c6f4b23-10ba-4c92-bd88-510fc569ea4a")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plat modifié avec succès."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plat ou catégorie spécifiée introuvable."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit (Droits Admin requis)."
     *     )
     * )
     */
    #[Route('/card/foods/{uuid}', name: 'update_card_food', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateFood(
        string $uuid,
        Request $request,
        FoodRepository $foodRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $food = $foodRepository->findOneBy(['uuid' => $uuid]);
        if (!$food) {
            return $this->json(['message' => 'Plat introuvable.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $food->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $food->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            $food->setPrice((int)$data['price']);
        }

        if (isset($data['categoryUuid'])) {
            $category = $categoryRepository->findOneBy(['uuid' => $data['categoryUuid']]);
            if (!$category) {
                return $this->json(['message' => 'La catégorie spécifiée n\'existe pas.'], 404);
            }
            $food->setCategory($category);
        }

        $em->flush();

        return $this->json($food, 200, [], ['groups' => 'food:read']);
    }

    /**
     * Supprimer un plat de la carte
     * DELETE /api/card/foods/{uuid}
     * 
     * @OA\Delete(
     *     path="/api/card/foods/{uuid}",
     *     summary="Supprimer un plat de la carte (Admin)",
     *     tags={"Card"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="L'UUID du plat à supprimer",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Le plat a bien été supprimé de la carte."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plat introuvable."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit (Droits Admin requis)."
     *     )
     * )
     */
    #[Route('/card/foods/{uuid}', name: 'delete_card_food', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteFood(
        string $uuid,
        FoodRepository $foodRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $food = $foodRepository->findOneBy(['uuid' => $uuid]);
        if (!$food) {
            return $this->json(['message' => 'Plat introuvable.'], 404);
        }

        $em->remove($food);
        $em->flush();

        return $this->json(['message' => 'Le plat a bien été supprimé de la carte.'], 200);
    }
}