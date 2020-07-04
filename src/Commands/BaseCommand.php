<?php

namespace Sven\ForgeCLI\Commands;

use Sven\FileConfig\Drivers\Json;
use Sven\FileConfig\File;
use Sven\FileConfig\Store;
use Sven\ForgeCLI\Contracts\NeedsForge;
use Sven\ForgeCLI\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Themsaid\Forge\Forge;

abstract class BaseCommand extends Command
{
    /**
     * @var \Themsaid\Forge\Forge
     */
    protected $forge;

    /**
     * @var \Sven\FileConfig\Store
     */
    protected $config;

    /**
     * @var array
     */
    protected $optionMap = [];

    /**
     * @param \Sven\FileConfig\Store     $config
     * @param \Themsaid\Forge\Forge|null $forge
     */
    public function __construct(Store $config, Forge $forge = null)
    {
        parent::__construct();

        $this->config = $config;

        if ($this instanceof NeedsForge) {
            $this->forge = $forge ?: new Forge($this->config->get('key'));
        }
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasArgument('server')) {
            return;
        }

        // If the 'site' argument is present, the user probably did not
        // use an alias, so we will return early. If it is missing,
        // resolve the alias and set the arguments accordingly.
        if ($input->hasArgument('site') && $input->getArgument('site') !== null) {
            return;
        }

        $alias = $this->config->get(
            'aliases.'.$input->getArgument('server')
        );

        // No alias was found by that name, so we will
        // continue executing the command here. This
        // will cause a validation error later on.
        if ($alias === null) {
            $output->writeln('<error>No alias found for "'.$input->getArgument('server').'".</error>');

            return;
        }

        // Could not find alias for site, continue executing the
        // command to cause an error later on by Symfony's own
        // validation that takes place after this method.
        if (!isset($alias['site']) && $input->hasArgument('site')) {
            $output->writeln('<error>No site alias found, but a site is required for this command.</error>');

            return;
        }

        if (!$output->isQuiet()) {
            $message = 'Using aliased server "'.$alias['server'].'"';

            if ($input->hasArgument('site')) {
                $message .= ' and site "'.$alias['site'].'"';
            }

            $output->writeln('<comment>'.$message.'.</comment>');
        }

        $input->setArgument('server', $alias['server']);

        if ($input->hasArgument('site')) {
            $input->setArgument('site', $alias['site']);
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array                                             $header
     * @param array                                             $rows
     */
    protected function table(OutputInterface $output, array $header, array $rows)
    {
        $table = new Table($output);
        $table->setHeaders($header)
            ->setRows($rows);

        $table->render();
    }

    /**
     * @param array      $options
     * @param array|null $optionMap
     *
     * @return array
     */
    protected function fillData(array $options, array $optionMap = null)
    {
        $data = [];

        foreach ($optionMap ?: $this->optionMap as $option => $requestKey) {
            if (!isset($options[$option])) {
                continue;
            }

            $data[$requestKey] = $options[$option];
        }

        return $data;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param string                                          $option
     *
     * @return bool|string
     */
    protected function getFileContent(InputInterface $input, $option)
    {
        $filename = $input->hasOption($option) ? $input->getOption($option) : 'php://stdin';

        if (!file_exists($filename)) {
            return $filename;
        }

        if ($filename && ftell(STDIN) !== false) {
            return file_get_contents($filename);
        }

        throw new \InvalidArgumentException('This command requires either the "--'.$option.'" option to be set, or an input from STDIN.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param array                                           ...$keys
     *
     * @throws \RuntimeException
     */
    protected function requireOptions(InputInterface $input, ...$keys)
    {
        foreach ($keys as $key) {
            if ($input->hasOption($key)) {
                continue;
            }

            throw new \RuntimeException(
                sprintf('The option "%s" is required.', $key)
            );
        }
    }

    protected function getFileConfig(): Store
    {
        $config = Util::getConfigFilePath();

        // If this is the first time this command is run, we will
        // create a new configuration file. Otherwise, we just
        // return the already existing configuration store.
        if (! file_exists($config)) {
            file_put_contents($config, '{"key":""}');
        }

        return new Store(new File($config), new Json());
    }
}
