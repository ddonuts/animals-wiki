<?php

class ItisDbClient
{
    private $mysqlConnection;

    public function __construct($mysqlConnection)
    {
        $this->mysqlConnection = $mysqlConnection;
    }

    public function findHierachyFromNames(array $names): array
    {
        foreach ($names as $name) {
            $tsn = $this->findTsnFromName($name);
            if ($tsn) {
                $itisHierarchy = $this->findHierachyFromTsn($tsn);
                $this->findChildrenFromTsn($tsn);
                if ($itisHierarchy) {
                    return $itisHierarchy;
                }
            }
        }
        return null;
    }

    public function findChildrenFromTsn(string $tsn): array
    {
        $result = $this->mysqlConnection->getQueryResults('SELECT * FROM `longnames` WHERE `tsn` IN (SELECT `TSN` FROM `hierarchy` WHERE `Parent_TSN` = \'' . $tsn . '\')', 'tsn');

        return array_map(fn($line) => $line['completename'], $result);
    }

    //TSN: Taxonomic Serial Number
    private function findTsnFromName(string $name): string 
    {
        $tsn = '';
        $commonName = '';
        $latinName = '';
        /*$result = $this->mysqlConnection->getQueryResults('SELECT * FROM `vernaculars` WHERE `vernacular_name` LIKE \'%' . $name . '\'', 'tsn');
        if ($result) {
            $bestLine = current($result);
            $tsn = $bestLine['tsn'];
            $commonName = $bestLine['vernacular_name'];
        } else*/ {
            $result = $this->mysqlConnection->getQueryResults('SELECT * FROM `longnames` WHERE `completename` LIKE \'' . $name . '%\'', 'tsn');
            if ($result) {
                $bestLine = current($result);
                $tsn = $bestLine['tsn'];
                //$latinName = $bestLine['vernacular_name'];
            }
        }
        return $tsn;
    }

    private function findHierachyFromTsn(string $tsn): array
    {
        $result = $this->mysqlConnection->getQueryResults('SELECT * FROM `hierarchy` WHERE `TSN` = \'' . $tsn . '\'', 'TSN');

        $hierarchyTsns = $result ? explode('-', current($result)['hierarchy_string']) : [];

        $result = $this->mysqlConnection->getQueryResults('SELECT * FROM `longnames` WHERE `tsn` IN (\'' . implode('\', \'', $hierarchyTsns) . '\')', 'tsn');

        /* reorder */
        $hierarchy = [];
        foreach ($hierarchyTsns as $tsn) {
            $hierarchy[$tsn] = $result[$tsn]['completename'];
        }
        return $hierarchy;

        /*return array_map(function($line) {
            return $line['completename'];
        }, $result);*/
    }

    public function findChildren(string $tsn): array
    {

    }
}
