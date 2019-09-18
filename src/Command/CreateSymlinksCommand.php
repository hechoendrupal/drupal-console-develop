<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\CreateSymlinksCommand.
 */

namespace Drupal\Console\Develop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class CreateSymlinksCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */
class CreateSymlinksCommand extends Command
{
    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $packages = [];

    /**
     * ContributeCommand constructor.
     *
     * @param $consoleRoot
     * @param configurationManager $configurationManager
     */
    public function __construct(
        $consoleRoot,
        ConfigurationManager $configurationManager
    ) {
        $this->consoleRoot = $consoleRoot;
        $this->configurationManager = $configurationManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('develop:create:symlinks')
            ->setDescription($this->trans('commands.develop.create.symlinks.description'))
            ->addOption(
                'code-directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.develop.create.symlinks.options.code-directory')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $io->newLine();
        $io->comment(
            trim($this->trans('commands.develop.create.symlinks.messages.info')),
            false
        );

        $codeDirectory = $input->getOption('code-directory');
        if (!$codeDirectory) {
            $codeDirectory = $io->ask(
                $this->trans('commands.develop.create.symlinks.questions.code-directory')
            );
            $input->setOption('code-directory', $codeDirectory);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $codeDirectory = $input->getOption('code-directory');
        if (!$codeDirectory) {
            $io->error(
                $this->trans('commands.develop.create.symlinks.messages.no-directory')
            );
        }
        $codeDirectory = $str = rtrim($codeDirectory, '/');

        // Convert the alias for home directory into the real path i.e. ~/dev/drupal-console.
        if (substr($codeDirectory, 0, 2) == '~/') {
            $codeDirectory = getenv('HOME') . '/' . substr($codeDirectory, 2, strlen($codeDirectory));
        }

        $io->writeln(
            $this->trans('commands.develop.create.symlinks.messages.symlink')
        );

        $this->packages = $this->populatePackages($codeDirectory);
        $sitePackages = $this->populatePackages($this->consoleRoot . '/vendor/drupal');

        foreach ($this->packages as $name => $package) {

            $projectDirectory = $package['path'] . '/' . $package['directory'];
            if (isset($sitePackages[$name])) {
                $packageDirectory = $sitePackages[$name]['path'] . '/' . $sitePackages[$name]['directory'];

                $this->symlinkDirectory(
                    $io,
                    $projectDirectory,
                    $packageDirectory
                );
            }
        }

        $packages = reset($this->packages);
        $autoloadDistOriginal = $codeDirectory.'/'.$packages['directory'].'/autoload.local.php.dist';
        $autoloadDistLocal = $codeDirectory.'/'.$packages['directory'].'/autoload.local.php';
        $this->copyAutoloadFile(
            $io,
            $autoloadDistOriginal,
            $autoloadDistLocal
        );
    }

    /**
     * Finds all drupal console packages on the code directory.
     *
     * @param string $directory
     *
     * @return array List of packages.
     */
    protected function populatePackages($directory) {
        $packages = array();
        try {
            $finder = new Finder();
            $finder->files()
                ->in($directory)
                ->name('composer.json')
                ->contains('drupal/')
                ->depth('== 1')
                ->ignoreUnreadableDirs();

            foreach ($finder as $file) {
                $contents = json_decode($file->getContents());
                $packages[$contents->name]['path'] = $directory;
                $packages[$contents->name]['directory'] = $file->getRelativePath();
            }
        } catch (\Exception $e) {
            // Ommitting erros for now.
        }

        return $packages;
    }

    /**
     * @param DrupalStyle   $io
     * @param string        $projectDirectory
     * @param string        $packageDirectory
     */
    protected function symlinkDirectory(
        DrupalStyle $io,
        $projectDirectory,
        $packageDirectory
    ) {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists([$projectDirectory])) {
            if ($fileSystem->exists([$packageDirectory])) {
                $fileSystem->remove($packageDirectory);
            }
            $fileSystem->symlink(
                $projectDirectory,
                $packageDirectory
            );
            $io->info(
                rtrim(
                    $fileSystem->makePathRelative(
                        $packageDirectory,
                        $this->consoleRoot
                    ),
                    '/'
                ) . ' => ',
                    FALSE
            );
            $io->writeln(
                rtrim(
                    $fileSystem->makePathRelative(
                        $projectDirectory,
                        $this->consoleRoot
                    ),
                    '/'
                )
            );
        }
    }

    /**
     * @param DrupalStyle   $io
     * @param string        $autoloadDistOriginal
     * @param string        $autoloadDistLocal
     */
    protected function copyAutoloadFile(
        DrupalStyle $io,
        $autoloadDistOriginal,
        $autoloadDistLocal
    ) {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($autoloadDistOriginal) && !$fileSystem->exists($autoloadDistLocal)) {
            $io->writeln(
                sprintf(
                    $this->trans('commands.develop.create.symlinks.messages.copy'),
                    $fileSystem->makePathRelative(
                        $autoloadDistOriginal,
                        $this->consoleRoot
                    ),
                    $fileSystem->makePathRelative(
                        $autoloadDistLocal,
                        $this->consoleRoot
                    )
                )
            );
            $fileSystem->copy(
                $autoloadDistOriginal,
                $autoloadDistLocal
            );
        }
    }
}
