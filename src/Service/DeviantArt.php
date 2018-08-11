<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DomCrawler\Crawler;

class DeviantArt extends Parser
{
	public function parseRss($q = "popular8h") {
		if($q == "popular8h") {
			$url = "https://backend.deviantart.com/rss.xml?q=boost%3Apopular+max_age%3A8h+meta%3Aall&type=deviation";
		} elseif($q == "popular24h") {
			$url = "https://backend.deviantart.com/rss.xml?q=boost%3Apopular+max_age%3A24h+meta%3Aall&type=deviation";
		} elseif($q == "newest") {
			$url = "https://backend.deviantart.com/rss.xml?q=+sort%3Atime+meta%3Aall&type=deviation";
		} elseif($q == "hot") {
			$url = "https://backend.deviantart.com/rss.xml?q=boost%3Ahot+meta%3Aall&type=deviation";
		} else {
			$url = "https://backend.deviantart.com/rss.xml?q=boost%3Ahot+meta%3Aall&type=deviation";
		}

		$data = $this->getUrlData($url);
		if(!$data["success"]) return;

		$crawler = new Crawler($data["content"]);

		$entries = $crawler->filter("item");
		for($i=0, $countEntries=$entries->count();$i<$countEntries;$i++) {
			$entry = $entries->eq($i);

			try {
				$url = $entry->filter("link")->eq(0)->text();
			} catch(\Exception $e) {
				$url = null;
			}
			if(!$url && filter_var($url, FILTER_VALIDATE_URL) !== false) {
				continue;
			}

			$item = $this->em->getRepository(Item::class)->findOneByUrl($url);
			if(!$item) {
				$item = new Item();
				$item->setUrl($url);
				$item->setWebsite("deviantart");
			}

			$item->setUpdateDate(new \DateTime());

			// Publication date
			try {
				$publishedDateString = $entry->filter("pubDate")->eq(0)->text();
				if($publishedDateString) {
					$publishedDateTimestamp = strtotime($publishedDateString);
					if($publishedDateTimestamp) {
						$publishedDate = new \DateTime();
						$publishedDate->setTimestamp($publishedDateTimestamp);
						
						$item->setPublishedDate($publishedDate);
					}
				}
			} catch(\Exception $e) {}

			// Title
			if($entry->filter("title")->count() > 0) {
				$title = $this->sanitizeText($entry->filter("title")->eq(0)->text());
				if($title) {
					$item->setTitle($title);
				}
			}

			// Thumbnail Url
			$thumbnailUrl = $this->getThumbnailUrlFromRss($entry);
			if($thumbnailUrl) {
				$item->setThumbnailUrl($thumbnailUrl);
			}

			// Rating
			$rating = $this->getRatingFromRss($entry);
			if($rating == "adult") {
				$item->setIsAdult(true);
			} elseif($rating == "nonadult") {
				$item->setIsAdult(false);
			}

			$this->em->persist($item);
		}

		$this->em->flush();
	}

	private function getThumbnailUrlFromRss(Crawler $entry) {
		$thumbnailUrl = null;

		// Get from media:thumbnail
		$children = $entry->children();
		for($i=0,$count=$children->count();$i<$count;$i++) {
			$child = $children->eq($i);
			if($child->nodeName() == "media:thumbnail") {
				$url = $child->attr("url");
				if(
					filter_var($url, FILTER_VALIDATE_URL) !== false
					&& preg_match('#t00\.deviantart.*fixed_height\(100,100\):origin\(\)/pre00/#isU', $url)
				) {
					$thumbnailUrl = $url;
					break;
				}
			}
		}

		// Or from the description
		if(!$thumbnailUrl) {
			try {
				$description = $entry->filter("description")->eq(0)->html();
				$descriptionHtml = html_entity_decode($description);
				$descriptionNode = new Crawler($descriptionHtml);

				$urls = [];

				// Get images
				$images = $descriptionNode->filter("img");
				for($j=0,$countImg=$images->count();$j<$countImg;$j++) {
					$url = trim($images->eq($j)->attr("src"));
					if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
						$urls[] = $url;
					}
				}

				// Get the thumbnail
				foreach($urls as $url) {
					if(preg_match('#^https?://t00\.deviantart.*fixed_height\(100,100\):origin\(\)/pre00/#isU', $url)) {
						$thumbnailUrl = $url;
						break;
					}
				}

				// Or the original as a fallback
				if(!$thumbnailUrl) {
					foreach($urls as $url) {
						if(preg_match('#^https?://orig00\.deviantart#isU', $url)) {
							$thumbnailUrl = $url;
							break;
						}
					}
				}
			} catch(\Exception $e) {}
		}

		return $thumbnailUrl;
	}

	private function getRatingFromRss(Crawler $entry) {
		$rating = null;

		$children = $entry->children();
		for($i=0,$count=$children->count();$i<$count;$i++) {
			$child = $children->eq($i);
			if($child->nodeName() == "media:rating") {
				$rating = $child->text();
				break;
			}
		}

		return $rating;
	}

	public function parseItem(Item $item) {
		$data = $this->getUrlData($item->getUrl());
		if(!$data["success"]) return;

		$crawler = new Crawler($data["content"]);

		// Tags //

		$rawTags = [];

		$elementTags = $crawler->filter(".dev-about-tags-cc .discoverytag");
		for($i=0,$count=$elementTags->count();$i<$count;$i++) {
			$elementTag = $elementTags->eq($i);

			$rawTag = $this->sanitizeString($elementTag->attr("data-canonical-tag"));
			if($rawTag != "" && !in_array($rawTag, $rawTags)) {
				$rawTags[] = $rawTag;
			}
		}

		$tags = [];
		foreach($rawTags as $rawTag) {
			$tag = $this->em->getRepository(Tag::class)->findOneByName($rawTag);
			if(!$tag) {
				$tag = new Tag();
				$tag->setName($rawTag);
				$this->em->persist($tag);
			}

			$tags[] = $tag;
		}
		$this->em->flush();

		foreach($tags as $tag) {
			$item->addTag($tag);
		}

		$tags = new ArrayCollection($tags);
		foreach($item->getTags() as $tag) {
			if(!$tags->contains($tag)) {
				$item->removeTag($tag);
			}
		}

		$item->setUpdateDate(new \DateTime());
		$item->setParseDate(new \DateTime());

		$this->em->persist($item);
		$this->em->flush();
	}
}
