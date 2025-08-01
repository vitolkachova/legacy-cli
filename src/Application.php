<?php
namespace Platformsh\Cli;

use Platformsh\Cli\Command\MultiAwareInterface;
use Platformsh\Cli\Console\EventSubscriber;
use Platformsh\Cli\Console\HiddenInputOption;
use Platformsh\Cli\Service\Config;
use Platformsh\Cli\Util\TimezoneUtil;
use Symfony\Component\Console\Application as ParentApplication;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException as ConsoleInvalidOptionException;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Application extends ParentApplication
{
    /**
     * @var ConsoleCommand|null
     */
    protected $currentCommand;

    /** @var Config */
    protected $cliConfig;

    /** @var string */
    private $envPrefix;

    /** @var bool */
    private $runningViaMulti = false;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->cliConfig = new Config();
        $this->envPrefix = $this->cliConfig->get('application.env_prefix');
        parent::__construct($this->cliConfig->get('application.name'), $this->cliConfig->getVersion());

        // Use the configured timezone, or fall back to the system timezone.
        date_default_timezone_set(
            $this->cliConfig->getWithDefault('application.timezone', TimezoneUtil::getTimezone())
        );

        $this->addCommands($this->getCommands());

        $this->setDefaultCommand('welcome');

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new EventSubscriber($this->cliConfig));
        $this->setDispatcher($dispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Only print necessary output; suppress other messages and errors. This implies --no-interaction. It is ignored in verbose mode.'),
            new InputOption('--yes', '-y', InputOption::VALUE_NONE, 'Answer "yes" to confirmation questions; accept the default value for other questions; disable interaction'),
            new InputOption(
                '--no-interaction',
                null,
                InputOption::VALUE_NONE,
                'Do not ask any interactive questions; accept default values. '
                . sprintf('Equivalent to using the environment variable: <comment>%sNO_INTERACTION=1</comment>', $this->envPrefix)
            ),
            new HiddenInputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
            new HiddenInputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
            new HiddenInputOption('--no', '-n', InputOption::VALUE_NONE, 'Answer "no" to confirmation questions; accept the default value for other questions; disable interaction'),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        // Override the default commands to add a custom HelpCommand and
        // ListCommand.
        return [new Command\HelpCommand(), new Command\ListCommand()];
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    protected function getCommands()
    {
        static $commands = [];
        if (count($commands)) {
            return $commands;
        }

        $commands[] = new Command\ApiCurlCommand();
        $commands[] = new Command\BotCommand();
        $commands[] = new Command\ClearCacheCommand();
        $commands[] = new Command\CompletionCommand();
        $commands[] = new Command\DecodeCommand();
        $commands[] = new Command\DocsCommand();
        $commands[] = new Command\LegacyMigrateCommand();
        $commands[] = new Command\MultiCommand();
        $commands[] = new Command\Activity\ActivityCancelCommand();
        $commands[] = new Command\Activity\ActivityGetCommand();
        $commands[] = new Command\Activity\ActivityListCommand();
        $commands[] = new Command\Activity\ActivityLogCommand();
        $commands[] = new Command\App\AppConfigGetCommand();
        $commands[] = new Command\App\AppListCommand();
        $commands[] = new Command\Auth\AuthInfoCommand();
        $commands[] = new Command\Auth\AuthTokenCommand();
        $commands[] = new Command\Auth\LogoutCommand();
        $commands[] = new Command\Auth\ApiTokenLoginCommand();
        $commands[] = new Command\Auth\BrowserLoginCommand();
        $commands[] = new Command\Auth\VerifyPhoneNumberCommand();
        $commands[] = new Command\BlueGreen\BlueGreenConcludeCommand();
        $commands[] = new Command\BlueGreen\BlueGreenDeployCommand();
        $commands[] = new Command\BlueGreen\BlueGreenEnableCommand();
        $commands[] = new Command\Certificate\CertificateAddCommand();
        $commands[] = new Command\Certificate\CertificateDeleteCommand();
        $commands[] = new Command\Certificate\CertificateGetCommand();
        $commands[] = new Command\Certificate\CertificateListCommand();
        $commands[] = new Command\Commit\CommitGetCommand();
        $commands[] = new Command\Commit\CommitListCommand();
        $commands[] = new Command\Db\DbSqlCommand();
        $commands[] = new Command\Db\DbDumpCommand();
        $commands[] = new Command\Db\DbSizeCommand();
        $commands[] = new Command\Domain\DomainAddCommand();
        $commands[] = new Command\Domain\DomainDeleteCommand();
        $commands[] = new Command\Domain\DomainGetCommand();
        $commands[] = new Command\Domain\DomainListCommand();
        $commands[] = new Command\Domain\DomainUpdateCommand();
        $commands[] = new Command\Environment\EnvironmentActivateCommand();
        $commands[] = new Command\Environment\EnvironmentBranchCommand();
        $commands[] = new Command\Environment\EnvironmentCheckoutCommand();
        $commands[] = new Command\Environment\EnvironmentCurlCommand();
        $commands[] = new Command\Environment\EnvironmentDeleteCommand();
        $commands[] = new Command\Environment\EnvironmentDeployCommand();
        $commands[] = new Command\Environment\EnvironmentDeployTypeCommand();
        $commands[] = new Command\Environment\EnvironmentDrushCommand();
        $commands[] = new Command\Environment\EnvironmentHttpAccessCommand();
        $commands[] = new Command\Environment\EnvironmentListCommand();
        $commands[] = new Command\Environment\EnvironmentLogCommand();
        $commands[] = new Command\Environment\EnvironmentInfoCommand();
        $commands[] = new Command\Environment\EnvironmentInitCommand();
        $commands[] = new Command\Environment\EnvironmentMergeCommand();
        $commands[] = new Command\Environment\EnvironmentPauseCommand();
        $commands[] = new Command\Environment\EnvironmentPushCommand();
        $commands[] = new Command\Environment\EnvironmentRedeployCommand();
        $commands[] = new Command\Environment\EnvironmentRelationshipsCommand();
        $commands[] = new Command\Environment\EnvironmentResumeCommand();
        $commands[] = new Command\Environment\EnvironmentSshCommand();
        $commands[] = new Command\Environment\EnvironmentScpCommand();
        $commands[] = new Command\Environment\EnvironmentSynchronizeCommand();
        $commands[] = new Command\Environment\EnvironmentUrlCommand();
        $commands[] = new Command\Environment\EnvironmentSetRemoteCommand();
        $commands[] = new Command\Environment\EnvironmentXdebugCommand();
        $commands[] = new Command\Integration\IntegrationAddCommand();
        $commands[] = new Command\Integration\IntegrationDeleteCommand();
        $commands[] = new Command\Integration\IntegrationGetCommand();
        $commands[] = new Command\Integration\IntegrationListCommand();
        $commands[] = new Command\Integration\IntegrationUpdateCommand();
        $commands[] = new Command\Integration\IntegrationValidateCommand();
        $commands[] = new Command\Integration\Activity\IntegrationActivityGetCommand();
        $commands[] = new Command\Integration\Activity\IntegrationActivityListCommand();
        $commands[] = new Command\Integration\Activity\IntegrationActivityLogCommand();
        $commands[] = new Command\Local\LocalBuildCommand();
        $commands[] = new Command\Local\LocalCleanCommand();
        $commands[] = new Command\Local\LocalDrushAliasesCommand();
        $commands[] = new Command\Local\LocalDirCommand();
        $commands[] = new Command\Mount\MountListCommand();
        $commands[] = new Command\Mount\MountDownloadCommand();
        $commands[] = new Command\Mount\MountSizeCommand();
        $commands[] = new Command\Mount\MountUploadCommand();
        $commands[] = new Command\Organization\OrganizationCreateCommand();
        $commands[] = new Command\Organization\OrganizationCurlCommand();
        $commands[] = new Command\Organization\OrganizationDeleteCommand();
        $commands[] = new Command\Organization\OrganizationInfoCommand();
        $commands[] = new Command\Organization\OrganizationListCommand();
        $commands[] = new Command\Organization\OrganizationSubscriptionListCommand();
        $commands[] = new Command\Organization\Billing\OrganizationAddressCommand();
        $commands[] = new Command\Organization\Billing\OrganizationProfileCommand();
        $commands[] = new Command\Organization\User\OrganizationUserAddCommand();
        $commands[] = new Command\Organization\User\OrganizationUserDeleteCommand();
        $commands[] = new Command\Organization\User\OrganizationUserGetCommand();
        $commands[] = new Command\Organization\User\OrganizationUserListCommand();
        $commands[] = new Command\Organization\User\OrganizationUserProjectsCommand();
        $commands[] = new Command\Organization\User\OrganizationUserUpdateCommand();
        $commands[] = new Command\Metrics\AllMetricsCommand();
        $commands[] = new Command\Metrics\CpuCommand();
        $commands[] = new Command\Metrics\CurlCommand();
        $commands[] = new Command\Metrics\DiskUsageCommand();
        $commands[] = new Command\Metrics\MemCommand();
        $commands[] = new Command\Project\ProjectClearBuildCacheCommand();
        $commands[] = new Command\Project\ProjectCurlCommand();
        $commands[] = new Command\Project\ProjectCreateCommand();
        $commands[] = new Command\Project\ProjectDeleteCommand();
        $commands[] = new Command\Project\ProjectGetCommand();
        $commands[] = new Command\Project\ProjectListCommand();
        $commands[] = new Command\Project\ProjectInfoCommand();
        $commands[] = new Command\Project\ProjectSetRemoteCommand();
        $commands[] = new Command\Project\Variable\ProjectVariableDeleteCommand();
        $commands[] = new Command\Project\Variable\ProjectVariableGetCommand();
        $commands[] = new Command\Project\Variable\ProjectVariableSetCommand();
        $commands[] = new Command\Repo\CatCommand();
        $commands[] = new Command\Repo\LsCommand();
        $commands[] = new Command\Repo\ReadCommand();
        $commands[] = new Command\Route\RouteListCommand();
        $commands[] = new Command\Route\RouteGetCommand();
        $commands[] = new Command\Self\SelfBuildCommand();
        $commands[] = new Command\Self\SelfConfigCommand();
        $commands[] = new Command\Self\SelfInstallCommand();
        $commands[] = new Command\Self\SelfUpdateCommand();
        $commands[] = new Command\Self\SelfReleaseCommand();
        $commands[] = new Command\Self\SelfStatsCommand();
        $commands[] = new Command\Server\ServerRunCommand();
        $commands[] = new Command\Server\ServerStartCommand();
        $commands[] = new Command\Server\ServerListCommand();
        $commands[] = new Command\Server\ServerStopCommand();
        $commands[] = new Command\Service\MongoDB\MongoDumpCommand();
        $commands[] = new Command\Service\MongoDB\MongoExportCommand();
        $commands[] = new Command\Service\MongoDB\MongoRestoreCommand();
        $commands[] = new Command\Service\MongoDB\MongoShellCommand();
        $commands[] = new Command\Service\RedisCliCommand();
        $commands[] = new Command\Service\ServiceListCommand();
        $commands[] = new Command\Service\ValkeyCliCommand();
        $commands[] = new Command\Session\SessionSwitchCommand();
        $commands[] = new Command\Backup\BackupCreateCommand();
        $commands[] = new Command\Backup\BackupDeleteCommand();
        $commands[] = new Command\Backup\BackupGetCommand();
        $commands[] = new Command\Backup\BackupListCommand();
        $commands[] = new Command\Backup\BackupRestoreCommand();
        $commands[] = new Command\Resources\ResourcesGetCommand();
        $commands[] = new Command\Resources\ResourcesSizeListCommand();
        $commands[] = new Command\Resources\ResourcesSetCommand();
        $commands[] = new Command\Resources\Build\BuildResourcesGetCommand();
        $commands[] = new Command\Resources\Build\BuildResourcesSetCommand();
        $commands[] = new Command\RuntimeOperation\ListCommand();
        $commands[] = new Command\RuntimeOperation\RunCommand();
        $commands[] = new Command\SourceOperation\ListCommand();
        $commands[] = new Command\SourceOperation\RunCommand();
        $commands[] = new Command\SshCert\SshCertInfoCommand();
        $commands[] = new Command\SshCert\SshCertLoadCommand();
        $commands[] = new Command\SshKey\SshKeyAddCommand();
        $commands[] = new Command\SshKey\SshKeyDeleteCommand();
        $commands[] = new Command\SshKey\SshKeyListCommand();
        $commands[] = new Command\SubscriptionInfoCommand();
        $commands[] = new Command\Team\TeamCreateCommand();
        $commands[] = new Command\Team\TeamDeleteCommand();
        $commands[] = new Command\Team\TeamGetCommand();
        $commands[] = new Command\Team\TeamListCommand();
        $commands[] = new Command\Team\TeamUpdateCommand();
        $commands[] = new Command\Team\Project\TeamProjectAddCommand();
        $commands[] = new Command\Team\Project\TeamProjectDeleteCommand();
        $commands[] = new Command\Team\Project\TeamProjectListCommand();
        $commands[] = new Command\Team\User\TeamUserAddCommand();
        $commands[] = new Command\Team\User\TeamUserDeleteCommand();
        $commands[] = new Command\Team\User\TeamUserListCommand();
        $commands[] = new Command\Tunnel\TunnelCloseCommand();
        $commands[] = new Command\Tunnel\TunnelInfoCommand();
        $commands[] = new Command\Tunnel\TunnelListCommand();
        $commands[] = new Command\Tunnel\TunnelOpenCommand();
        $commands[] = new Command\Tunnel\TunnelSingleCommand();
        $commands[] = new Command\User\UserAddCommand();
        $commands[] = new Command\User\UserDeleteCommand();
        $commands[] = new Command\User\UserListCommand();
        $commands[] = new Command\User\UserGetCommand();
        $commands[] = new Command\User\UserUpdateCommand();
        $commands[] = new Command\Variable\VariableCreateCommand();
        $commands[] = new Command\Variable\VariableDeleteCommand();
        $commands[] = new Command\Variable\VariableDisableCommand();
        $commands[] = new Command\Variable\VariableEnableCommand();
        $commands[] = new Command\Variable\VariableGetCommand();
        $commands[] = new Command\Variable\VariableListCommand();
        $commands[] = new Command\Variable\VariableSetCommand();
        $commands[] = new Command\Variable\VariableUpdateCommand();
        $commands[] = new Command\Version\VersionListCommand();
        $commands[] = new Command\WelcomeCommand();
        $commands[] = new Command\WebConsoleCommand();
        $commands[] = new Command\WinkyCommand();
        $commands[] = new Command\Worker\WorkerListCommand();

        return $commands;
    }

    /**
     * @inheritdoc
     */
    public function getHelp()
    {
        $messages = [
            $this->getLongVersion(),
            '',
            '<comment>Global options:</comment>',
        ];

        foreach ($this->getDefinition()
                      ->getOptions() as $option) {
            $messages[] = sprintf(
                '  %-29s %s %s',
                '<info>--' . $option->getName() . '</info>',
                $option->getShortcut() ? '<info>-' . $option->getShortcut() . '</info>' : '  ',
                $option->getDescription()
            );
        }

        return implode(PHP_EOL, $messages);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        // Allow the NO_COLOR, CLICOLOR_FORCE, and TERM environment variables to
        // override whether colors are used in the output.
        // See: https://no-color.org
        // See: https://en.wikipedia.org/wiki/Computer_terminal#Dumb_terminals
        /* @see StreamOutput::hasColorSupport() */
        if (getenv('CLICOLOR_FORCE') === '1') {
            $output->setDecorated(true);
        } elseif (getenv('NO_COLOR')
            || getenv('CLICOLOR_FORCE') === '0'
            || getenv('TERM') === 'dumb'
            || getenv($this->envPrefix . 'NO_COLOR')) {
            $output->setDecorated(false);
        }

        if ($input->hasParameterOption('--ansi', true)) {
            $output->setDecorated(true);
        } elseif ($input->hasParameterOption('--no-ansi', true)) {
            $output->setDecorated(false);
        }

        $stdErr = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        if ($input->hasParameterOption(['--yes', '-y', '--no-interaction', '-n', '--no'], true)
            || getenv($this->envPrefix . 'NO_INTERACTION')) {
            $input->setInteractive(false);

            // Deprecate the -n flag as a shortcut for --no.
            // It is confusing as it's a shortcut for --no-interaction in other Symfony Console commands.
            if ($input->hasParameterOption('-n', true)) {
                $stdErr->writeln('<options=reverse>DEPRECATED</> The -n flag (as a shortcut for --no) is deprecated. It will be removed or changed in a future version.');
            }
        } elseif (\function_exists('posix_isatty')) {
            $inputStream = null;

            if ($input instanceof StreamableInputInterface) {
                $inputStream = $input->getStream();
            }

            if (!@posix_isatty($inputStream) && false === getenv('SHELL_INTERACTIVE')) {
                $input->setInteractive(false);
            }
        }

        switch ($shellVerbosity = (int) getenv('SHELL_VERBOSITY')) {
            case -1: $stdErr->setVerbosity(OutputInterface::VERBOSITY_QUIET); break;
            case 1: $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE); break;
            case 2: $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE); break;
            case 3: $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG); break;
            default: $shellVerbosity = 0; break;
        }

        if ($input->hasParameterOption('-vvv', true)
            || getenv('CLI_DEBUG') || getenv($this->envPrefix . 'DEBUG')) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            $shellVerbosity = 3;
        } elseif ($input->hasParameterOption('-vv', true)) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
            $shellVerbosity = 2;
        } elseif ($input->hasParameterOption(['-v', '--verbose'], true)) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
            $shellVerbosity = 1;
        } elseif ($input->hasParameterOption(['--quiet', '-q'], true)) {
            $stdErr->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $input->setInteractive(false);
            $shellVerbosity = -1;
        }

        putenv('SHELL_VERBOSITY='.$shellVerbosity);
        $_ENV['SHELL_VERBOSITY'] = $shellVerbosity;
        $_SERVER['SHELL_VERBOSITY'] = $shellVerbosity;

        // Turn off error reporting in quiet mode.
        if ($shellVerbosity === -1) {
            error_reporting(false);
            ini_set('display_errors', '0');
        } else {
            // Display errors by default. In verbose mode, display all PHP
            // error levels except deprecations. Deprecations will only be
            // displayed while in debug mode and only if this is enabled via
            // the CLI_REPORT_DEPRECATIONS environment variable.
            $error_level = ($shellVerbosity >= 1 ? E_ALL : E_PARSE | E_ERROR) & ~E_DEPRECATED;
            $report_deprecations = getenv('CLI_REPORT_DEPRECATIONS') || getenv($this->envPrefix . 'REPORT_DEPRECATIONS');
            if ($report_deprecations && $shellVerbosity >= 3) {
                $error_level |= E_DEPRECATED;
            }
            error_reporting($error_level);
            ini_set('display_errors', 'stderr');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(ConsoleCommand $command, InputInterface $input, OutputInterface $output)
    {
        $this->setCurrentCommand($command);
        if ($command instanceof MultiAwareInterface) {
            $command->setRunningViaMulti($this->runningViaMulti);
        }

        // Build the command synopsis early, so it doesn't include default
        // options and arguments (such as --help and <command>).
        // @todo find a better solution for this?
        $this->currentCommand->getSynopsis();

        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * Set the current command. This is used for error handling.
     *
     * @param ConsoleCommand|null $command
     */
    public function setCurrentCommand(ConsoleCommand $command = null)
    {
        // The parent class has a similar (private) property named
        // $runningCommand.
        $this->currentCommand = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function renderException(\Exception $e, OutputInterface $output)
    {
        $output->writeln('', OutputInterface::VERBOSITY_QUIET);
        $main = $e;

        do {
            $exceptionName = get_class($e);
            if (($pos = strrpos($exceptionName, '\\')) !== false) {
                $exceptionName = substr($exceptionName, $pos + 1);
            }
            $title = sprintf('  [%s]  ', $exceptionName);

            $len = strlen($title);

            $width = (new Terminal())->getWidth() - 1;
            $formatter = $output->getFormatter();
            $lines = array();
            foreach (preg_split('/\r?\n/', $e->getMessage()) as $line) {
                foreach (str_split($line, $width - 4) as $chunk) {
                    // pre-format lines to get the right string length
                    $lineLength = strlen(preg_replace('/\[[^m]*m/', '', $formatter->format($chunk))) + 4;
                    $lines[] = array($chunk, $lineLength);

                    $len = max($lineLength, $len);
                }
            }

            $messages = array();
            $messages[] = $emptyLine = $formatter->format(sprintf('<error>%s</error>', str_repeat(' ', $len)));
            $messages[] = $formatter->format(sprintf('<error>%s%s</error>', $title, str_repeat(' ', max(0, $len - strlen($title)))));
            foreach ($lines as $line) {
                $messages[] = $formatter->format(sprintf('<error>  %s  %s</error>', $line[0], str_repeat(' ', $len - $line[1])));
            }
            $messages[] = $emptyLine;
            $messages[] = '';

            $output->writeln($messages, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);

            if ($output->isDebug()) {
                $output->writeln('<comment>Exception trace:</comment>', OutputInterface::VERBOSITY_QUIET);

                // exception related properties
                $trace = $e->getTrace();
                array_unshift($trace, array(
                    'function' => '',
                    'file' => $e->getFile() !== null ? $e->getFile() : 'n/a',
                    'line' => $e->getLine() !== null ? $e->getLine() : 'n/a',
                    'args' => array(),
                ));

                for ($i = 0, $count = count($trace); $i < $count; ++$i) {
                    $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
                    $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
                    $function = $trace[$i]['function'];
                    $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
                    $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

                    $output->writeln(sprintf(' %s%s%s() at <info>%s:%s</info>', $class, $type, $function, $file, $line), OutputInterface::VERBOSITY_QUIET);
                }

                $output->writeln('', OutputInterface::VERBOSITY_QUIET);
            }
        } while (($c = $e) && ($e = $e->getPrevious()) && $e->getMessage() !== $c->getMessage());

        if (isset($this->currentCommand)
            && $this->currentCommand->getName() !== 'welcome'
            && ($main instanceof ConsoleInvalidArgumentException
                || $main instanceof ConsoleInvalidOptionException
                || $main instanceof ConsoleRuntimeException
            )) {
            $output->writeln(
                sprintf('Usage: <info>%s</info>', $this->currentCommand->getSynopsis()),
                OutputInterface::VERBOSITY_QUIET
            );
            $output->writeln('', OutputInterface::VERBOSITY_QUIET);
            $output->writeln(sprintf(
                'For more information, type: <info>%s help %s</info>',
                $this->cliConfig->get('application.executable'),
                $this->currentCommand->getName()
            ), OutputInterface::VERBOSITY_QUIET);
            $output->writeln('', OutputInterface::VERBOSITY_QUIET);
        }
    }

    public function setRunningViaMulti()
    {
        $this->runningViaMulti = true;
    }

    public function getLongVersion()
    {
        // Show "(legacy)" in the version output, if not wrapped.
        if (!$this->cliConfig->isWrapped() && $this->cliConfig->get('application.mark_unwrapped_legacy')) {
            return sprintf('%s (legacy) <info>%s</info>', $this->cliConfig->get('application.name'), $this->cliConfig->getVersion());
        }
        return sprintf('%s <info>%s</info>', $this->cliConfig->get('application.name'), $this->cliConfig->getVersion());
    }
}
