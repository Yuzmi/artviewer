<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseDeviantArtRssCommand extends ContainerAwareCommand {
	protected function configure() {
		$this
			->setName("app:parse:deviantart:rss")
			->addOption("q", null, InputOption::VALUE_REQUIRED)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("Parsing DeviantArt RSS...");
		$this->getContainer()->get("App\Service\DeviantArt")->parseRss($input->getOption("q"));
	}
}
