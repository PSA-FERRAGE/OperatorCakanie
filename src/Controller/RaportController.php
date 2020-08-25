<?php

namespace App\Controller;

use App\Entity\Fer\Linka;
use App\Entity\Fer\Okno;
use App\Entity\Fer\Operator;
use App\Entity\Sapia\SSCQT03;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RaportController extends AbstractController
{
    /**
     * @Route(path="/analyse/{linka}", methods={"POST"}, name="analyse_action")
     *
     * @param Request $request
     * @param Linka   $linka
     *
     * @return JsonResponse
     */
    public function analyseAction(Request $request, Linka $linka)
    {
        $params     = $request->request;
        $datum      = $params->get('datum');
        $zmeny      = $params->get('zmeny', []);
        $trojzmenka = $params->get('trojzmenka', true);

        if ($datum == null || empty($zmeny)) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        $result        = [];
        $timeIntervals = $this->getTimeIntervals($zmeny, $datum, $trojzmenka);
        $sapia         = $this->getDoctrine()->getManager('sapia');

        foreach ($timeIntervals as $timeInterval) {
            $tmpResult = [
                'linka'       => $linka->getNazov(),
                'zmena'       => $timeInterval['zmena'],
                'zmenaString' => $timeInterval['zmenaString'],
                'trojzmenka'  => $trojzmenka,
                'operatori'   => $this->getOperatoriData($linka, $timeInterval, $sapia),
                'okna'        => $this->getOknaData($linka, $timeInterval, $sapia),
            ];

            $result[] = $tmpResult;
        }


        return new JsonResponse($result);
    }

    /**
     * @param array  $zmeny
     * @param string $datum
     * @param bool   $trojzmenka
     *
     * @return array
     */
    private function getTimeIntervals(array $zmeny, string $datum, bool $trojzmenka): array
    {
        return array_map(
            function ($elem) use ($trojzmenka, $datum) {
                $start = \DateTime::createFromFormat('d/m/Y H:i', $datum." 00:00");
                $end   = \DateTime::createFromFormat('d/m/Y H:i', $datum." 00:00");

                switch ($elem) {
                    case "R":
                        return [
                            'zmena'       => $elem,
                            'zmenaString' => "Ranná zmena",
                            'start'       => $start->add(new DateInterval('PT6H')),
                            'end'         => $end->add(new DateInterval(($trojzmenka ? "PT14H" : "PT18H"))),
                        ];
                    case "P":
                        return [
                            'zmena'       => $elem,
                            'zmenaString' => "Poobedná zmena",
                            'start'       => $start->add(new DateInterval('PT14H')),
                            'end'         => $end->add(new DateInterval(($trojzmenka ? "PT22H" : "P1DT2H"))),
                        ];
                    case "N":
                        return [
                            'zmena'       => $elem,
                            'zmenaString' => "Nočná zmena",
                            'start'       => $start->add(new DateInterval(($trojzmenka ? "PT22H" : "PT18H"))),
                            'end'         => $end->add(new DateInterval('P1DT6H')),
                        ];
                    default:
                        return [];
                }
            },
            $zmeny
        );
    }

    /**
     * @param Linka                  $linka
     * @param array                  $timeInterval
     * @param EntityManagerInterface $sapia
     *
     * @return array
     */
    private function getOperatoriData(Linka $linka, array $timeInterval, EntityManagerInterface $sapia): array
    {
        $result = [
            'tcy'       => ['labels' => [], 'vals' => [], 'min' => 0, 'max' => 0],
            'overtimes' => ['labels' => [], 'vals' => [], 'min' => 0, 'max' => 0],
        ];

        /** @var Operator $operator */
        foreach ($linka->getOperatori() as $operator) {
            /** @var SSCQT03[] $data */
            $data = $sapia->getRepository(SSCQT03::class)
                          ->findAllBetweenDates(
                              $timeInterval,
                              $operator->getMnemo()
                          );

            $processedData = [];

            for ($i = 1; $i < count($data); $i++) {
                $processedData[] = $data[$i]->getFINEVT()->getTimestamp() - $data[$i - 1]->getFINEVT()->getTimestamp();
            }

            $result['tcy']['labels'][] = $operator->getMnemo();
            $result['tcy']['vals'][]   = $this->computeMedianArray($processedData);

            $result['overtimes']['labels'][] = $operator->getMnemo();
            $result['overtimes']['vals'][]   = array_reduce(
                    $processedData,
                    function ($a, $b) use ($operator) {
                        return ($b >= $operator->getCiel()) ? ++$a : $a;
                    }
                ) ?? 0;
        }

        if (empty($result['tcy']['vals'])) {
            $result['tcy']['min'] = 0;
            $result['tcy']['max'] = 1;
        } else {
            $result['tcy']['min'] = min($result['tcy']['vals']);
            $result['tcy']['max'] = max($result['tcy']['vals']);
        }

        if (empty($result['overtimes']['vals'])) {
            $result['overtimes']['min'] = 0;
            $result['overtimes']['max'] = 1;
        } else {
            $result['overtimes']['min'] = min($result['overtimes']['vals']);
            $result['overtimes']['max'] = max($result['overtimes']['vals']);
        }

        array_multisort($result['tcy']['vals'], SORT_DESC, SORT_NUMERIC, $result['tcy']['labels'], SORT_STRING);
        array_multisort(
            $result['overtimes']['vals'],
            SORT_DESC,
            SORT_NUMERIC,
            $result['overtimes']['labels'],
            SORT_STRING
        );

        return $result;
    }

    private function getOknaData(Linka $linka, array $timeInterval, EntityManagerInterface $sapia): array
    {
        $result = [];

        /** @var Okno $okno */
        foreach ($linka->getOkna() as $okno) {
            /** @var SSCQT03[] $data */
            $data = $sapia->getRepository(SSCQT03::class)
                          ->findAllBetweenDates(
                              $timeInterval,
                              $okno->getMnemo()
                          );

            $processedData = [];
            $labels        = [];
            foreach ($data as $item) {
                $processedData[] = $item->getDURATION();
                $labels[]        = (string)$item->getDEBEVT();
            }

            $median = $this->computeMedianArray($processedData);

            $result[] = [
                'okno'   => $okno->getMnemo(),
                'min'    => empty($processedData) ? 1 : min($processedData),
                'max'    => empty($processedData) ? 1 : max($processedData),
                'stred'  => $median,
                'vals'   => $processedData,
                'labels' => $labels,
                'median' => array_fill(0, count($processedData), $median),
                'ciel'   => array_fill(0, count($processedData), $okno->getCiel()),
            ];
        }

        return $result;
    }

    /**
     * @param array $arr
     *
     * @return int
     */
    private function computeMedianArray(array $arr): int
    {
        if ($arr) {
            $count = count($arr);
            sort($arr);
            $mid = (int)floor(($count - 1) / 2);

            $median = ($arr[$mid] + $arr[$mid + 1 - $count % 2]) / 2;

            return $median;
        }

        return 0;
    }
}
