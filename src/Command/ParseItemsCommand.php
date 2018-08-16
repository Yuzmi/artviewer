<?php

namespace App\Command;

use App\Entity\Item;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseItemsCommand extends ContainerAwareCommand {
	protected function configure() {
		$this
			->setName("app:parse:items")
			->addOption("website", "w", InputOption::VALUE_REQUIRED)
			->addOption("unparsed", null, InputOption::VALUE_NONE)
			->addOption("recent", null, InputOption::VALUE_NONE)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$em = $this->getContainer()->get("doctrine")->getManager();
		$i = 0;

		$qb = $em->getRepository(Item::class)->createQueryBuilder("i");

		$website = $input->getOption("website");
		if($website !== null) {
			$qb->andWhere("i.website = :website");
			$qb->setParameter("website", $website);
		}

		if($input->getOption("unparsed")) {
			$qb->andWhere("i.parseDate IS NULL");
		}

		if($input->getOption("recent")) {
			$qb->andWhere("i.parseDate IS NULL OR i.parseDate >= :recently");
			$qb->setParameter("recently", new \DateTime("2 days ago"));
		}

		$items = $qb->getQuery()->getResult();

		if(count($items) <= 0) {
			$output->writeln("No item found");
			return;
		}

		$output->write("Parsing items");

		foreach($items as $item) {
			$item = $em->getRepository(Item::class)->find($item->getId());
			if($item->getWebsite() == "danbooru") {
				$this->getContainer()->get("App\Service\Danbooru")->parseItem($item);
			} elseif($item->getWebsite() == "deviantart") {
				$this->getContainer()->get("App\Service\DeviantArt")->parseItem($item);
			} elseif($item->getWebsite() == "konachan") {
				$this->getContainer()->get("App\Service\Konachan")->parseItem($item);
			} elseif($item->getWebsite() == "safebooru") {
				$this->getContainer()->get("App\Service\Safebooru")->parseItem($item);
			}

			$i++;
			if($i%20 == 0) {
				$em->clear();
			}

			$output->write(".");
			sleep(1);
		}

		$output->writeln("");
	}
}
