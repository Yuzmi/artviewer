<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseKonachanRssCommand extends ContainerAwareCommand {
	protected function configure() {
		$this
			->setName("app:parse:konachan:rss")
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("Parsing Konachan RSS...");
		$this->getContainer()->get("App\Service\Konachan")->parseRss();
	}
}
