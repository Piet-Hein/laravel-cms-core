<?php
namespace Czim\CmsCore\Console\Commands;

use Illuminate\Database\Console\Migrations\ResetCommand;

class MigrateResetCommand extends ResetCommand
{
    use CmsMigrationContextTrait;

    protected $name = 'cms:migrate:reset';
    protected $description = 'Rollback all CMS database migrations';

    /**
     * Overridden for setConnection only.
     *
     * {@inheritdoc}
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->migrator->setConnection($this->determineConnection());

        if (! $this->migrator->repositoryExists()) {
            $this->output->writeln('<comment>Migration table not found.</comment>');

            return;
        }

        $pretend = $this->input->getOption('pretend');

        $this->migrator->reset($pretend);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }
    }

}