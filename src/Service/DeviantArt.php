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
			$url = "https://backend.deviantart.com/rss.xml?q=boost%3Apopular+max_age%3A8h+meta%3Aall&type=deviation";
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
			if(!$url) {
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
			if(!$item->getPublishedDate()) {
				$publishedDate = null;
				try {
					$publishedDateString = $entry->filter("pubDate")->eq(0)->text();
					if($publishedDateString) {
						$publishedDateTimestamp = strtotime($publishedDateString);
						if($publishedDateTimestamp) {
							$publishedDate = new \DateTime();
							$publishedDate->setTimestamp($publishedDateTimestamp);
						}
					}
				} catch(\Exception $e) {}
				
				if($publishedDate) {
					$item->setPublishedDate($publishedDate);
				} else {
					$item->setPublishedDate(new \DateTime());
				}
			}

			// Title
			try {
				$title = $entry->filter("title")->eq(0)->text();
			} catch(\Exception $e) {
				$title = null;
			}
			if($title) {
				$item->setTitle($title);
			}

			// Preview Url
			$previewUrl = $this->getPreviewUrl($entry);
			if($previewUrl) {
				$item->setPreviewUrl($previewUrl);
			}

			// Rating
			$rating = $this->getRating($entry);
			if($rating == "adult") {
				$item->setIsAdult(true);
			} elseif($rating == "nonadult") {
				$item->setIsAdult(false);
			}

			if($item->getPreviewUrl()) {
				$this->em->persist($item);
			}
		}

		$this->em->flush();
	}

	private function getPreviewUrl(Crawler $entry) {
		$previewUrl = null;

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
					$previewUrl = $url;
					break;
				}
			}
		}

		// Or from the description
		if(!$previewUrl) {
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
						$previewUrl = $url;
						break;
					}
				}

				// Or the original as a fallback
				if(!$previewUrl) {
					foreach($urls as $url) {
						if(preg_match('#^https?://orig00\.deviantart#isU', $url)) {
							$previewUrl = $url;
							break;
						}
					}
				}
			} catch(\Exception $e) {}
		}

		return $previewUrl;
	}

	private function getRating(Crawler $entry) {
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
}
