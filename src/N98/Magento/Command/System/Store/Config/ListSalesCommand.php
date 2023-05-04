<?php

namespace N98\Magento\Command\System\Store\Config;

use N98\Util\Console\Helper\DatabaseHelper;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListSalesCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:store:config:sales:list')
            ->setDescription('Lists store sales settings')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
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

        if (!$input->getOption('format')) {
            $this->writeSection($output, 'Magento Stores - Sales');
        }
        $this->initMagento();

	$dbHelper = $this->getHelper('database');
	$connection = $dbHelper->getConnection();
	$sql = " 
	SELECT 
        t.entity_type_code 
       	,s.increment_prefix 
        ,t.increment_pad_length 
        ,s.increment_last_id 
        ,cw.website_id as WebsiteId 
        ,cw.code as WebsiteCode 
        ,cs.store_id as StoreId 
        ,cs.code as StoreCode 
	FROM eav_entity_type t 
	INNER JOIN eav_entity_store s ON (t.entity_type_id=s.entity_type_id)
	INNER JOIN core_store cs ON (s.store_id=cs.store_id)
	INNER JOIN core_website cw ON (cs.website_id=cw.website_id)
	ORDER BY WebsiteCode,StoreCode DESC
	";	
	$stmt = $connection->query($sql);
	$result = $stmt->fetchAll();
	//$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
	//$rows = $connection->fetchAll($sql);

        foreach ($result as $row) {
		$table[] = array (
			$row['entity_type_code'],
			$row['increment_prefix'],
			$row['increment_pad_length'],
			$row['increment_last_id'],
			$row['WebsiteId'],
			$row['WebsiteCode'],
			$row['StoreId'],
			$row['StoreCode'],
		);
	}

        // @var $tableHelper TableHelper 
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(array('type', 'prefix', 'length', 'last','website id','website','store id','store'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }
}
