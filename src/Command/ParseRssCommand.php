<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseRssCommand extends ContainerAwareCommand {
	protected function configure() {
		$this
			->setName("app:parse:rss")
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("Parsing Danbooru...");
		$this->getContainer()->get("App\Service\Danbooru")->parseRss();

		$output->writeln("Parsing DeviantArt...");
		$this->getContainer()->get("App\Service\DeviantArt")->parseRss();

		$output->writeln("Parsing Konachan...");
		$this->getContainer()->get("App\Service\Konachan")->parseRss();

		$output->writeln("Parsing Safebooru...");
		$this->getContainer()->get("App\Service\Safebooru")->parseRss();

		$output->writeln("Parsing Sankaku Complex...");
		$this->getContainer()->get("App\Service\SankakuComplex")->parseRss();
	}
}
