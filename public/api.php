<?php

include '../src/MysqlConnection.php';
include '../src/WikipediaApiClient.php';
include '../src/ItisDbClient.php';
include '../src/HierarchyClassification.php';

//print_r($_ENV); die();

$mysqlConnection = new MysqlConnection($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
$wikipediaApiClient = new WikipediaApiClient('fr');
$itisDbClient = new ItisDbClient($mysqlConnection);

if (!isset($_GET['name'])) {
    generateErrorResponse('Please provide a "name" query parameter.');
}
$name = $_GET['name'];

try {
    $info = $wikipediaApiClient->getPageInfo($name);

    if ($info) {
        $classificationNames = array_map(fn(array $classificationLine) => $classificationLine['name'], $info['classification']);
        
        $itisHierarchy = $itisDbClient->findHierachyFromNames(array_merge([$info['latinName']], array_reverse($classificationNames)));
        $hierarchyClassification = $itisHierarchy
            ? HierarchyClassification::generateFromItisHierarchy($itisHierarchy)
            : HierarchyClassification::generateFromWikipediaClassification($info['classification'])
        ;

        $subSpecies = [];
        if ($itisHierarchy && ($info['latinName'] === end($itisHierarchy))) {
            $subSpecies = $itisDbClient->findChildrenFromTsn(array_key_last($itisHierarchy));
        }
        
        generateResultResponse($name, true, [
            'latinName' => $info['latinName'],
            'imageUrl' => $info['imageUrl'],
            'description' => $info['description'],
            'hierarchy' => array_values($hierarchyClassification),
            'subSpecies' => array_values($subSpecies),
            'wikipediaPageUrl' => $info['pageUrl'],
        ]);
    } else {
        $choices = $wikipediaApiClient->getDisambiguationChoices($name);

        generateResultResponse($name, false, [
            'choices' => $choices,
        ]);
    }
} catch (\Exception $exception) {
    generateErrorResponse($exception->getMessage());
}

function generateResultResponse(string $name, bool $found, array $data): void
{
    generateResponse([
        'name' => $name,
        'found' => $found,
    ] + $data);
}

function generateErrorResponse(string $errorMessage): void
{
    generateResponse([
        'error' => $errorMessage,
    ]);
}

function generateResponse(array $data): void
{
    header('Content-Type: application/json');
    die(json_encode($data, JSON_PRETTY_PRINT));
}

