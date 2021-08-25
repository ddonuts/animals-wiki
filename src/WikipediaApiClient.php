<?php

//namespace App;

class WikipediaApiClient
{
    const PAGE_URL_TEMPLATE = 'https://fr.wikipedia.org/wiki/{name}';
    const API_URL = 'https://fr.wikipedia.org/w/api.php?{queryParams}';

    private $lang;
    private $cachedPages;

    public function __construct(string $lang)
    {
        $this->lang = $lang;
        $this->cachedPages = [];
    }

    public function getPageInfo(string $name): ?array
    {
        $pageContent = $this->getPageContent($name);
        
        if (!$pageContent || (strpos($pageContent['content'], 'taxobox_v3') === false)) {
            //TODO: HANDLE case like https://fr.wikipedia.org/wiki/Triton_(amphibien)
            //die('SHIT. CANNOT FIND TAXOBOX v3');
            return null;
        }
        $titlePart = '<div "' . self::getStringBetween($pageContent['content'], 'taxobox_v3', '</div>');

        $classificationTableHtml = '<table class="taxobox_classification">' . self::getStringBetween($pageContent['content'], 'class="taxobox_classification">', '</table>') . '</table>';

        $imagePart = '<div "' . self::getStringBetween($pageContent['content'], 'taxobox_v3', 'srcset=');

        $offset = strpos($pageContent['content'], $classificationTableHtml);
        $descriptionHtml = self::getStringBetween($pageContent['content'], '<p>', '</p>', $offset);
        
        return [
            'latinName' => trim(strip_tags($titlePart)),
            'classification' => self::parseClassificationHtml($classificationTableHtml),
            'description' => self::cleanDescriptionHtml($descriptionHtml),
            'imageUrl' => self::getStringBetween($imagePart, 'src="', '"'),
            'pageUrl' => $pageContent['url'],
        ];
    }

    public function getPageSummary(string $name): string
    {
        $content = self::downloadFile(self::API_URL, [ '{queryParams}' => http_build_query([
            'format' => 'xml',
            'action' => 'query',
            'prop' => 'extracts',
            'exintro' => null,
            'explaintext' => null,
            'redirects' => 1,
            'titles' => $name,
        ])]);

        $parsedContent = json_decode($content, true);

        return isset($parsedContent['query']['pages']) ? current($parsedContent['query']['pages'])['extract'] : null;
    }

    public function getDisambiguationChoices(string $name): array
    {
        $content = self::downloadFile(self::API_URL, [ '{queryParams}' => http_build_query([
            'format' => 'xml',
            'action' => 'opensearch',
            'limit' => 10,
            'suggest' => false,
            'search' => $name,
            //'srsearch' => $name . '+incategory:Article_avec_taxobox-animal',
        ])]);
        $xmlElement = new SimpleXMLElement($content['content']);
        $choices = [];
        foreach ($xmlElement->Section->Item as $item) {
            if ((stripos($item->Description, 'peut désigner') === false) && (stripos($item->Description, 'ambigu') === false) && (strcasecmp($item->Text, $name) != 0)) {
                $choices[] = [
                    'name' => "$item->Text",
                    //'description' => "$item->Description",
                    'imageUrl' => (string)@$item->Image->attributes()->source,
                    'pageUrl' => "$item->Url",
                ];
            }
        }
        return $choices;
    }

    static private function parseClassificationHtml(string $classificationTableHtml): array
    {
        $xmlElement = new SimpleXMLElement($classificationTableHtml);
        $classification = [];
        foreach ($xmlElement->tbody->tr as $tableLine) {
            $classificationType = (string)$tableLine->th->a;
            $name = (string)($tableLine->td->span ? $tableLine->td->span->a : $tableLine->td->a);
            if ($name) {
                $path = $tableLine->td->span->a ? (string)$tableLine->td->span->a->attributes()->href : '';
                $classification[$classificationType] = [
                    'name' => $name,
                    'url' => $path ? 'https://fr.wikipedia.org' . $path : '',
                ];
            }
        }
        return $classification;
    }

    static private function cleanDescriptionHtml(string $descriptionHtml): string
    {
        return strip_tags($descriptionHtml);
    }

    private function getPageContent(string $name): array
    {
        if (!isset($this->cachedPages[$name])) {
            $this->cachedPages[$name] = self::downloadFile(self::PAGE_URL_TEMPLATE, [ '{name}' => ucfirst(str_replace(' ', '_', $name)) ]);
        }
        return $this->cachedPages[$name];
    }

    private static function getStringBetween(string $string, string $startText, string $endText, int $offset = 0): string
    {
        $string = ' ' . $string;
        $ini = strpos($string, $startText, $offset);
        if ($ini == 0) return '';
        $ini += strlen($startText);
        $len = strpos($string, $endText, $ini) - $ini;

        return substr($string, $ini, $len);
    }

    private static function downloadFile(string $urlPattern, array $replacements): array
    {
        //die(strtr($urlPattern, $replacements));
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, strtr($urlPattern, $replacements));  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        $content = curl_exec($curl);
        $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        
        if (!$content) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception(sprintf('Could not read content from URL "%s": %s', $url, $error));   
        }
        curl_close($curl);

        return [
            'url' => $url,
            'content' => $content,
        ];
    }
}
