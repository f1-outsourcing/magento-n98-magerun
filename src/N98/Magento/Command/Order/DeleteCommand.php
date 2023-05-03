<?php

namespace N98\Magento\Command\Order;

use Exception;
use Mage_Customer_Model_Entity_Customer_Collection;
use Mage_Customer_Model_Resource_Customer_Collection;
use Mage_Sales_Model_Entity_Order_Collection;
use Mage_Sales_Model_Resource_Order_Collection;
use N98\Util\Console\Helper\ParameterHelper;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteCommand
 * @package N98\Magento\Command\Customer
 */
class DeleteCommand extends AbstractOrderCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var DialogHelper
     */
    protected $dialog;

    /**
     * Set up options
     */
    protected function configure()
    {
        $this
            ->setName('order:delete')
            ->addArgument('id', InputArgument::OPTIONAL, 'Customer Id', false)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Delete all customers')
            ->setDescription('Delete Customer/s');

        $help = <<<HELP
This will delete an order by a given IncrementId, delete all orders or delete all orders in a range of Ids.

<comment>Example Usage:</comment>

n98-magerun order:delete 200000001           <info># Will delete order with IncrementId 200000001</info>
n98-magerun order:delete --all               <info># Will delete all customers</info>

HELP;

        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return false|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $this->input = $input;
        $this->output = $output;
        /** @var DialogHelper dialog */
        $this->dialog = $this->getHelper('dialog');

        // Defaults
        $all = false;
	
        $this->output->writeln('<error>'.$interactive.'</error>');
	
        $id = $this->input->getArgument('id');
        $all = $this->input->getOption('all');
        // Get args required
        if (!($id) && !($all)) {

            // Delete more than one order ?
            $batchDelete = $this->dialog->askConfirmation(
                $this->output,
                $this->getQuestion('Delete more than 1 order?', 'n'),
                false
            );

            if ($batchDelete) {
                // Batch deletion
                $all = $this->dialog->askConfirmation(
                    $this->output,
                    $this->getQuestion('Delete all orders?', 'n'),
                    false
                );

            }
        }

        if (!$all) {
            // Single order deletion
            if (!$id) {
                $id = $this->dialog->ask($this->output, $this->getQuestion('Order Id'), null);
            }

            try {
                $order = $this->getOrder($id);
            } catch (Exception $e) {
                $this->output->writeln('<error>No order found!</error>');
                return false;
            }

            if ($this->shouldRemove()) {
                $this->deleteOrder($order);
            } else {
                $this->output->writeln('<error>Aborting delete</error>');
            }
        } else {
            $orders = $this->getOrderCollection();

            if ($this->shouldRemove()) {
                $count = $this->batchDelete($orders);
                $this->output->writeln('<info>Successfully deleted ' . $count . ' orders/s</info>');
            } else {
                $this->output->writeln('<error>Aborting delete</error>');
            }
        }
    }

    /**
     * @return bool
     */
    protected function shouldRemove()
    {
	$shouldRemove = ! $this->input->isInteractive();
        if (!$shouldRemove) {
            $shouldRemove = $this->dialog->askConfirmation(
                $this->output,
                $this->getQuestion('Are you sure?', 'n'),
                false
            );
        }

        return $shouldRemove;
    }

    /**
     * @param int|string $id
     *
     * @return \Mage_Customer_Model_Customer
     * @throws RuntimeException
     */
    protected function getOrder($id)
    {
        /** @var \Mage_Customer_Model_Customer $customer */
        $order = $this->getOrderModel()->loadByIncrementId($id);

	/*
        if (!$order->getId()) {
            // @var $parameterHelper ParameterHelper 
            $parameterHelper = $this->getHelper('parameter');
            $website = $parameterHelper->askWebsite($this->input, $this->output);
            $order = $this->getOrderModel()
                ->setWebsiteId($website->getId())
                ->loadByEmail($id);
        }
	*/

        if (!$order->getId()) {
            throw new RuntimeException('No order found!');
        }

        return $order;
    }

    /**
     * @param \Mage_Sales_Model_Order $order
     *
     * @return true|Exception
     */
    protected function deleteOrder(\Mage_Sales_Model_Order $order)
    {
        try {
            $order->delete();
            $this->output->writeln(
                sprintf('<info>%s (%s) was successfully deleted</info>', $order->getId(), $order->getIncrementId())
            );
            return true;
        } catch (Exception $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            return $e;
        }
    }

    /**
     * @param Mage_Customer_Model_Entity_Customer_Collection|Mage_Customer_Model_Resource_Customer_Collection $customers
     *
     * @return int
     */
    protected function batchDelete($orders)
    {
        $count = 0;
        foreach ($orders as $order) {
            if ($this->deleteOrder($order) === true) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string $answer
     * @return string
     */
    public function validateInt($answer)
    {
        if (intval($answer) === 0) {
            throw new RuntimeException(
                'The range should be numeric and above 0 e.g. 1'
            );
        }

        return $answer;
    }

    /**
     * @param string $message
     * @param string $default [optional]
     *
     * @return string
     */
    private function getQuestion($message, $default = null)
    {
        $params = array($message);
        $pattern = '%s: ';

        if (null !== $default) {
            $params[] = $default;
            $pattern .= '[%s] ';
        }

        return vsprintf($pattern, $params);
    }
}
