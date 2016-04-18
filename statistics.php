<?php

class Statistics
{

    // so missing functions... :))
    function goBack($daysBack, $organizationType)
    {
        $red = DB::queryFirstField("SELECT count(*) FROM scans_ssllabs
                                    LEFT OUTER JOIN scans_ssllabs as t2 ON (
                                    scans_ssllabs.url = t2.url
                                    AND scans_ssllabs.ipadres = t2.ipadres
                                    AND scans_ssllabs.poort = t2.poort
                                    AND t2.scanmoment > scans_ssllabs.scanmoment
                                    AND t2.scanmoment <= DATE_ADD(now(), INTERVAL -%i DAY))
                                    INNER JOIN url ON scans_ssllabs.url = url.url
                                    INNER JOIN organization ON url.organization = organization.name
                                    WHERE
                                    t2.url IS NULL
                                    AND scans_ssllabs.scanmoment <= DATE_ADD(now(), INTERVAL -%i DAY)
                                    AND scans_ssllabs.rating IN ('F','D','T')
                                    AND url.isDead = 0
                                    AND scans_ssllabs.isDead = 0
                                    AND organization.type = %s", $daysBack, $daysBack, $organizationType);
        $orange = DB::queryFirstField("SELECT count(*) FROM scans_ssllabs
                                    LEFT OUTER JOIN scans_ssllabs as t2 ON (
                                    scans_ssllabs.url = t2.url
                                    AND scans_ssllabs.ipadres = t2.ipadres
                                    AND scans_ssllabs.poort = t2.poort
                                    AND t2.scanmoment > scans_ssllabs.scanmoment
                                    AND t2.scanmoment <= DATE_ADD(now(), INTERVAL -%i DAY))
                                    INNER JOIN url ON scans_ssllabs.url = url.url
                                    INNER JOIN organization ON url.organization = organization.name
                                    WHERE
                                    t2.url IS NULL
                                    AND scans_ssllabs.scanmoment <= DATE_ADD(now(), INTERVAL -%i DAY)
                                    AND scans_ssllabs.rating IN ('C','B')
                                    AND url.isDead = 0
                                    AND scans_ssllabs.isDead = 0
                                    AND organization.type = %s", $daysBack, $daysBack, $organizationType);
        $green = DB::queryFirstField("SELECT count(*) FROM scans_ssllabs
                                    LEFT OUTER JOIN scans_ssllabs as t2 ON (
                                    scans_ssllabs.url = t2.url
                                    AND scans_ssllabs.ipadres = t2.ipadres
                                    AND scans_ssllabs.poort = t2.poort
                                    AND t2.scanmoment > scans_ssllabs.scanmoment
                                    AND t2.scanmoment <= DATE_ADD(now(), INTERVAL -%i DAY))
                                    INNER JOIN url ON scans_ssllabs.url = url.url
                                    INNER JOIN organization ON url.organization = organization.name
                                    WHERE
                                    t2.url IS NULL
                                    AND scans_ssllabs.scanmoment <= DATE_ADD(now(), INTERVAL -%i DAY)
                                    AND scans_ssllabs.rating IN ('A','A-','A+','A++')
                                    AND url.isDead = 0
                                    AND scans_ssllabs.isDead = 0
                                    AND organization.type = %s", $daysBack, $daysBack, $organizationType);

        return array('red' => $red, 'orange' => $orange, 'green' => $green, 'total' => ($red+$orange+$green));
    }
}