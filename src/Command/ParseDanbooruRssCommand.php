<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseDanbooruRssCommand extends ContainerAwareCommand {
	protected function configure() {
		$this
			->setName("app:parse:danbooru:rss")
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("Parsing Danbooru RSS...");
		$this->getContainer()->get("App\Service\Danbooru")->parseRss();
	}
}
