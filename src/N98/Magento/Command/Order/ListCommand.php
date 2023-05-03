<?php

namespace N98\Magento\Command\Order;

use N98\Magento\Command\Customer\AbstractCustomerCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractOrderCommand
{
    protected function configure()
    {
        $this
            ->setName('order:list')
            ->addArgument('search', InputArgument::OPTIONAL, 'Search query')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->setDescription('Lists orders')
        ;

        $help = <<<HELP
List orders.
If search parameter is given the orders are filtered (searchs in firstname, lastname and email).
HELP;
        $this->setHelp($help);
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

        $config = $this->getCommandConfig();

        $collection = $this->getOrderCollection();

        $collection->addFieldToSelect(array('entity_id', 'state', 'status','increment_id', 'customer_email'));

        if ($input->getArgument('search')) {
            $collection->addFieldToFilter(
		array('increment_id','status','state','customer_email'),
                array(
                    array('like' => '%' . $input->getArgument('search') . '%'),
                    array('like' => '%' . $input->getArgument('search') . '%'),
                    array('like' => '%' . $input->getArgument('search') . '%'),
                    array('like' => '%' . $input->getArgument('search') . '%'),
                )
            );
        }

        //$collection->setPageSize($config['limit']);

        $table = array();
        foreach ($collection as $order) {
            $table[] = array(
                $order->getIncrementId(),
                $order->getState(),
		$order->getCustomerEmail(),
                $order->getId(),
            );
        }

        if (count($table) > 0) {
            // @var $tableHelper TableHelper
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(array('IncrementId', 'State', 'Email', 'Id'))
                ->renderByFormat($output, $table, $input->getOption('format'));
        } else {
            $output->writeln('<comment>No orders found</comment>');
        }

    }
}
