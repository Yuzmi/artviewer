<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function index(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $defaultLimit = 100;

        // Limit
        $limit = (int) $request->query->get("limit", $defaultLimit);
        if($limit <= 0 || $limit > 1000) {
            $limit = $defaultLimit;
        }

        $page = max(1, $request->query->get("page", 1));
        $offset = $limit * ($page - 1);

        // Get items ids
        $qb = $em->getRepository(Item::class)->createQueryBuilder("i")
            ->andWhere("i.thumbnailUrl IS NOT NULL");

        // Website filter
        $website = trim($request->query->get("website"));
        if($website) {
            $qb->andWhere("i.website = :website");
            $qb->setParameter("website", $website);
        }

        // Rating filter
        $rating = $request->query->get("rating");
        if($rating == "safe") {
            $qb->andWhere("i.isAdult = 0");
        } elseif($rating == "adult") {
            $qb->andWhere("i.isAdult = 1");
        }

        // Tags filter
        $tags = $request->query->get("tags");
        $tags = explode(" ", $tags);

        $i = 0;
        foreach($tags as $tag) {
            $tag = trim($tag);
            if($tag != "") {
                $qb->innerJoin("i.tags", "t".$i);
                $qb->andWhere("t".$i.".name = :tag".$i);
                $qb->setParameter("tag".$i, $tag);
                $i++;
            }
        }

        // Count items
        $countItems = $qb->select("COUNT(i.id)")->getQuery()->getSingleScalarResult();

        // Page count
        $pageCount = (int) ceil($countItems / $limit);

        // Get ids
        $filterItems = $qb->select("i.id")
            ->orderBy("i.publishedDate", "DESC")
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()->getResult();

        $itemIds = [];
        foreach($filterItems as $i) {
            $itemIds[] = $i["id"];
        }

        // Get items
        $items = $em->getRepository(Item::class)->createQueryBuilder("i")
            ->leftJoin("i.tags", "t")->addSelect("t")
            ->where("i.id IN (:ids)")->setParameter("ids", $itemIds)
            ->orderBy("i.publishedDate", "DESC")
            ->getQuery()->getResult();

        // Get tags
        /*$tags = $em->getRepository(Tag::class)->createQueryBuilder("t")
            ->innerJoin("t.items", "i")
            ->where("i.id IN (:itemIds)")->setParameter("itemIds", $itemIds)
            ->getQuery()->getResult();

        $tagIds = [];
        foreach($tags as $t) {
            $tagIds[] = $t->getId();
        }

        // Get tags data
        $tagData = $em->getRepository(Tag::class)->createQueryBuilder("t")
            ->select("t AS tag, COUNT(i.id) AS countItems")
            ->leftJoin("t.items", "i")
            ->where("t.id IN (:ids)")->setParameter("ids", $tagIds)
            ->groupBy("t")
            ->having("countItems > 0")
            ->orderBy("t.name", "ASC")
            ->getQuery()->getResult();*/

        $websites = [
            "danbooru" => "Danbooru",
            "deviantart" => "DeviantArt",
            "konachan" => "Konachan",
            "safebooru" => "Safebooru",
            "sankakucomplex" => "Sankaku Complex"
        ];

        return $this->render('default/index.html.twig', [
            "items" => $items,
            "limit" => $limit,
            "page" => $page,
            "pageCount" => $pageCount,
            "websites" => $websites
        ]);
    }

    public function autocompleteTag(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $term = trim($request->query->get("term"));

        // Get 20 most popular tags
        $tags = $em->getRepository(Tag::class)->createQueryBuilder("t")
            ->select("t.name")
            ->where("t.name LIKE :name")->setParameter("name", $term."%")
            ->orderBy("t.name", "ASC")
            ->setMaxResults(10)
            ->getQuery()->getResult();

        $names = [];
        foreach($tags as $t) {
            $names[] = $t["name"];
        }

        return new JsonResponse($names);
    }
}
