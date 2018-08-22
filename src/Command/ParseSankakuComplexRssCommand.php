<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseSankakuComplexRssCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName("app:parse:sankakucomplex:rss")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("Parsing Sankaku Complex RSS...");
        $this->getContainer()->get("App\Service\SankakuComplex")->parseRss();
    }
}
