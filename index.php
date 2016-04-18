<?php
require_once("meekrodb.2.3.class.php");
require_once('../configuration.php');
require_once('statistics.php');

// some helper-functions that should be put in classes...
// no exception on rounding, so if total is unknown or 0, just return 0.
function percentage($number, $total){
    if (!$total) return 0;
    return round(($number / $total) * 100, 0);
}

$ratingColors = array("0" => "000000", "F" => "ff0000", "T" => "ff0000", "D" => "ff0000",  "C" => "FFA500",  "B" => "FFA500", "A-" => "00ff00", "A" => "00ff00","A+" => "00ff00","A++" => "00ff00");
function getRatingColor($rating){
    global $ratingColors; // saves re-initializing the same list. But this should be a class thing. Global is just a sign for disgusting code.
    if (isset($ratingColors[$rating])){
        return $ratingColors[$rating];
    } else {
        return "AAAAAA";
    }
}

function makeHTMLId($text){
    return preg_replace("/[^a-zA-Z]+/", "", $text);
}

?><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Faalkaart, geeft inzicht in beveiligde verbindingen van gemeentes.</title>
    <link rel="stylesheet" type="text/css" href="css/tooltipster.css" />
    <link rel="stylesheet" type="text/css" href="css/themes/tooltipster-light.css" />

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>


    <script src="scripts/jquery.maphilight.min.js"></script>
    <script type="text/javascript" src="scripts/jquery.tooltipster.min.js"></script>

    <?php
        if (isset($refreshTimer) and ($refreshTimer > 0)){
            print "<meta http-equiv=\"refresh\" content=\"".$refreshTimer."\">";
        }
    ?>

    <script type="text/javascript">$(function() {

        $.fn.maphilight.defaults = {
            fill: true,
            fillColor: '000000',
            fillOpacity: 0.4,
            stroke: true,
            strokeColor: 'ffffff',
            strokeOpacity: 1,
            strokeWidth: 1,
            fade: true,
            alwaysOn: true,
            neverOn: false,
            groupBy: false,
            wrapClass: true,
            shadow: false,
            shadowX: 0,
            shadowY: 0,
            shadowRadius: 6,
            shadowColor: '000000',
            shadowOpacity: 0.8,
            shadowPosition: 'outside',
            shadowFrom: false
        }
        $('.map').maphilight();
    });
        $(document).ready(function() {
            $('.tooltip').tooltipster({
                theme: 'tooltipster-light'
            });
            <?php

            /* Should look like:
            $('#my-tooltip').tooltipster({
                content: $('<span><img src="my-image.png" /> <strong>This text is in bold case !</strong></span>')
            });
            */

            $previousUrl = ""; $i=0;

            // ssllabs can discover multiple endpoints per domain. There can be multiple IP-address on both IPv4 and IPv6.
            $sql = "SELECT 
                          organization, 
                          url.url as theurl, 
                          scans_ssllabs.ipadres, 
                          scans_ssllabs.servernaam,
                          scans_ssllabs.poort,
                          scans_ssllabs.scandate, 
                          scans_ssllabs.scantime, 
                          scans_ssllabs.rating 
                        FROM `url` left outer join scans_ssllabs ON url.url = scans_ssllabs.url 
                        LEFT OUTER JOIN scans_ssllabs as t2 ON (
                          scans_ssllabs.url = t2.url
                          AND scans_ssllabs.ipadres = t2.ipadres
                          AND scans_ssllabs.poort = t2.poort
                          AND t2.scanmoment > scans_ssllabs.scanmoment  
                          AND t2.scanmoment <= NOW()) 
                        WHERE t2.url IS NULL 
                          AND organization <> '' 
                          AND scans_ssllabs.scanmoment <= now() 
                          AND url.isDead = 0
                          AND scans_ssllabs.isDead = 0
                        order by organization ASC";

                $results = DB::query($sql);
                foreach ($results as $row) {

                    if ($previousUrl != $row['theurl']) {
                        if ($i!=0){print "</span>')}); \n ";}

                       print "$('#".makeHTMLId($row['theurl'])."').tooltipster({ animation: 'fade', interactive: 'true', theme: 'tooltipster-light', content: $('<span>";
                    }

                    if ($row['ipadres']) {
                        if ($row['rating'] === '0')
                            $unknown = "Geen beveiligde verbinding gevonden.<br /><br />Dit komt vaak doordat:<br /><ul><li>er gekozen is publieke informatie zo benaderbaar mogelijk te maken,</li><li>er gebruik wordt gemaakt van filtering,</li><li>er geen dienst (meer) draait,</li><li>een ander poortnummer wordt gebruikt dan 443.</li></ul><br />";
                        else
                            $unknown = "";

                        $colorOordeel = getRatingColor($row['rating']);
                        print $unknown."<span style=\"color: #".$colorOordeel."\">Domein: ".$row['theurl']."<br />Adres: ".$row['ipadres'].":".$row['poort']."<br /></span>Reverse name: ".$row['servernaam']."<br />Scantijd: ".$row['scandate']." ".$row['scantime']." <br /><br /><a href=\"https://www.ssllabs.com/ssltest/analyze.html?d=".$row['theurl']."&hideResults=on&latest\" target=\"_blank\">Second opinion</a><br /><br />";
                    } else {
                        print "Geen informatie gevonden. Dit domein wordt mogelijk niet gebruikt of moet nog worden niet getest.";
                    }

                    $previousUrl = $row['theurl'];
                    $i++;
                }
                print "</span>')}); \n ";
            ?>
        });

    </script>

<!-- Piwik -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//faalkaart.nl/kiwip/";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', 1]);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript><p><img src="//faalkaart.nl/kiwip/piwik.php?idsite=1" style="border:0;" alt="" /></p></noscript>
<!-- End Piwik Code -->
</head>

<body role="document">

<nav class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Faalkaart</a>
        </div>
        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="active"><a href="#">Home</a></li>
                <li><a href="#kaart">Kaart</a></li>
                <li><a href="#balk">Balk</a></li>
                <li><a href="#cijfers">Cijfers</a></li>
                <li><a href="#domeinen">Domeinen</a></li>
                <li><a href="#uitleg">Uitleg</a></li>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</nav>

    <div class="container theme-showcase" role="main">

        <div class="jumbotron">
            <h1>Faalkaart</h1>
         <p>Faalkaart geeft inzicht in hoe veilig uw gemeente is richting het internet. Er wordt gekeken hoe veilig de gemeente haar verbindingen heeft ingericht. Het is belangrijk dat dit goed 
gebeurt omdat hierover ook uw gegevens worden verstuurd.</p>
            <p>Stuur nieuwe subdomeinen in via twitter: <a href="https://twitter.com/faalkaart">@faalkaart</a> of mail <a href="mailto:info@faalkaart.nl?subject=subdomeinen">info@faalkaart.nl</a></p>
         <p><small>Update 8 april 2016: Het aantal domeinen met een onvoldoende is gezakt naar 2%, was ooit 8%. Er zijn zojuist 1200 domeinen toegevoegd. Er is een team aan het ontstaan dat de faalkaart verder gaat uitbreiden en onderhouden. Vele handen maken licht werk. Dank aan gemeenten voor het insturen van subdomeinen. Dit is altijd welkom!</small></p>
         <!-- <p><small>Update 25 maart 2016: De kaart wordt automatisch ververst. Onder de uitleg staat een overzicht met domeinen die onvoldoende scoren.</small></p>-->
         <!-- <p><small>Update 18 maart 2016: De kaart wordt zeer binnenkort automatisch bijgewerkt. Nieuw zijn statistieken met historie. De domeinenlijst is verbeterd en er is tekst toegevoegd over de totstandkoming van het cijfer. Binnenkort ook open source.</small></p>
         <!-- <p><small>Update 16 maart 2016: De eerste serie van 1800 domeinen is geladen, dit wordt nog aangevuld en zal binnenkort opnieuw worden gecontroleerd. De testdatum is nu zichtbaar. De eerste verbeteringen schijnen een half uur na presentatie al te zijn doorgevoerd. Dat is stoer!</small></p>-->
        </div>

        <div class="page-header">
            <a name="kaart"></a>
            <h1>De Kaart</h1>
            <p>Deze kaart is te lezen als een stoplicht. Iedere gemeente is weergegeven als een kleur. Rood betekent onvoldoende, groen betekent voldoende. Nuances daargelaten, zie onderaan.</p>
        </div>
        <center>
            <img class="map" name="NLGem" alt="NLGem" align="absmiddle" hspace="0" vspace="0" src="./images/kaart2.png" width="770" usemap="#NLGem" border="0">
        <map name="NLGem" id="NLGem">
        <?php
            // some municipalities have more than one area... you have to draw all of them and still have the right color :) FUN!
            // grouping by area, because no area is unique... this feels like a disgusting hack.
            $sql = "SELECT 
                          url.organization as organization, 
                          area,
                          max(scans_ssllabs.rating) as rating
                        FROM `url` 
                        left outer join scans_ssllabs ON url.url = scans_ssllabs.url
                        left outer join organization ON url.organization = organization.name
                        inner join coordinate ON coordinate.organization = organization.name
                        LEFT OUTER JOIN scans_ssllabs as t2 ON (
                          scans_ssllabs.url = t2.url
                          AND scans_ssllabs.ipadres = t2.ipadres
                          AND scans_ssllabs.poort = t2.poort
                          AND t2.scanmoment > scans_ssllabs.scanmoment  
                          AND t2.scanmoment <= DATE_ADD(now(), INTERVAL -0 DAY)) 
                        WHERE t2.url IS NULL 
                          AND url.organization <> '' 
                          AND scans_ssllabs.scanmoment <= DATE_ADD(now(), INTERVAL -0 DAY) 
                          AND url.isDead = 0
                          AND scans_ssllabs.isDead = 0
                        group by (area) 
                        order by url.organization ASC, rating DESC";
            $results = DB::query($sql);
            foreach ($results as $row) {
                $colorOordeel = getRatingColor($row['rating']);
                print "<area data-maphilight='{\"fillColor\":\"".$colorOordeel."\"}' shape=\"Poly\" title=\"".$row['organization']."\" href=\"#".$row['organization']."\" coords=\"".$row['area']."\"  >\n";
            }
        ?>
        </map>
        </center>

        <div class="page-header">
            <a name="balk"></a>
            <h1>De Balk</h1>
            <p>Deze balk geeft in percentages aan hoe het er voor staat. Het kan zijn dat en gemeente 1 oranje en 20 groene verbindingen heeft. Dat is beter zichtbaar in deze balk.</p>
        </div>

        <?php
            $Stats = new Statistics();
            $results = $Stats->goBack(0,'municipality');

            $red = $results['red'];
            $orange = $results['orange'];
            $green = $results['green'];
            $total = $results['total'];

            $total = $red + $green + $orange;

            $progressRed = floor(($red/$total)*100);
            $progressOrange = floor(($orange/$total)*100);
            $progressGreen = floor(($green/$total)*100);

            // due to rounding we might miss a little... so fill it up on the positive side... we are so generous.
            if ($progressRed+$progressOrange+$progressGreen<100) $progressGreen += 100 - ($progressRed+$progressOrange+$progressGreen);
        ?>

        <div class="progress">
            <div class="progress-bar progress-bar-success" style="width: <?php print $progressGreen;?>%"><span class="sr-only">35% Complete (success)</span></div>
            <div class="progress-bar progress-bar-warning" style="width: <?php print $progressOrange;?>%"><span class="sr-only">20% Complete (warning)</span></div>
            <div class="progress-bar progress-bar-danger" style="width: <?php print $progressRed;?>%"><span class='sr-only'>10% Complete (danger)</span></div>

            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                Dropdown
            </a>
        </div>


<?php

        $previousDay = $Stats->goBack(1,'municipality');
        $previousTwoDays = $Stats->goBack(2,'municipality');
        $previousWeek = $Stats->goBack(7,'municipality');
        $previousTwoWeeks = $Stats->goBack(14,'municipality');
        $previousMonth = $Stats->goBack(31,'municipality');
        $previousQuarter = $Stats->goBack(91,'municipality');
        $previousHalfYear = $Stats->goBack(182,'municipality');

	

?>
        <div class="page-header">
        <a name="cijfers"></a>
        <h1>De Cijfers</h1>
        <p><i>Let op: verwijderde/opgeruimde subdomeinen en endpoints staan niet in deze statistieken. Dit zijn er ongeveer 10, wat toeneemt over tijd.</i></p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Wanneer</th>
                        <th>Domeinen</th>
                        <th>Voldoende</th>
                        <th>Matig</th>
                        <th>Onvoldoende</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Laatste stand</td>
                        <td><?php print $total;?></td>
                        <td><?php print $green;?> <small>(<?php print percentage($green,$total);?>%)<small></td>
                        <td><?php print $orange;?> <small>(<?php print percentage($orange,$total);?>%)<small></td>
                        <td><?php print $red;?> <small>(<?php print percentage($red,$total);?>%)<small></td>
                    </tr>
                    <tr>
                        <td>Gisteren</td>
                        <td><?php print $previousDay['total'];?></td>
                        <td><?php print $previousDay['green'];?> <small>(<?php print percentage($previousDay['green'],$previousDay['total']);?>%)<small></td>
                        <td><?php print $previousDay['orange'];?> <small>(<?php print percentage($previousDay['orange'],$previousDay['total']);?>%)<small></td>
                        <td><?php print $previousDay['red'];?> <small>(<?php print percentage($previousDay['red'],$previousDay['total']);?>%)<small></td>
                    </tr>
                    <tr>
                        <td>Eergisteren</td>
                        <td><?php print $previousTwoDays['total'];?></td>
                        <td><?php print $previousTwoDays['green'];?> <small>(<?php print percentage($previousTwoDays['green'],$previousTwoDays['total']);?>%)<small></td>
                        <td><?php print $previousTwoDays['orange'];?> <small>(<?php print percentage($previousTwoDays['orange'],$previousTwoDays['total']);?>%)<small></td>
                        <td><?php print $previousTwoDays['red'];?> <small>(<?php print percentage($previousTwoDays['red'],$previousTwoDays['total']);?>%)<small></td>
                    </tr>
                    <tr>
                        <td>Week</td>
                        <td><?php print $previousWeek['total'];?></td>
                        <td><?php print $previousWeek['green'];?> <small>(<?php print percentage($previousWeek['green'],$previousWeek['total']);?>%)<small></td>
                        <td><?php print $previousWeek['orange'];?> <small>(<?php print percentage($previousWeek['orange'],$previousWeek['total']);?>%)<small></td>
                        <td><?php print $previousWeek['red'];?> <small>(<?php print percentage($previousWeek['red'],$previousWeek['total']);?>%)<small></td>
                    </tr>
                    <tr>
                        <td>Maand</td>
                        <td><?php print $previousMonth['total'];?></td>
                        <td><?php print $previousMonth['green'];?> <small>(<?php print percentage($previousMonth['green'],$previousMonth['total']);?>%)<small></td>
                        <td><?php print $previousMonth['orange'];?> <small>(<?php print percentage($previousMonth['orange'],$previousMonth['total']);?>%)<small></td>
                        <td><?php print $previousMonth['red'];?> <small>(<?php print percentage($previousMonth['red'],$previousMonth['total']);?>%)<small></td>
                    </tr>
                    <tr>
                        <td>Kwartaal</td>
                        <td><?php print $previousQuarter['total'];?></td>
                        <td><?php print $previousQuarter['green'];?> <small>(<?php print percentage($previousQuarter['green'],$previousQuarter['total']);?>%)<small></td>
                        <td><?php print $previousQuarter['orange'];?> <small>(<?php print percentage($previousQuarter['orange'],$previousQuarter['total']);?>%)<small></td>
                        <td><?php print $previousQuarter['red'];?> <small>(<?php print percentage($previousQuarter['red'],$previousQuarter['total']);?>%)<small></td>
                    </tr>
                    <tr>
                        <td>Half Jaar</td>
                        <td><?php print $previousHalfYear['total'];?></td>
                        <td><?php print $previousHalfYear['green'];?> <small>(<?php print percentage($previousHalfYear['green'],$previousHalfYear['total']);?>%)<small></td>
                        <td><?php print $previousHalfYear['orange'];?> <small>(<?php print percentage($previousHalfYear['orange'],$previousHalfYear['total']);?>%)<small></td>
                        <td><?php print $previousHalfYear['red'];?> <small>(<?php print percentage($previousHalfYear['red'],$previousHalfYear['total']);?>%)<small></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-6">
            <div class="page-header">
                <a name="domeinen"></a>
                <h1>Domeinen</h1>
            </div>
            <table class="table table-striped">
                <thead>
                <tr><th>Gemeente</th><th>Domeinen / oordeel</th></tr>
                </thead><tbody>
            <?php

            $gradeColors = array("0" => "000000", "F" => "ff0000", "T" => "ff0000", "D" => "ff0000",  "C" => "FFA500",  "B" => "FFA500", "A-" => "00ff00", "A" => "00ff00","A+" => "00ff00","A++" => "00ff00");

                $previousGemeente = ""; $previousUrl = "";
                $i=0; // should use a template engine :) which avoids setting these types of zany variables and status tracking.

                /**

                 We want a query that:
                 - Gives results to a certain date.
                 - Gives only the latest result from the entire set.

                 Vendor neutral and fast solution:
                 http://stackoverflow.com/questions/121387/fetch-the-row-which-has-the-max-value-for-a-column
                 
                 With this one we browse through time and get only one result per url :) Time is by default NOW()
                 
                 The queries selects the last scanned domain, which can have multiple endoints (N). 
                 In "the latest set" there is uniqueness on (url, ip, port). Group-concating or maxing those for the worst rating determines the color.
                 
                 */
                
                $sql = "SELECT 
                          organization, 
                          url.url as theurl, 
                          scans_ssllabs.ipadres, 
                          scans_ssllabs.servernaam, 
                          scans_ssllabs.scandate, 
                          scans_ssllabs.scantime, 
                          count(scans_ssllabs.rating) as endpointsfound,
                          max(scans_ssllabs.rating) as rating
                        FROM `url` left outer join scans_ssllabs ON url.url = scans_ssllabs.url 
                        LEFT OUTER JOIN scans_ssllabs as t2 ON (
                          scans_ssllabs.url = t2.url
                          AND scans_ssllabs.ipadres = t2.ipadres
                          AND scans_ssllabs.poort = t2.poort
                          AND t2.scanmoment > scans_ssllabs.scanmoment  
                          AND t2.scanmoment <= now()) 
                        WHERE t2.url IS NULL 
                          AND organization <> '' 
                          AND scans_ssllabs.scanmoment <= now() 
                          AND url.isDead = 0
                          AND scans_ssllabs.isDead = 0
                        group by (scans_ssllabs.url) 
                        order by organization ASC, rating DESC";
                $results = DB::query($sql);
                    foreach ($results as $row) {

                        if ($previousGemeente != $row['organization']){
                            // close the previous row.
                            if ($i!= 0) {
                                print "</td></tr>";
                            }

                            print "<tr><td><a name=\"".$row['organization']."\"></a>".$row['organization']."</td><td>";
                        }

                        if ($previousUrl != $row['theurl']){
                            if (isset($gradeColors[$row['rating']])){
                                $colorOordeel = $gradeColors[$row['rating']];
                            } else {
                                $colorOordeel = "AAAAAA";
                            }

                            // show a nice (2x) if there are multiple endpoints. Amsterdam has redundant endpoints on both ipv4 and ipv6.
                            if ($row['endpointsfound'] > 1) {
                                print "<div style='color: #" . $colorOordeel . "' id='" . makeHTMLId($row['theurl']) . "'>" . $row['theurl'] . " (" . $row['endpointsfound'] . "x)</div> ";
                            } else {
                                print "<div style='color: #" . $colorOordeel . "' id='" . makeHTMLId($row['theurl']) . "'>" . $row['theurl'] . "</div> ";
                            }
                        }
                        $previousGemeente = $row['organization'];
                        $previousUrl = $row['theurl'];
                    }

                print "</td></tr>";
                ?>
                </tbody>
            </table>
        </div>

        <div class="col-md-6">
            <div class="page-header">
                <a name="uitleg"></a>
                <h1>Over Faalkaart</h1>
            </div>

            <h2>Waarom Faalkaart?</h2>
            <p>Antwoord: Het is boeiend om te zien wat de status is van veiligheid van dataverbindingen van publieke diensten, daar wordt namelijk vaak gevoelige informatie verwerkt. De kaart heet Faalkaart omdat initieel werd aangenomen dat er nog veel te verbeteren viel en een tendentieuze naam eerder leidt tot actie dan een schattige naam. De naam bleek verkeerd gekozen.</p>

            <h2>Mijn gemeente is rood, wat nu?</h2>
            <p>Antwoord: Helaas zit er een gat in de muur: de gemeente zal actie moeten ondernemen om e.e.a. goed in te richten of een <a href="https://letsencrypt.org">nieuw certificaat te installeren</a>. Er wordt uitgegaan van de zwakste schakel: ergens een onvoldoende betekent een rode vlek op de kaart.</p>

            <h2>Mijn gemeente is groen, is alles goed?</h2>
            <p>Antwoord: De kans dat een subdomein mist, en juist daar een gat in de muur zit, is aanwezig. De vuistregel is dat meer subdomeinen meer zekerheid geven.</p>

            <h2>Hoe compleet is dit?</h2>
            <p>Antwoord: Er zijn meer dan 1800 domeinen getest van 350+ gemeentes. Er wordt getest op poort 443 (https). De geteste domeinen staan links op deze site vermeld. Er is alleen getest op *.gemeentenaam.tld, dus niet op doorverwijzingen. Sommige diensten zitten achter ip en certificaatfiltering en zijn dus niet testbaar zonder de juiste voorwaarden. Sommige gemeentes accepteren DNS wildcards, hiervan zijn alleen www.~ en ~ getest: dit is dus incompleet en kan voor deze gemeentes de schijn wekken dat alles in orde is. De kaartdata en gemeente-websites komen uit 2014 en het is mogelijk dat er dus wat mist door o.a. het samengaan van gemeentes.</p>

            <h2>Hoe komt de score tot stand?</h2>
            <p>Antwoord: Er wordt verbinding gemaakt op poort 443. Als er een verbinding is, en er is een beveiligde verbinding bedoeld, dan wordt gecontroleert hoe goed die verbinding is. Als er een beveiligde verbinding is, dan wordt deze geacht als nodig. Het is lastig om automatisch te bepalen of een domein wel of geen beveiligde verbinding vereist: publieke informatie moet ook bereikbaar zijn voor mensen die internetten via een aardappel. </p>

            <h2>Hoe moet SSL/TLS worden ingericht?</h2>
            <p>Antwoord: Er zijn een <a href="https://www.google.nl/search?q=secure+tls+configuration&oq=secure+tls+configuration&aqs=chrome..69i57.6276j0j1&sourceid=chrome&es_sm=93&ie=UTF-8">aantal goede handleidingen te vinden</a>. Toonaangevend advies komt van het <a href="https://www.ncsc.nl/actueel/whitepapers/ict-beveiligingsrichtlijnen-voor-transport-layer-security-tls.html">Nationaal Cyber Security Centrum</a>. Een variant toegespitst op gemeenten, die ook ingaat op DNSSEC, staat op de site van <a href="https://www.ibdgemeenten.nl/3619-2/" target="_blank">IBD Gemeenten</a>. Lijsten met goede instellingen zijn te vinden op <a href="https://cipherli.st">Cipher List</a>.</p>

            <h2>Sinds wanneer bestaat deze kaart?</h2>
            <p>Antwoord: De kaart is gepresenteerd door sprekers op de "<a href="http://inhethoofdvandehacker.nl/" target="_blank">in het hoofd van de hacker</a>" conferentie van 16 maart 2016. Een volledig programma van de conferentie is terug te zien op de site van de conferentie.</p>

            <h2>Hoe is dit tot stand gekomen?</h2>
            <p>Antwoord: Dit project is tot stand gekomen door:</p>
            <ul>
                <li>Programmeer, hak en breekwerk door Elger Jonker</li>
                <li>DNSSEC en nuttig ongevraagd advies: Eelko Neven</li>
                <li>Actuele lijst gemeenten: 200ok.nl</li>
                <li>Beoordeling op veiligheid door Qualys SSL labs</li>
                <li>URLs en polygonen van de klikbare gemeentekaart van Imergis. Kaartdata 2014.</li>
                <li>Simpele DNS verkenning met DNS Recon</li>
                <li>Styling: Twitter Bootstrap</li>
                <li>Talen: Python, PHP op MariaDB.</li>
                <li>JQuery MapHighlight door David Lynch</li>
            </ul>

            // todo: be warned, below is only a copy of the above table, with only the F-grades showing. So this is where functional decomposition should have started.
            <div class="page-header">
                <a name="takenlijst"></a>
                <h2>Wat scoort onvoldoende?</h2>
            </div>
            <table class="table table-striped">
                <thead>
                <tr><th>Gemeente</th><th>Domeinen / oordeel</th></tr>
                </thead><tbody>
                <?php

                $gradeColors = array("0" => "000000", "F" => "ff0000", "T" => "ff0000", "D" => "ff0000",  "C" => "FFA500",  "B" => "FFA500", "A-" => "00ff00", "A" => "00ff00","A+" => "00ff00","A++" => "00ff00");

                $previousGemeente = ""; $previousUrl = "";
                $i=0;


                $sql = "SELECT 
                          organization, 
                          url.url as theurl, 
                          scans_ssllabs.ipadres, 
                          scans_ssllabs.servernaam, 
                          scans_ssllabs.scandate, 
                          scans_ssllabs.scantime, 
                          min(scans_ssllabs.scanmoment) as scanmoment,
                          count(scans_ssllabs.rating) as endpointsfound,
                          max(scans_ssllabs.rating) as rating
                        FROM `url` left outer join scans_ssllabs ON url.url = scans_ssllabs.url 
                        LEFT OUTER JOIN scans_ssllabs as t2 ON (
                          scans_ssllabs.url = t2.url
                          AND scans_ssllabs.ipadres = t2.ipadres
                          AND scans_ssllabs.poort = t2.poort
                          AND t2.scanmoment > scans_ssllabs.scanmoment  
                          AND t2.scanmoment <= NOW()) 
                        WHERE t2.url IS NULL 
                          AND organization <> '' 
                          AND scans_ssllabs.scanmoment <= now() 
                          AND url.isDead = 0
                          AND scans_ssllabs.isDead = 0
                          AND scans_ssllabs.rating IN ('T','F')
                        group by (scans_ssllabs.url) 
                        order by organization ASC, rating DESC";
                $results = DB::query($sql);
                foreach ($results as $row) {

                    if ($previousGemeente != $row['organization']){

                        if ($i!= 0) {
                            print "</td></tr>";
                        }

                        print "<tr><td><a name=\"".$row['organization']."\"></a>".$row['organization']."</td><td>";
                    }

                    if ($previousUrl != $row['theurl']){
                        if (isset($gradeColors[$row['rating']])){
                            $colorOordeel = $gradeColors[$row['rating']];
                        } else {
                            $colorOordeel = "AAAAAA";
                        }

                        if ($row['endpointsfound'] > 1) {
                            print "<div style='color: #" . $colorOordeel . "' id='" . makeHTMLId($row['theurl']) . "'>" . $row['theurl'] . " (" . $row['endpointsfound'] . "x)</div><small>".$row['scanmoment']."</small>";
                        } else {
                            print "<div style='color: #" . $colorOordeel . "' id='" . makeHTMLId($row['theurl']) . "'>" . $row['theurl'] . "</div><small> ".$row['scanmoment']."</small>";
                        }
                    }
                    $previousGemeente = $row['organization'];
                    $previousUrl = $row['theurl'];
                }

                print "</td></tr>";
                ?>
                </tbody>
            </table>
        </div>


    </div>
</body>
</html>
