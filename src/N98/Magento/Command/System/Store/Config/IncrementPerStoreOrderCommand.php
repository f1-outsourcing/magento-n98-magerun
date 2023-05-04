<?php

namespace N98\Magento\Command\System\Store\Config;

use N98\Util\Console\Helper\DatabaseHelper;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class IncrementPerStoreOrderCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:store:config:order:incrementperstore')
            ->setDescription('Set order incrementing per store')
            ->addArgument('inc', InputArgument::REQUIRED, 'inc')
            ->addArgument('storeid', InputArgument::REQUIRED, 'StoreId')
            ->addArgument('websiteid', InputArgument::REQUIRED, 'WebsiteId')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
	if (!$this->initMagento()) {
	        return;
	}

	$this->input = $input;
	$this->output = $output;

	$inc = (int)$this->input->getArgument('inc');
	$storeid = (int)$this->input->getArgument('storeid');
	$webid = (int)$this->input->getArgument('websiteid');

	$dbHelper = $this->getHelper('database');
	$connection = $dbHelper->getConnection();
	$sql = " 
	UPDATE eav_entity_type t 
	INNER JOIN eav_entity_store s ON (t.entity_type_id=s.entity_type_id)
	INNER JOIN core_store cs ON (s.store_id=cs.store_id)
	INNER JOIN core_website cw ON (cs.website_id=cw.website_id)
	SET t.increment_per_store=$inc
	WHERE t.entity_type_code='order' AND cw.website_id=$webid AND s.store_id=$storeid
	";	
	$stmt = $connection->prepare($sql);
	$stmt->execute();

	if ($stmt->rowCount()>0) {
		$this->output->writeln('<info>Updated padding</info>');
	} else {
		$this->output->writeln('<error>Could not execute query update.</error>');
	}
		

    }
}
