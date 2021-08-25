<?php

class HierarchyClassification
{
    const LINES = [
        'Règne', //Animalia
        'Sous-règne', //Bilateria
        'Infra-règne', //Deuterostomia
        'Embranchement', //Chordata
        'Sous-embr.', //Vertebrea
        'Infra-embr.', //Gnathostomata
        'Super classe', //Tetrapoda
        'Classe', //Mammalia
        'Sous-classe', //Theria
        'Infra-classe', //Eutheria
        'Ordre', //Carnivora
        'Sous-ordre', //Caniformia
        'Famille', //Ursidae
        'Genre', //Ailuporda
        'Tribe',
    ];

    public static function generateFromItisHierarchy(array $hierarchy): array
    {
        $num = min(count(self::LINES), count($hierarchy));
        return array_combine(array_slice(self::LINES, 0, $num), array_slice($hierarchy, 0, $num));
    }

    public static function generateFromWikipediaClassification(array $classification): array
    {
        return array_map(function(string $classificationType) use ($classification) {
            return isset($classification[$classificationType]) ? $classification[$classificationType]['name'] : '';
        }, self::LINES);
    }
}
