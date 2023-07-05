<?php

namespace Controller\API\ContentManager\MovieController;

use Core\Controller;
use Core\Request;
use Core\Response;

use Entity\Movie;
use Entity\Video;
use Services\Access\AccessContentsManager;
use Services\Back\VideoManagerService as VideoManager;

/**
 * @POST{/api/movie}
 * @apiName CreateMovie
 * @apiGroup ContentManager/MovieController
 * @apiVersion 1.0.0
 * @Feature ContentManager
 * @Description Create a movie
 * @param array url
 * @param string title_video
 * @param string description
 * @param string image
 * @param int category
 * @return Response
 */
/*
Entry:
{
"title_video": "Video title",
"description": "Video description",
"image": "https://www.image.com/image.png",
"category_id": 1
}
*/
class createMovie extends AccessContentsManager
{
    public function checkers(Request $request): array
    {
        return [
            ["type/string", $request->getBody()['title_video'], "title_video"],
            ["video/title", fn () => $this->floor->pickup("title_video"), "title_video"],
            ["type/string", $request->getBody()['description'], "description"],
            ["video/description", fn () => $this->floor->pickup("description"), "description"],
            ["type/string", $request->getBody()['image'], "image"],
            ["video/image", fn () => $this->floor->pickup("image"), "image"],
            ["type/string", $request->getBody()['category_name'], "category_name"],
            ["category/existByName", fn () => $this->floor->pickup("category_name"), "category"],
            ["type/string", $request->getBody()['release_date'], "release_date"]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        /** @var Video $video */
        $video = VideoManager::createVideo();
        if (empty($video)) {
            $response->code(500)->info("video.error")->send();
        }
        /** @var Movie $movie */
        $movie = Movie::insertOne([
            "title" => $this->floor->pickup("title_video"),
            "description" => $this->floor->pickup("description"),
            "video_id" => $video->getId(),
            "image" => $this->floor->pickup("image"),
            "category_id" => $this->floor->pickup("category")->getId(),
            "release_date" => $this->floor->pickup("release_date")
        ]);

        $response->code(201)->info("movie.created")->send($movie);
    }
}

/**
 * @DELETE{/api/movie/{id}}
 * @apiName DeleteMovie
 * @apiGroup ContentManager/MovieController
 * @apiVersion 1.0.0
 * @Feature ContentManager
 * @Description Delete a movie
 * @param int id
 * @return Response
 */
class deleteMovie extends AccessContentsManager
{
    public function checkers(Request $request): array
    {
        return [
            ["type/int", $request->getParam('id'), "movie_id"],
            ["movie/exist", fn () => $this->floor->pickup("movie_id"), "movie"]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        $this->floor->pickup("movie")->delete();
        $response->info("movie.deleted")->code(204)->send();
    }
}

/**
 * @GET{/api/movie/{id}}
 * @apiName GetMovie
 * @apiGroup ContentManager/MovieController
 * @apiVersion 1.0.0
 * @Feature ContentManager
 * @Description Get a movie
 * @param int id
 * @return Response
 */
class getMovie extends AccessContentsManager
{
    public function checkers(Request $request): array
    {
        return [
            ["type/int", $request->getParam('id'), "movie_id"],
            ["movie/exist", fn () => $this->floor->pickup("movie_id"), "movie"]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        /** @var Movie $movie */
        $movie = $this->floor->pickup("movie");

        $movieMapped = [
            "id" => $movie->getId(),
            "description" => $movie->getDescription(),
            "title" => $movie->getTitle(),
            "image" => $movie->getImage(),
            "category" => $movie->getCategory(),
            "video" => $movie->getVideo()
        ];

        $response->code(200)->info("movie.get")->send($movieMapped);
    }
}

/**
 * @GET{/api/movies}
 * @apiName GetMovies
 * @apiGroup ContentManager/MovieController
 * @apiVersion 1.0.0
 * @Feature ContentManager
 * @Description Get movies
 * @return Response
 */
class getMovies extends AccessContentsManager
{
    public function checkers(Request $request): array
    {
        return [
            ["type/int", $request->getQuery("page") ?? 0, "page"],
            ["type/string", $request->getQuery("title") ?? "", "title"]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        $page = $this->floor->pickup("page");
        $title = $this->floor->pickup("title");
        $number = 5;
        
        $movies = Movie::findMany(
            [
                "title" => [
                    "\$CTN" => $title
                ]
            ],
            ["ORDER_BY" => ["id"], "OFFSET" => $number * $page, "LIMIT" => $number]
        );
        
        Movie::groups("dateProps", "category");

        $response->code(200)->info("movies.get")->send($movies);
    }
}

/**
 * @GET{/api/movies/count}
 */
class getMoviesCount extends AccessContentsManager
{
    public function checkers(Request $request): array
    {
        return [];
    }

    public function handler(Request $request, Response $response): void
    {
        $response
            ->code(200)
            ->info("count")
            ->send(["count" => Movie::count()]);
    }
}

/**
 * @PUT{/api/movie/{id}}
 * @apiName UpdateMovie
 * @apiGroup ContentManager/MovieController
 * @apiVersion 1.0.0
 * @Feature ContentManager
 * @Description Update a movie
 * @param int id
 * @param array url
 * @param string title_video
 * @param string description
 * @param string image
 * @param int category
 * @return Response
 */
/*
Entry:
{
"title_video": "Video title",
"description": "Video description",
"image": "https://www.image.com/image.png",
"category_id": 1
}
*/
class updateMovie extends AccessContentsManager
{
    public function checkers(Request $request): array
    {
        return [
            ["type/int", $request->getParam('id'), "movie_id"],
            ["movie/exist", fn () => $this->floor->pickup("movie_id"), "movie"],
            ["type/string", $request->getBody()['title_video'], "title_video"],
            ["video/title", fn () => $this->floor->pickup("title_video"), "title_video"],
            ["type/string", $request->getBody()['description'], "description"],
            ["video/description", fn () => $this->floor->pickup("description"), "description"],
            ["type/string", $request->getBody()['image'], "image"],
            ["video/image", fn () => $this->floor->pickup("image"), "image"],
            ["type/int", $request->getBody()['category'], "category_id"],
            ["category/exist", fn () => $this->floor->pickup("category_id"), "category"]
        ];
    }

    public function handler(Request $request, Response $response): void
    {
        VideoManager::updateVideo(
            $this->floor->pickup("movie")->getVideo()->getId(),
            $this->floor->pickup("title_video"),
            $this->floor->pickup("description")
        );
        /** @var Movie $movie */
        $movie = $this->floor->pickup("movie");
        $movie->setImage($this->floor->pickup("image"));
        $movie->setUpdatedAt(date("Y-m-d H:i:s"));
        $movie->save();

        $response->info("movie.updated")->code(200)->send();
    }
}