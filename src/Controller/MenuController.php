<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\CategoryRepository;
use App\Repository\FoodRepository;
use App\Repository\MenuRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
#[OA\Tag(name: 'Menus')]
class MenuController extends AbstractController
{
    /**
     * Route publique : Permet aux clients et visiteurs de consulter la liste des menus avec leurs plats.
     */
    #[Route('/menus', name: 'get_menus', methods: ['GET'])]
    #[OA\Get(
        path: '/api/menus',
        summary: 'Obtenir la liste de tous les menus avec leurs plats',
        description: 'Retourne la liste complète des menus avec les détails de tous les plats qui les composent.'
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des menus avec leurs plats récupérée avec succès',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'uuid', type: 'string', example: 'd3b07384-d113-43a6-a00e-1f3d32832560'),
                    new OA\Property(property: 'title', type: 'string', example: 'Menu Gourmand'),
                    new OA\Property(property: 'description', type: 'string', example: 'Entrée, plat et dessert au choix'),
                    new OA\Property(property: 'price', type: 'integer', example: 2500, description: 'Prix en centimes (25.00 €)'),
                    new OA\Property(
                        property: 'foods',
                        type: 'array',
                        description: 'Liste des plats composant ce menu',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', example: 'f8c3de3d-1fea-4d7c-a8b0-29f63c4c34a1'),
                                new OA\Property(property: 'title', type: 'string', example: 'Salade Landaise'),
                                new OA\Property(property: 'description', type: 'string', example: 'Foie gras, gésiers, salade verte'),
                                new OA\Property(property: 'price', type: 'integer', example: 1200)
                            ]
                        )
                    )
                ]
            )
        )
    )]
    public function getMenus(MenuRepository $menuRepository): JsonResponse
    {
        $menus = $menuRepository->findAll();

        return $this->json($menus, Response::HTTP_OK, [], ['groups' => 'menu:read']);
    }

    /**
     * Route Admin : Créer un nouveau menu en y rattachant une liste de plats via leurs UUIDs.
     */
    #[Route('/admin/menus', name: 'create_menu', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/menus',
        summary: 'Créer un nouveau menu en sélectionnant des plats (Admin)',
        description: 'Permet à un administrateur de créer un menu et d\'y associer des plats existants via leurs UUIDs.'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'price', 'restaurantUuid'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Menu du Marché'),
                new OA\Property(property: 'description', type: 'string', example: 'Plats frais préparés du jour'),
                new OA\Property(property: 'price', type: 'integer', example: 2200, description: 'Prix en centimes (22.00 €)'),
                new OA\Property(property: 'restaurantUuid', type: 'string', example: 'a1b2c3d4-e5f6-7890-1234-56789abcdef0'),
                new OA\Property(
                    property: 'foodUuids',
                    type: 'array',
                    description: 'Tableau des UUIDs des plats sélectionnés pour constituer ce menu',
                    items: new OA\Items(type: 'string'),
                    example: ['f8c3de3d-1fea-4d7c-a8b0-29f63c4c34a1', 'e931ab56-82cd-4110-9b12-9876543210ab']
                ),
                new OA\Property(
                    property: 'categoryUuids',
                    type: 'array',
                    description: 'Tableau des UUIDs des catégories éventuellement associées',
                    items: new OA\Items(type: 'string'),
                    example: ['018f3a5b-9c7d-7123-8456-abcdef123456']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Menu créé avec succès et plats associés',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Menu créé avec succès !'),
                new OA\Property(property: 'uuid', type: 'string', example: 'd3b07384-d113-43a6-a00e-1f3d32832560'),
                new OA\Property(property: 'title', type: 'string', example: 'Menu du Marché'),
                new OA\Property(property: 'price', type: 'integer', example: 2200)
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides ou champs obligatoires manquants',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Les champs title, price et restaurantUuid sont obligatoires.')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Restaurant introuvable',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Restaurant non trouvé.')
            ]
        )
    )]
    public function createMenu(
        Request $request,
        EntityManagerInterface $em,
        RestaurantRepository $restaurantRepository,
        FoodRepository $foodRepository,
        CategoryRepository $categoryRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['title'], $data['price'], $data['restaurantUuid'])) {
            return $this->json([
                'error' => 'Les champs title, price et restaurantUuid sont obligatoires.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // 1. Recherche du Restaurant
        $restaurant = $restaurantRepository->findOneBy(['uuid' => $data['restaurantUuid']]);
        if (!$restaurant) {
            return $this->json(['error' => 'Restaurant non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // 2. Instanciation du Menu
        $menu = new Menu();
        $menu->setTitle($data['title']);
        $menu->setDescription($data['description'] ?? '');
        $menu->setPrice((int) $data['price']);
        $menu->setRestaurant($restaurant);

        // 3. Association des Plats
        if (!empty($data['foodUuids']) && is_array($data['foodUuids'])) {
            foreach ($data['foodUuids'] as $foodUuid) {
                $food = $foodRepository->findOneBy(['uuid' => $foodUuid]);
                if ($food) {
                    $menu->addFood($food);
                }
            }
        }

        // 4. Association des Catégories
        if (!empty($data['categoryUuids']) && is_array($data['categoryUuids'])) {
            foreach ($data['categoryUuids'] as $categoryUuid) {
                $category = $categoryRepository->findOneBy(['uuid' => $categoryUuid]);
                if ($category) {
                    $menu->addCategory($category);
                }
            }
        }

        // Persistance
        $em->persist($menu);
        $em->flush();

        return $this->json([
            'message' => 'Menu créé avec succès !',
            'uuid' => $menu->getUuid(),
            'title' => $menu->getTitle(),
            'price' => $menu->getPrice()
        ], Response::HTTP_CREATED);
    }
}