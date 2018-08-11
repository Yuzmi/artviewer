<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseSafebooruRssCommand extends ContainerAwareCommand {
	protected function configure() {
		$this
			->setName("app:parse:safebooru:rss")
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("Parsing Safebooru RSS...");
		$this->getContainer()->get("App\Service\Safebooru")->parseRss();
	}
}
