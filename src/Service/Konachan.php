<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;

class Konachan extends Parser
{
	public function parseRss() {
		$rssItems = $this->getPieItems("https://konachan.com/post/atom");
		
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
				$item->setWebsite("konachan");
			}

			$item->setUpdateDate(new \DateTime());

			// Publication date
			if(!$item->getPublishedDate()) {
				$publishedDate = $i->get_date(DATE_ATOM);
				if($publishedDate) {
					$publishedDate = new \DateTime($publishedDate);
				}

				if($publishedDate) {
					$item->setPublishedDate($publishedDate);
				} else {
					$item->setPublishedDate(new \DateTime());
				}
			}

			// Title
			$title = $this->sanitizeText($i->get_title());
			if($title) {
				$item->setTitle($title);
			}

			// Preview URL
			$previewUrl = $i->get_link(0, "enclosure");
			if(filter_var($previewUrl, FILTER_VALIDATE_URL) !== false) {
				$item->setPreviewUrl($previewUrl);
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
}
