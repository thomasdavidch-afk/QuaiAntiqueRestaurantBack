<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Annotations as OA; // <-- Ne pas oublier l'import OpenApi

#[Route('/api/booking')]
#[IsGranted('ROLE_USER')]
class BookingController extends AbstractController
{
    /**
     * @OA\Post(
     *     path="/api/booking",
     *     summary="Créer une nouvelle réservation (Max 50 couverts par service)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="date", type="string", format="date", example="2026-07-25"),
     *             @OA\Property(property="time", type="string", format="time", example="19:30"),
     *             @OA\Property(property="guestNumber", type="integer", example=4),
     *             @OA\Property(property="allergy", type="string", example="Arachides")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Réservation confirmée"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur (ex: Service complet, Date manquante)"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé (Token manquant ou invalide)"
     *     )
     * )
     */
    #[Route('', name: 'api_booking_create', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em, 
        RestaurantRepository $restaurantRepo,
        BookingRepository $bookingRepo
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $dateString = $data['date'] ?? null;
        $timeString = $data['time'] ?? null;
        $guestNumber = $data['guestNumber'] ?? 1;

        if (!$dateString || !$timeString) {
            return $this->json(['message' => 'Date et heure requises'], 400);
        }

        $bookingDate = new \DateTime($dateString . ' ' . $timeString);
        
        $isMidi = (int) $bookingDate->format('H') < 16;
        $reservationsDuJour = $bookingRepo->findByDate($dateString);
        
        $couvertsDejaReserves = 0;
        foreach ($reservationsDuJour as $resa) {
            $resaHeure = (int) $resa->getBookingAt()->format('H');
            $resaIsMidi = $resaHeure < 16;
            
            if ($isMidi === $resaIsMidi) {
                $couvertsDejaReserves += $resa->getGuestNumber();
            }
        }

        if (($couvertsDejaReserves + $guestNumber) > 50) {
            return $this->json(['message' => 'Désolé, nous sommes complets pour ce service (Max 50 couverts).'], 400);
        }

        $booking = new Booking();
        $booking->setClient($user);
        $booking->setBookingAt($bookingDate);
        $booking->setGuestNumber($guestNumber);
        $booking->setAllergy($data['allergy'] ?? $user->getAllergy());
        $booking->setUuid(Uuid::v4()->toRfc4122());
        $booking->setCreatedAt(new \DateTimeImmutable());
        $booking->setUpdatedAt(new \DateTimeImmutable());

        $restaurant = $restaurantRepo->findOneBy([]);
        if ($restaurant) $booking->setRestaurant($restaurant);

        $em->persist($booking);
        $em->flush();

        return $this->json(['message' => 'Réservation confirmée !', 'uuid' => $booking->getUuid()], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/booking",
     *     summary="Voir la liste des réservations de l'utilisateur connecté",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des réservations",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="uuid", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="date", type="string", format="date", example="2026-07-25"),
     *                 @OA\Property(property="time", type="string", format="time", example="19:30"),
     *                 @OA\Property(property="guestNumber", type="integer", example=4),
     *                 @OA\Property(property="allergy", type="string", example="Arachides")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé"
     *     )
     * )
     */
    #[Route('', name: 'api_booking_list', methods: ['GET'])]
    public function list(BookingRepository $bookingRepo): JsonResponse
    {
        $user = $this->getUser();
        $bookings = $bookingRepo->findBy(['client' => $user], ['bookingAt' => 'DESC']);

        $data = [];
        foreach ($bookings as $booking) {
            $data[] = [
                'uuid' => $booking->getUuid(),
                'date' => $booking->getBookingAt()->format('Y-m-d'),
                'time' => $booking->getBookingAt()->format('H:i'),
                'guestNumber' => $booking->getGuestNumber(),
                'allergy' => $booking->getAllergy()
            ];
        }

        return $this->json($data);
    }

    /**
     * @OA\Put(
     *     path="/api/booking/{uuid}",
     *     summary="Modifier les informations d'une réservation",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="L'identifiant unique (UUID) de la réservation",
     *         @OA\Schema(type="string", example="123e4567-e89b-12d3-a456-426614174000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="guestNumber", type="integer", example=2),
     *             @OA\Property(property="allergy", type="string", example="Aucune")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Réservation modifiée avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Réservation introuvable"
     *     )
     * )
     */
    #[Route('/{uuid}', name: 'api_booking_edit', methods: ['PUT'])]
    public function edit(
        string $uuid, 
        Request $request, 
        BookingRepository $bookingRepo, 
        EntityManagerInterface $em
    ): JsonResponse {
        $booking = $bookingRepo->findOneBy(['uuid' => $uuid, 'client' => $this->getUser()]);

        if (!$booking) {
            return $this->json(['message' => 'Réservation introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['guestNumber'])) $booking->setGuestNumber($data['guestNumber']);
        if (isset($data['allergy'])) $booking->setAllergy($data['allergy']);
        $booking->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->json(['message' => 'Réservation modifiée avec succès']);
    }

    /**
     * @OA\Delete(
     *     path="/api/booking/{uuid}",
     *     summary="Annuler une réservation",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="L'identifiant unique (UUID) de la réservation à annuler",
     *         @OA\Schema(type="string", example="123e4567-e89b-12d3-a456-426614174000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Réservation annulée avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Réservation introuvable"
     *     )
     * )
     */
    #[Route('/{uuid}', name: 'api_booking_delete', methods: ['DELETE'])]
    public function delete(
        string $uuid, 
        BookingRepository $bookingRepo, 
        EntityManagerInterface $em
    ): JsonResponse {
        $booking = $bookingRepo->findOneBy(['uuid' => $uuid, 'client' => $this->getUser()]);

        if (!$booking) {
            return $this->json(['message' => 'Réservation introuvable ou non autorisée'], 404);
        }

        $em->remove($booking);
        $em->flush();

        return $this->json(['message' => 'Réservation annulée avec succès']);
    }
}