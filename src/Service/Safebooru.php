<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\Tag;
use App\Service\phpUri;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DomCrawler\Crawler;

class Safebooru extends Parser
{
	private $websiteUrl = "http://safebooru.org/";

	public function parseRss() {
		$rssItems = $this->getPieItems("http://safebooru.org/index.php?page=cooliris");

		// Get tags from items
		$rawTags = [];
		foreach($rssItems as $i) {
			$title = $i->get_title();
			$iTags = explode(" ", $title);
			foreach($iTags as $tag) {
				$tag = $this->sanitizeString($tag);
				if($tag != "" && !in_array($tag, $rawTags)) {
					$rawTags[] = $tag;
				}
			}
		}

		// Get or create tags
		$tags = [];
		foreach($rawTags as $t) {
			$tag = $this->em->getRepository(Tag::class)->findOneByName($t);
			if(!$tag) {
				$tag = new Tag();
				$tag->setName($t);
				$this->em->persist($tag);
			}
			$tags[$t] = $tag;
		}
		$this->em->flush();

		foreach($rssItems as $i) {
			// Link
			$url = $i->get_link();
			if(!$url && filter_var($url, FILTER_VALIDATE_URL) !== false) {
				continue;
			}

			$url = phpUri::parse($this->websiteUrl)->join($url);
			$url = str_replace("&amp;", "&", $url);

			$item = $this->em->getRepository(Item::class)->findOneByUrl($url);
			if(!$item) {
				$item = new Item();
				$item->setUrl($url);
				$item->setWebsite("safebooru");
			}

			$item->setUpdateDate(new \DateTime());

			// Publication date
			$publishedDate = $i->get_date(DATE_ATOM);
			if($publishedDate) {
				$publishedDate = new \DateTime($publishedDate);
				if($publishedDate) {
					$item->setPublishedDate($publishedDate);
				}
			}

			// Title
			$title = $this->sanitizeText($i->get_title());
			if($title) {
				$item->setTitle($title);
			}

			// Thumbnail URL
			$thumbnail = $i->get_thumbnail();
			if($thumbnail && isset($thumbnail["url"])) {
				$thumbnailUrl = phpUri::parse($this->websiteUrl)->join($thumbnail["url"]);
				if(filter_var($thumbnailUrl, FILTER_VALIDATE_URL) !== false) {
					$item->setThumbnailUrl($thumbnailUrl);
				}
			}

			$this->em->persist($item);
		}

		$this->em->flush();
	}

	public function parseItem(Item $item) {
		$data = $this->getUrlData($item->getUrl());
		if(!$data["success"]) return;

		$crawler = new Crawler($data["content"]);

		$infoSections = $crawler->filter("#stats");
		if($infoSections->count() > 0) {
			$infoSection = $infoSections->eq(0);
			$infos = $infoSection->filter("ul li");
			for($i=0,$count=$infos->count();$i<$count;$i++) {
				$info = $infos->eq($i);

				// Rating
				$infoText = mb_strtolower($info->text());
				if(strpos($infoText, "rating") !== false) {
					if(strpos($infoText, "safe") !== false) {
						$item->setIsAdult(false);
					} elseif(strpos($infoText, "explicit") !== false
					|| strpos($infoText, "questionable") !== false) {
						$item->setIsAdult(true);
					}
				}
			}
		}

		$item->setUpdateDate(new \DateTime());
		$item->setParseDate(new \DateTime());

		$this->em->persist($item);
		$this->em->flush();
	}
}
