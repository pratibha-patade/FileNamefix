<?php

/**
 * Display the current version of Arcanist.
 */
final class ArcanistVersionWorkflow extends ArcanistWorkflow {

  public function getWorkflowName() {
    return 'version';
  }

  public function getCommandSynopses() {
    return phutil_console_format(<<<EOTEXT
      **version** [__options__]
EOTEXT
      );
  }

  public function getCommandHelp() {
    return phutil_console_format(pht(<<<EOTEXT
          Supports: cli
          Shows the current version of arcanist.
EOTEXT
      ));
  }

  public function run() {
    $console = PhutilConsole::getConsole();

    if (!Filesystem::binaryExists('git')) {
      throw new ArcanistUsageException(
        pht(
          'Cannot display current version without having `%s` installed.',
          'git'));
    }

    $roots = array(
      'arcanist' => dirname(phutil_get_library_root('arcanist')),
      'libphutil' => dirname(phutil_get_library_root('phutil')),
    );

    foreach ($roots as $lib => $root) {
      $working_copy = ArcanistWorkingCopyIdentity::newFromPath($root);
      $configuration_manager = clone $this->getConfigurationManager();
      $configuration_manager->setWorkingCopyIdentity($working_copy);
      $repository = ArcanistRepositoryAPI::newAPIFromConfigurationManager(
        $configuration_manager);

      if (!Filesystem::pathExists($repository->getMetadataPath())) {
        throw new ArcanistUsageException(
          pht('%s is not a git working copy.', $lib));
      }

      // NOTE: Carefully execute these commands in a way that works on Windows
      // until T8298 is properly fixed. See PHI52.

      list($commit) = $repository->execxLocal('log -1 --format=%%H');
      $commit = trim($commit);

      list($timestamp) = $repository->execxLocal('log -1 --format=%%ct');
      $timestamp = trim($timestamp);

      $console->writeOut(
        "%s %s (%s)\n",
        $lib,
        $commit,
        date('j M Y', (int)$timestamp));
    }
  }

}
