<?php

namespace App\Service;

use DateTimeInterface;

class DateService {
    /**
     * Retourne la période Tempo à laquelle partient le jour fourni.
     * Le résultat est un tableau de deux entiers, par exemple [2023, 2024].
     * Les périodes Tempo vont du 1er septembre au 31 août de l'année suivante.
     * 
     * @param DateTimeInterface $day Jour dont on souhaitre connaitre la période
     * @return array Tableau au format [2023, 2024]
     */
    public function getPeriodeOfDay(DateTimeInterface $day): array
    {
        if ($day->format('m-d') <= '08-31') {
            // Du 1er janvier au 31 août inclus: Saison = N-1 / N
            return [
                ((int) $day->format('Y')) - 1,
                (int) $day->format('Y')
            ];
        } else {
            // 1er septembre au 31 décembre inclus: Saison = N / N+1
            return [
                (int) $day->format('Y'),
                ((int) $day->format('Y')) + 1
            ];
        }
    }

    /**
     * Retourne la période Tempo à laquelle partient le jour fourni.
     * Le résultat est un tableau de deux entiers, par exemple [2023, 2024].
     * Les périodes Tempo vont du 1er septembre au 31 août de l'année suivante.
     * 
     * @param DateTimeInterface $day Jour dont on souhaitre connaitre la période
     * @return string Période au format 2023-2024
     */
    public function getPeriodeOfDayAsString(DateTimeInterface $day): string {
        $periode = $this->getPeriodeOfDay($day);
        return $periode[0] . '-' . $periode[1];
    }

    /**
     * Retourne si la période Tempo est bissextile.
     * 
     * @param string $libPeriode La période au format 2023-2024
     * @return bool True si la période est bissextile, false sinon
     */
    public function isPeriodeBissextile(String $libPeriode): bool {
        // Une période Tempo est bissextile seulement si la deuxième année de la période l'est
        $annee = (int) explode('-', $libPeriode)[1];
        return ($annee % 4 === 0 && $annee % 100 !== 0) || ($annee % 400 === 0);
    }
}