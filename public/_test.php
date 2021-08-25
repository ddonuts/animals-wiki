<?php

include '../src/MysqlConnection.php';
include '../vendor/wikidrain-master/includes/wikidrain.class.php';
include '../src/WikipediaApiClient.php';
include '../src/ItisDbClient.php';

$name1 = $_GET['name1'] ?? 'Koala';
$name2 = $_GET['name2'] ?? 'Panda';

/*$wikidrain = new wikidrain('animals_wiki', 'fr');
$results = $wikidrain->Search($name1, 10);
print_r($results);
$results = $wikidrain->getSections($name1);
print_r($results);
foreach ($results['sections']['s'] as $i => $section) {
    echo "\n\n---SECTION " . $section['line'] . "<br />\n";
    $results = $wikidrain->getText($name1, $i);
    print_r($results);
}
die();*/

$mysqlConnection = new MysqlConnection('127.0.0.1', 'root', 'root', 'animals_wiki');
$wikipediaApiClient = new WikipediaApiClient('fr');
$itisDbClient = new ItisDbClient($mysqlConnection);

$latinName1 = $wikipediaApiClient->getLatinName($name1);
$latinName2 = $wikipediaApiClient->getLatinName($name2);

if (!$latinName1) {
    die(getDisambiguationHtml($wikipediaApiClient, $name1));
}
if (!$latinName2) {
    die(getDisambiguationHtml($wikipediaApiClient, $name2));
}

function getDisambiguationHtml($wikipediaApiClient, $name)
{
    $choices = $wikipediaApiClient->getDisambiguationChoices($name);
    return ''
        . '<h2>' . $name . ' ?</h2>'
        . '<ul>'
            . implode('', array_map(function ($choice) {
                return '<li><a href="">' . $choice['name'] . '</a>: ' . $choice['description'] . '</li>';
            }, $choices))
        . '</ul>'
    ;
}

//print_r($wikipediaApiClient->getClassification($name1));
//print_r($wikipediaApiClient->getClassification($name2));


$tsn1 = $itisDbClient->findTsn($latinName1);
$tsn2 = $itisDbClient->findTsn($latinName2);

$hierarchy1 = $tsn1 ? $itisDbClient->findHierachy($tsn1) : null;
$hierarchy2 = $tsn2 ? $itisDbClient->findHierachy($tsn2) : null;

$commonAncestorLevel = 0;
foreach (array_keys($hierarchy1) as $i) {
    if ($hierarchy1[$i] == $hierarchy2[$i]) {
        $commonAncestorLevel++;
    } else {
        break;
    }
}

?>
<table>
    <tr>
        <td><?php echo $name1 ?></td>
        <td><?php echo $latinName1 ?> (<?php echo $tsn1 ?>)</td>
        <?php if ($hierarchy1) foreach ($hierarchy1 as $hierarchy): ?>
         <td><?php echo $hierarchy ?></td>
        <?php endforeach ?>
    </tr>
    <tr>
        <td><?php echo $name2 ?></td>
        <td><?php echo $latinName2 ?> (<?php echo $tsn2 ?>)</td>
        <?php if ($hierarchy2) foreach ($hierarchy2 as $hierarchy): ?>
         <td><?php echo $hierarchy ?></td>
        <?php endforeach ?>
    </tr>
</table>

Score = <?php echo $commonAncestorLevel ?>
