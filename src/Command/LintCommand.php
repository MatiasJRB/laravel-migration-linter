<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Command;

use MatiasJRB\LaravelMigrationLinter\Analysis\Linter;
use MatiasJRB\LaravelMigrationLinter\Config\ConfigurationLoader;
use MatiasJRB\LaravelMigrationLinter\Format\FormatterFactory;
use MatiasJRB\LaravelMigrationLinter\Support\Path;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'lint', description: 'Analyze Laravel migration up() methods for unsafe operations.')]
final class LintCommand extends Command
{
    public function __construct(
        private readonly Linter $linter = new Linter(),
        private readonly ConfigurationLoader $configurationLoader = new ConfigurationLoader(),
        private readonly FormatterFactory $formatterFactory = new FormatterFactory(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'paths',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Migration files or directories. Configured paths are used when omitted.',
            )
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format: console, json, or markdown.', 'console')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the report to a file instead of stdout.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = getcwd();
        if ($workingDirectory === false) {
            $output->writeln('<error>Unable to determine the working directory.</error>');

            return 2;
        }

        try {
            $configPath = $input->getOption('config');
            $configuration = $this->configurationLoader->load(
                is_string($configPath) ? $configPath : null,
                $workingDirectory,
            );

            $inputPaths = $input->getArgument('paths');
            $paths = is_array($inputPaths) && $inputPaths !== []
                ? array_values(array_filter($inputPaths, 'is_string'))
                : $configuration->paths;

            $format = $input->getOption('format');
            $formatter = $this->formatterFactory->create(is_string($format) ? $format : 'console');
            $result = $this->linter->lint($paths, $configuration, $workingDirectory);
            $report = $formatter->render($result);

            $outputPath = $input->getOption('output');
            if (is_string($outputPath) && $outputPath !== '') {
                $resolvedOutputPath = Path::isAbsolute($outputPath)
                    ? $outputPath
                    : $workingDirectory.DIRECTORY_SEPARATOR.$outputPath;
                $directory = dirname($resolvedOutputPath);
                if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                    throw new \RuntimeException(sprintf('Unable to create output directory "%s".', $directory));
                }

                if (file_put_contents($resolvedOutputPath, $report) === false) {
                    throw new \RuntimeException(sprintf('Unable to write report to "%s".', $resolvedOutputPath));
                }

                $output->writeln(sprintf('Wrote %s report to %s', $result->status(), $outputPath));
            } else {
                $output->write($report);
            }

            return $result->exitCode();
        } catch (Throwable $error) {
            $output->writeln('<error>'.$error->getMessage().'</error>');

            return 2;
        }
    }
}
