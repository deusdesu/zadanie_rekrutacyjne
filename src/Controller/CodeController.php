<?php

namespace App\Controller;

use App\Entity\UserCode;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DivisibleBy;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;

class CodeController extends AbstractController
{
    /**
     * @param Request $request
     * @param ManagerRegistry $doctrine
     * @return Response
     */
    #[Route('/code', name: 'app_code', methods: 'POST')]
    public function createCode(Request $request, ManagerRegistry $doctrine): Response
    {
        //wez kod
        $code = $request->request->get('code');
        //waliduj kod, błędy przekaz do zmiennej $errors
        //jezeli zwrocono false wyswietl errory walidacji,
        if (!($this->addCode($code, $doctrine)[0])) {
            $errors = $this->addCode($code, $doctrine)[1];
            return $this->render('home/errors.html.twig', ["errors" => $errors]);
        }
        return $this->redirectToRoute('app_home');
    }

    /**
     * @param string $code
     * @param ManagerRegistry $doctrine
     * @return array [bool,errorsArray]
     *
     * funkcja wywołuje walidacje, po poprawnej walidacji dodaje kod do bazy
     */
    private function addCode(string $code, ManagerRegistry $doctrine): array
    {
        //spr czy kod jest w bazie
        $czyJestWBazie = $doctrine->getRepository(UserCode::class)->findOneBy(['number' => $code]);
        if ($czyJestWBazie !== null) {
            return [false, ["Kod $code jest juz w bazie."]];
        }
        $errors = $this->validation($code);

        //jeżeli są błędy, zwróć błędy walidacji
        if (!empty($errors)) {
            return [false, $errors];
        }
        $entityManager = $doctrine->getManager();

        $sendCode = new UserCode();
        $sendCode->setNumber($code);
        $sendCode->setFlag(1);
        $sendCode->setDate(new DateTime());

        $entityManager->persist($sendCode);
        $entityManager->flush();

        return [true, $errors];
    }

    /**
     * @param string $code
     * @return array
     *
     * funkcja do walidacji kodu
     */
    private function validation(string $code): array
    {
        $errors = [];
        $validator = Validation::createValidator();
        $violationCode = $validator->validate($code, [
            //TEST 1    sprawdz czy ma 15 znaków
            new Length(['min' => 15], minMessage: 'Code must have 15 characters.'),
            new Length(['max' => 15], maxMessage: 'Code must have 15 characters.'),
            new NotBlank(),

            //TEST 2    użyj wyrażenia regularnego 14 liter + dowolny znak 0-9 lub a-Z
            new Regex(pattern: '/[0-9]{14}[a-zA-Z0-9]{1}/', message: "Code does not match the formula: 14 numbers, 1 number, or an alphanumeric character. Example: 00000512752451M."),

            //TEST 3    00001236256212M nie spełnia warunku
            new NotEqualTo('00001236256212M'),
        ]);

        //dodanie elementów do tablicy $errors
        if (0 !== count($violationCode)) {
            $errors = $this->mergeArrays($errors, $violationCode);
        }

        //suma ma byc dzielona przez 2, bez literki
        $amount = 0;

        $chars = str_split($code);

        foreach ($chars as $num) {
            if (is_numeric($num)) {
                $amount += $num;
            }
        }
        $violationCode = $validator->validate($amount, [
            new DivisibleBy(2, message: 'Invalid code.'),
        ]);
        if (0 !== count($violationCode)) {
            $errors = $this->mergeArrays($errors, $violationCode);
        }
        return $errors;

    }

    /**
     * @param $violations
     * @return array
     *
     * fukncja używana w validation,
     * słuzy do dodania kolejnych błędów walidacji do głownej tablicy błędów $errors w validation
     */
    private function checkErrors($violations):array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = $violation->getMessage();
        }
        return $errors;
    }

    /**
     * @OA\Tag(name="return code", description="API sprawdzające czy dany kod jest w bazie.")
     * @OA\Response(
     *     response=201,
     *     description="Pomyślnie zwrócono dane o kodzie."
     * )
     * @param ManagerRegistry $doctrine
     * @param string $number
     * @return JsonResponse
     *
     * REST api, zwraca true jeżeli kod jest w bazie
     */
    #[Route('/api/returnCode/{number}', name: 'returnCode', methods: 'GET')]
    public function returnCode(ManagerRegistry $doctrine, string $number): JsonResponse
    {
        //pobierz obiekt po number
        $szukanyObiekt = $doctrine->getRepository(UserCode::class)->findOneBy(['number' => $number]);

        //if istnieje to zwroc True
        if ($szukanyObiekt !== null) {
            return $this->json('true');
        }

        //if nie istnieje to zwroc False
        return $this->json('false');
    }

    /**
     * @OA\Tag(name="add code", description="API dodające kod do bazy.")
     * @OA\Parameter(name="number", in="query", @OA\Schema(type="string"), required=true, description="kod użytkownika", allowEmptyValue=false)
     * @OA\Response(
     *     response=201,
     *     description="Pomyślnie dodano kod."
     * )
     * @param ManagerRegistry $doctrine
     * @param Request $request
     * @return JsonResponse
     *
     * REST api, wywołuje f addCode, która waliduje kod, po poprawnej walidacji wrzuca kod do bazy
     */
    #[Route('/api/add', name: 'addCode', methods: 'POST')]
    public function add(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $number = $request->get('number');
        if (!($this->addCode($number, $doctrine)[0])) {
            $errors = $this->addCode($number, $doctrine)[1];
            return $this->json([
                'message' => "błąd walidacji kodu: $number.",
                'errors' => $errors,
                'status' => '400 '
            ]);
        }
        return $this->json([
                'message' => "pomyslnie dodano kod: $number.",
                'status' => '201'
            ]
        );
    }

    /**
     * @OA\Tag(name="delete code", description="API usuwające kod z bazy.")
     * @OA\Response(
     *     response=201,
     *     description="Pomyślnie usunięto kod."
     * )
     * @param ManagerRegistry $doctrine
     * @param string $number
     * @return JsonResponse
     *
     * REST api sprawdza czy kod jest w bazie, jeżeli tak to go usuwa
     */
    #[Route('/api/delete/{number}', name: 'deleteCode', methods: 'DELETE')]
    public function delete(ManagerRegistry $doctrine, string $number): JsonResponse
    {
        $szukanyObiekt = $doctrine->getRepository(UserCode::class)->findOneBy(['number' => $number]);
        if ($szukanyObiekt === null) {
            return $this->json([
                    'message' => "Kodu: $number nie ma w bazie.",
                    'status' => '404'
                ]
            );

        }
        $entityManager = $doctrine->getManager();
        $entityManager->remove($szukanyObiekt);
        $entityManager->flush();
        return $this->json([
                'message' => "usunięto kod: $number.",
                'status' => '200'
            ]
        );
    }

    /**
     * @param array $errors
     * @param $violationCode
     * @return array
     *
     * f scala dwie tablice
     */
    private function mergeArrays(array $errors, $violationCode): array
    {
        array_push($errors, ...$this->checkErrors($violationCode));
        return $errors;
    }


}
