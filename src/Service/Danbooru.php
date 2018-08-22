<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DomCrawler\Crawler;

class Danbooru extends Parser
{
	public function parseRss() {
		$rssItems = $this->getPieItems("https://danbooru.donmai.us/posts.atom");

		// Get tags from items
		$rawTags = [];
		foreach($rssItems as $i) {
			$summary = $i->get_description(true);
			$iTags = explode(" ", $summary);
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

		// Save items
		foreach($rssItems as $i) {
			// Link
			$url = $i->get_link();
			if(!$url && filter_var($url, FILTER_VALIDATE_URL) !== false) {
				continue;
			}

			$item = $this->em->getRepository(Item::class)->findOneByUrl($url);
			if(!$item) {
				$item = new Item();
				$item->setUrl($url);
				$item->setWebsite("danbooru");
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

			$content = $i->get_content(true);
			$crawler = new Crawler($content);

			// Thumbnail URL
			$thumbnail = null;
			try {
				$url = $crawler->filter("img")->eq(0)->attr("src");
				if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
					$thumbnailUrl = $url;
				}
			} catch(\Exception $e) {
				$thumbnailUrl = null;
			}
			if($thumbnailUrl) {
				$item->setThumbnailUrl($thumbnailUrl);
			}

			// Tags (in the summary)
			$summary = $i->get_description(true);
			$iNewTags = [];

			// Get tags for item
			$iRawTags = explode(" ", $summary);
			foreach($iRawTags as $iRawTag) {
				$iRawTag = $this->sanitizeString($iRawTag);

				if($iRawTag != "" && array_key_exists($iRawTag, $tags)) {
					$iNewTags[] = $tags[$iRawTag];
				}
			}

			// Add new tags
			foreach($iNewTags as $iNewTag) {
				$item->addTag($iNewTag);
			}

			// Remove obsolete tags
			$iNewTags = new ArrayCollection($iNewTags);
			foreach($item->getTags() as $tag) {
				if(!$iNewTags->contains($tag)) {
					$item->removeTag($tag);
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

		$infoSections = $crawler->filter("section#post-information");
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
