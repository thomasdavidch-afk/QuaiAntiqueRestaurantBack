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
     * Route publique : Consulter la liste des menus
     */
    #[Route('/menus', name: 'get_menus', methods: ['GET'])]
    public function getMenus(MenuRepository $menuRepository): JsonResponse
    {
        $menus = $menuRepository->findAll();

        return $this->json($menus, Response::HTTP_OK, [], ['groups' => 'menu:read']);
    }

    /**
     * Route Admin : Créer un nouveau menu
     */
    #[Route('/admin/menus', name: 'create_menu', methods: ['POST'])]
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

        $restaurant = $restaurantRepository->findOneBy(['uuid' => $data['restaurantUuid']]);
        if (!$restaurant) {
            return $this->json(['error' => 'Restaurant non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $menu = new Menu();
        $menu->setTitle($data['title']);
        $menu->setDescription($data['description'] ?? '');
        $menu->setPrice((int) $data['price']);
        $menu->setRestaurant($restaurant);

        if (!empty($data['foodUuids']) && is_array($data['foodUuids'])) {
            foreach ($data['foodUuids'] as $foodUuid) {
                $food = $foodRepository->findOneBy(['uuid' => $foodUuid]);
                if ($food) {
                    $menu->addFood($food);
                }
            }
        }

        if (!empty($data['categoryUuids']) && is_array($data['categoryUuids'])) {
            foreach ($data['categoryUuids'] as $categoryUuid) {
                $category = $categoryRepository->findOneBy(['uuid' => $categoryUuid]);
                if ($category) {
                    $menu->addCategory($category);
                }
            }
        }

        $em->persist($menu);
        $em->flush();

        return $this->json([
            'message' => 'Menu créé avec succès !',
            'uuid' => $menu->getUuid(),
            'title' => $menu->getTitle(),
            'price' => $menu->getPrice()
        ], Response::HTTP_CREATED);
    }

    /**
     * Route Admin : Modifier un menu existant via son UUID
     */
    #[Route('/admin/menus/{uuid}', name: 'update_menu', methods: ['PUT'])]
    public function updateMenu(
        string $uuid,
        Request $request,
        EntityManagerInterface $em,
        MenuRepository $menuRepository,
        FoodRepository $foodRepository
    ): JsonResponse {
        $menu = $menuRepository->findOneBy(['uuid' => $uuid]);

        if (!$menu) {
            return $this->json(['error' => 'Menu non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $menu->setTitle($data['title']);
        }
        if (isset($data['price'])) {
            $menu->setPrice((int) $data['price']);
        }
        if (isset($data['description'])) {
            $menu->setDescription($data['description']);
        }

        // Réinitialiser et associer les nouveaux plats
        if (isset($data['foodUuids']) && is_array($data['foodUuids'])) {
            // Vider les plats actuels
            foreach ($menu->getFoods() as $existingFood) {
                $menu->removeFood($existingFood);
            }
            // Ajouter les nouveaux plats
            foreach ($data['foodUuids'] as $foodUuid) {
                $food = $foodRepository->findOneBy(['uuid' => $foodUuid]);
                if ($food) {
                    $menu->addFood($food);
                }
            }
        }

        $em->flush();

        return $this->json([
            'message' => 'Menu mis à jour avec succès !',
            'uuid' => $menu->getUuid()
        ], Response::HTTP_OK);
    }

    /**
     * Route Admin : Supprimer un menu via son UUID
     */
    #[Route('/admin/menus/{uuid}', name: 'delete_menu', methods: ['DELETE'])]
    public function deleteMenu(
        string $uuid,
        EntityManagerInterface $em,
        MenuRepository $menuRepository
    ): JsonResponse {
        $menu = $menuRepository->findOneBy(['uuid' => $uuid]);

        if (!$menu) {
            return $this->json(['error' => 'Menu non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($menu);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}