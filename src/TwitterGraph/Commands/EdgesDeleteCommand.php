<?php

namespace TheDonHimself\GremlinOGM\TwitterGraph\Graph\Commands;

use Brightzone\GremlinDriver\InternalException;
use Brightzone\GremlinDriver\ServerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TheDonHimself\GremlinOGM\GraphConnection;

class EdgesDeleteCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('twittergraph:edges:delete')
            ->setDescription('TwitterGraph Delete Edges')
            ->addOption('configPath', null, InputOption::VALUE_OPTIONAL, 'The Path to the JSON Configuration FIle')
            ->addOption('label', null, InputOption::VALUE_OPTIONAL, 'The Edge label to be deleted');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configPath = $input->getOption('configPath');

        $options = array();

        if ($configPath) {
            $configFile = file_get_contents($configPath);
            $config = json_decode($configFile, true);
            $options = $config['options'];
        }

        $label = $input->getOption('label');

        $graph_connection = (new GraphConnection($options))->init();

        try {
            $graph_connection->open();
        } catch (InternalException $e) {
            $output->writeln($e->getMessage());

            return;
        }

        $graph_connection->transactionStart();

        //  Count Number of Edges
        $number_of_edges = 0;
        try {
            if ($label) {
                $result = $graph_connection->send("g.V().hasLabel('$label').count()");
            } else {
                $result = $graph_connection->send('g.V().count()');
            }
            $number_of_edges = $result[0];
            $output->writeln('Number of Edges in Graph : '.number_format($number_of_edges));
        } catch (ServerException $e) {
            $output->writeln($e->getMessage());

            return;
        }

        if ($number_of_edges > 0) {
            $output->writeln('Dropping All Edges');

            // Drop all edges
            if ($label) {
                $graph_connection->send("g.V().hasLabel('$label').drop().iterate()");
            } else {
                $graph_connection->send('g.V().drop().iterate()');
            }

            $graph_connection->transactionStop(true);
            $graph_connection->transactionStart();

            if ($label) {
                $result = $graph_connection->send("g.V().hasLabel('$label').count()");
            } else {
                $result = $graph_connection->send('g.V().count()');
            }

            $number_of_edges = $result[0];
            $output->writeln('Number of Edges in Graph : '.number_format($number_of_edges));
        }

        $graph_connection->transactionStop(true);

        $graph_connection->close();
    }
}
