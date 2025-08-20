<?php

namespace addContent;

require __DIR__ . '/../Core/AutoLoader.php';
require __DIR__ . '/../config.php';

use Entity\Video;
use Entity\Movie;
use Entity\Content;
use Entity\Category;
use Entity\Serie;
use Entity\Episode;
use Services\Back\VideoManagerService;

class ContentProcessor
{
    private array $env;

    public function __construct()
    {
        $this->loadEnvironment();
    }

    /**
     * Charge les variables d'environnement depuis le fichier .env
     */
    private function loadEnvironment(): void
    {
        $envPath = __DIR__ . '/../.env';
        if (!file_exists($envPath)) {
            throw new \Exception("Le fichier .env n'existe pas : $envPath");
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $this->env[$key] = $value;
            $_ENV[$key] = $value;
        }
    }

    /**
     * Charge et parse le fichier JSON d'input
     */
    private function loadJsonFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("Fichier non trouvé : $path");
        }
        
        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    /**
     * Récupère les détails d'un contenu depuis l'API TMDB
     */
    private function getContentDetails(string $name, bool $isSerie): array
    {
        $apiKey = $this->env['MOVIE_BD_API_KEY'] ?? '';
        if (empty($apiKey)) {
            throw new \Exception("Clé API TMDB manquante");
        }

        $endpoint = $isSerie ? 'tv' : 'movie';
        $url = "https://api.themoviedb.org/3/search/{$endpoint}?api_key={$apiKey}&query={$name}&language=fr-FR";
        
        $response = $this->makeHttpRequest($url);
        $data = json_decode($response, true);

        if (!isset($data['results'][0])) {
            return [
                'title' => str_replace(['+', '-'], ' ', $name),
                'description' => 'Aucune description disponible',
                'genre' => Category::findFirst() ?? $this->createDefaultCategory(),
                'date' => date('Y-m-d H:i:s'),
                'image' => 'https://cdn.pixabay.com/photo/2017/04/09/12/45/error-2215702_1280.png'
            ];
        }

        $result = $data['results'][0];
        
        // Prioriser backdrop > poster > image par défaut
        $imageUrl = 'https://cdn.pixabay.com/photo/2017/04/09/12/45/error-2215702_1280.png';
        if (!empty($result['backdrop_path'])) {
            $imageUrl = "https://image.tmdb.org/t/p/w1280{$result['backdrop_path']}";
        } elseif (!empty($result['poster_path'])) {
            $imageUrl = "https://image.tmdb.org/t/p/w500{$result['poster_path']}";
        }
        
        return [
            'title' => $result['name'] ?? $result['title'] ?? str_replace(['+', '-'], ' ', $name),
            'description' => $result['overview'] ?? 'La description sera ajoutée prochainement',
            'genre' => $this->getOrCreateCategory($result['genre_ids'][0] ?? null),
            'date' => $result['first_air_date'] ?? $result['release_date'] ?? date('Y-m-d H:i:s'),
            'image' => $imageUrl
        ];
    }

    /**
     * Fait une requête HTTP
     */
    private function makeHttpRequest(string $url): string
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \Exception("Erreur lors de la requête HTTP vers : $url");
        }

        return $response;
    }

    /**
     * Récupère ou crée une catégorie basée sur l'ID de genre TMDB
     */
    private function getOrCreateCategory(?int $genreId): Category
    {
        if ($genreId === null) {
            return Category::findFirst() ?? $this->createDefaultCategory();
        }

        $genreMap = [
            10759 => "Action & Adventure",
            16 => "Animation",
            35 => "Comedy",
            80 => "Crime",
            99 => "Documentary",
            18 => "Drama",
            10751 => "Family",
            10762 => "Kids",
            9648 => "Mystery",
            10765 => "Sci-Fi & Fantasy",
            28 => "Action",
            12 => "Adventure",
            14 => "Fantasy",
            27 => "Horror",
            10749 => "Romance",
            878 => "Science Fiction"
        ];

        $genreName = $genreMap[$genreId] ?? 'Other';
        
        $category = Category::findFirst(['title' => $genreName]);
        if ($category === null) {
            $category = Category::insertOne(['title' => $genreName]);
        }

        return $category;
    }

    /**
     * Crée une catégorie par défaut
     */
    private function createDefaultCategory(): Category
    {
        return Category::insertOne(['title' => 'Other']);
    }

    /**
     * Parse le fichier JSON et extrait les informations de contenu
     */
    private function parseJsonContent(array $data): array
    {
        $contents = [];
        
        foreach ($data as $url => $sourceData) {
            if ($sourceData['status'] !== 'success') {
                continue;
            }

            // Extraction du nom et type depuis l'URL
            if (preg_match('/catalogue\/([^_]+)_.*\/([^\/]+)\/(saison(\d+)|film)\//', $url, $matches)) {
                $name = str_replace('-', ' ', $matches[1]);
                $isSerie = isset($matches[4]);
                $season = $isSerie ? (int)$matches[4] : null;

                $details = $this->getContentDetails($name, $isSerie);

                $sources = [
                    $sourceData['source1'] ?? [],
                    $sourceData['source2'] ?? [],
                    $sourceData['source3'] ?? []
                ];

                $contents[] = [
                    'name' => $details['title'],
                    'type' => $isSerie ? 'serie' : 'movie',
                    'season' => $season,
                    'sources' => $sources,
                    'description' => $details['description'],
                    'date' => $details['date'],
                    'category' => $details['genre'],
                    'image' => $details['image']
                ];
            }
        }

        return $contents;
    }

    /**
     * Sauvegarde un film en base de données
     */
    private function saveMovie(array $contentData): void
    {
        echo "Ajout du film : {$contentData['name']}\n";

        $video = Video::insertOne([]);
        
        $movie = Movie::insertOne([
            'title' => $contentData['name'],
            'description' => $contentData['description'],
            'video_id' => $video->getId(),
            'image' => $contentData['image'],
            'release_date' => $contentData['date']
        ]);

        Content::insertOne([
            'value_id' => $movie->getId(),
            'value_type' => 'M',
            'category_id' => $contentData['category']->getId()
        ]);

        $this->saveSources($video->getId(), $contentData['sources']);
    }

    /**
     * Sauvegarde une série en base de données
     */
    private function saveSerie(array $contentData): void
    {
        echo "Ajout de la série : {$contentData['name']}\n";

        // Vérifier si la série existe déjà
        $existingSerie = Serie::findFirst([
            'title' => $contentData['name'],
            'image' => $contentData['image']
        ]);

        if ($existingSerie !== null) {
            $serie = $existingSerie;
        } else {
            $serie = Serie::insertOne([
                'title' => $contentData['name'],
                'description' => $contentData['description'],
                'image' => $contentData['image'],
                'release_date' => $contentData['date']
            ]);

            Content::insertOne([
                'value_id' => $serie->getId(),
                'value_type' => 'S',
                'category_id' => $contentData['category']->getId()
            ]);
        }

        $this->saveEpisodes($serie, $contentData['season'], $contentData['sources']);
    }

    /**
     * Sauvegarde les épisodes d'une série
     */
    private function saveEpisodes(Serie $serie, int $season, array $sources): void
    {
        $episodeSources = $this->transformSourcesForEpisodes($sources);
        
        foreach ($episodeSources as $episodeNumber => $episodeSources) {
            $video = Video::insertOne([]);
            
            Episode::insertOne([
                'episode' => $episodeNumber + 1,
                'season' => $season,
                'video_id' => $video->getId(),
                'serie_id' => $serie->getId(),
                'title' => "Episode " . ($episodeNumber + 1) . " - " . $serie->getTitle(),
                'description' => $serie->getDescription()
            ]);

            $this->saveSources($video->getId(), [$episodeSources]);
        }
    }

    /**
     * Transforme les sources pour les épisodes
     */
    private function transformSourcesForEpisodes(array $sources): array
    {
        $episodeSources = [];
        $maxEpisodes = 0;

        // Trouver le nombre maximum d'épisodes
        foreach ($sources as $source) {
            $maxEpisodes = max($maxEpisodes, count($source));
        }

        // Réorganiser par épisode
        for ($i = 0; $i < $maxEpisodes; $i++) {
            $episodeSources[$i] = [];
            foreach ($sources as $source) {
                if (isset($source[$i]) && !empty($source[$i])) {
                    $episodeSources[$i][] = $source[$i];
                }
            }
        }

        return $episodeSources;
    }

    /**
     * Sauvegarde les sources (URLs) pour une vidéo
     */
    private function saveSources(int $videoId, array $sources): void
    {
        foreach ($sources as $sourceList) {
            foreach ($sourceList as $url) {
                if (!empty($url)) {
                    VideoManagerService::createUrlWhereVideo($videoId, $url);
                }
            }
        }
    }

    /**
     * Traite le fichier JSON et sauvegarde tout en base
     */
    public function processFile(string $jsonPath): void
    {
        echo "Début du traitement du fichier : $jsonPath\n";

        $jsonData = $this->loadJsonFile($jsonPath);
        $contents = $this->parseJsonContent($jsonData);

        echo "Nombre de contenus trouvés : " . count($contents) . "\n";

        print_r($contents);

        sleep(10);

        foreach ($contents as $content) {
            try {
                if ($content['type'] === 'movie') {
                    $this->saveMovie($content);
                } elseif ($content['type'] === 'serie') {
                    $this->saveSerie($content);
                }
            } catch (\Exception $e) {
                echo "Erreur lors du traitement de {$content['name']} : " . $e->getMessage() . "\n";
            }
        }

        echo "Traitement terminé !\n";
    }
}

// Exécution du script
function main(): void
{
    try {
        $processor = new ContentProcessor();
        $processor->processFile(__DIR__ . '/src/list.json');
    } catch (\Exception $e) {
        echo "Erreur fatale : " . $e->getMessage() . "\n";
    }
}

main();