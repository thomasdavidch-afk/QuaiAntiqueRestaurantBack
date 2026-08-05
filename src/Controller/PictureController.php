<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api', name: 'api_')]
#[OA\Tag(name: 'Pictures')]
class PictureController extends AbstractController
{
    /**
     * Route publique : Consulter la liste des photos
     */
    #[Route('/pictures', name: 'get_pictures', methods: ['GET'])]
    public function getPictures(PictureRepository $pictureRepository): JsonResponse
    {
        $pictures = $pictureRepository->findAll();

        $data = [];
        foreach ($pictures as $pic) {
            $data[] = [
                'uuid'  => method_exists($pic, 'getUuid') ? $pic->getUuid() : $pic->getId(),
                'title' => $pic->getTitle(),
                'path'  => $pic->getPath()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    /**
     * Route Admin : Créer une nouvelle photo
     */
    #[Route('/admin/pictures', name: 'admin_pictures_add', methods: ['POST'])]
    public function addPicture(
        Request $request, 
        EntityManagerInterface $em,
        RestaurantRepository $restaurantRepository
    ): JsonResponse
    {
        // 1. Récupération des données soumises via FormData
        $title = $request->request->get('title');
        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');

        if (!$title || !$file) {
            return new JsonResponse(['error' => 'Les champs title et image sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        // 2. Récupération du restaurant à associer
        $restaurantId = $request->request->get('restaurant_id');
        $restaurant = null;

        if ($restaurantId) {
            $restaurant = $restaurantRepository->find($restaurantId);
        }
        
        // Si aucun ID spécifique transmis ou trouvé, récupérer le premier restaurant disponible
        if (!$restaurant) {
            $restaurant = $restaurantRepository->findOneBy([]);
        }

        if (!$restaurant) {
            return new JsonResponse(['error' => 'Aucun restaurant trouvé en base de données pour associer la photo.'], Response::HTTP_BAD_REQUEST);
        }

        // 3. Vérification et création du dossier de destination s'il n'existe pas
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!is_dir($uploadsDir)) {
            if (!mkdir($uploadsDir, 0777, true) && !is_dir($uploadsDir)) {
                return new JsonResponse(['error' => 'Impossible de créer le dossier d\'upload.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // 4. Génération du nom de fichier unique et déplacement
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $newFilename = uniqid('pic_', true) . '.' . $extension;

        try {
            $file->move($uploadsDir, $newFilename);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'enregistrement de l\'image : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 5. Création et hydratation de l'entité Picture
        $picture = new Picture();

        if (method_exists($picture, 'setUuid') && (method_exists($picture, 'getUuid') && !$picture->getUuid())) {
            $picture->setUuid(Uuid::v4());
        }

        $picture->setTitle($title);
        $picture->setPath('/uploads/' . $newFilename);
        
        // Association obligatoire du Restaurant à la Photo
        $picture->setRestaurant($restaurant);

        // 6. Enregistrement en base de données avec Try/Catch
        try {
            $em->persist($picture);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur de base de données : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'Image ajoutée avec succès !',
            'data'    => [
                'uuid'  => method_exists($picture, 'getUuid') ? $picture->getUuid() : $picture->getId(),
                'title' => $picture->getTitle(),
                'path'  => $picture->getPath()
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Route Admin : Supprimer une photo via son UUID
     */
    #[Route('/admin/pictures/{uuid}', name: 'delete_picture', methods: ['DELETE'])]
    public function deletePicture(
        string $uuid,
        EntityManagerInterface $em,
        PictureRepository $pictureRepository
    ): JsonResponse {
        $picture = $pictureRepository->findOneBy(['uuid' => $uuid]);

        if (!$picture) {
            // Repli au cas où $uuid serait en réalité un ID numérique
            $picture = $pictureRepository->find($uuid);
        }

        if (!$picture) {
            return $this->json(['error' => 'Photo non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $em->remove($picture);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Route Admin : Modifier une photo existante (titre et/ou fichier)
     */
    #[Route('/admin/pictures/{uuid}', name: 'admin_pictures_edit', methods: ['POST'])]
    public function editPicture(
        string $uuid,
        Request $request,
        EntityManagerInterface $em,
        PictureRepository $pictureRepository
    ): JsonResponse {
        $picture = $pictureRepository->findOneBy(['uuid' => $uuid]);

        if (!$picture) {
            $picture = $pictureRepository->find($uuid);
        }

        if (!$picture) {
            return $this->json(['error' => 'Photo non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        // 1. Mise à jour du titre si fourni
        $title = $request->request->get('title');
        if ($title) {
            $picture->setTitle($title);
        }

        // 2. Traitement du nouveau fichier image si fourni
        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');

        if ($file) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }

            // Supprimer l'ancienne image du serveur si elle existe
            $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $picture->getPath();
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }

            // Enregistrer la nouvelle image
            $extension = $file->guessExtension() ?? 'jpg';
            $newFilename = uniqid('pic_', true) . '.' . $extension;

            try {
                $file->move($uploadsDir, $newFilename);
                $picture->setPath('/uploads/' . $newFilename);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'enregistrement de la nouvelle image : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // 3. Sauvegarde en BDD
        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'Image modifiée avec succès !',
            'data'    => [
                'uuid'  => method_exists($picture, 'getUuid') ? $picture->getUuid() : $picture->getId(),
                'title' => $picture->getTitle(),
                'path'  => $picture->getPath()
            ]
        ], Response::HTTP_OK);
    }
}