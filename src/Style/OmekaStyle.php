<?php

namespace Omeka\Console\Style;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\OutputStyle;

class OmekaStyle extends OutputStyle
{
    private $input;
    private $questionHelper;
    private $progressBar;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        parent::__construct($output);
    }

    /**
     * {@inheritdoc}
     */
    public function title(string $message)
    {
        $this->writeln(sprintf('<comment>%s</>', OutputFormatter::escapeTrailingBackslash($message)));
        $this->writeln(sprintf('<comment>%s</>', str_repeat('=', Helper::strlenWithoutDecoration($this->getFormatter(), $message))));
        $this->newLine();
    }

    /**
     * {@inheritdoc}
     */
    public function section(string $message)
    {
        $this->writeln(sprintf('<comment>%s</>', OutputFormatter::escapeTrailingBackslash($message)));
        $this->writeln(sprintf('<comment>%s</>', str_repeat('-', Helper::strlenWithoutDecoration($this->getFormatter(), $message))));
        $this->newLine();
    }

    /**
     * {@inheritdoc}
     */
    public function listing(array $elements)
    {
        $elements = array_map(function ($element) {
            return sprintf(' * %s', $element);
        }, $elements);

        $this->writeln($elements);
        $this->newLine();
    }

    /**
     * {@inheritdoc}
     */
    public function text($message)
    {
        $this->writeln($message);
    }

    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->writeln($this->createMessage($message, 'OK', 'fg=green'));
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->writeln($this->createMessage($message, 'ERROR', 'fg=red'));
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->writeln($this->createMessage($message, 'WARNING', 'fg=yellow'));
    }

    /**
     * {@inheritdoc}
     */
    public function note($message)
    {
        $this->writeln($this->createMessage($message, 'NOTE', 'fg=blue'));
    }

    public function info($message)
    {
        $this->writeln($this->createMessage($message, 'INFO', null));
    }

    /**
     * {@inheritdoc}
     */
    public function caution($message)
    {
        $this->writeln($this->createMessage($message, 'CAUTION', 'fg=yellow'));
    }

    /**
     * {@inheritdoc}
     */
    public function table(array $headers, array $rows)
    {
        $style = clone Table::getStyleDefinition('symfony-style-guide');
        $style->setCellHeaderFormat('<info>%s</info>');

        $table = new Table($this);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->setStyle($style);

        $table->render();
        $this->newLine();
    }

    /**
     * {@inheritdoc}
     */
    public function ask(string $question, ?string $default = null, $validator = null)
    {
        $question = new Question($question, $default);
        $question->setValidator($validator);

        return $this->askQuestion($question);
    }

    /**
     * {@inheritdoc}
     */
    public function askHidden(string $question, $validator = null)
    {
        $question = new Question($question);

        $question->setHidden(true);
        $question->setValidator($validator);

        return $this->askQuestion($question);
    }

    /**
     * {@inheritdoc}
     */
    public function confirm($question, $default = true)
    {
        return $this->askQuestion(new ConfirmationQuestion($question, $default));
    }

    /**
     * {@inheritdoc}
     */
    public function choice(string $question, array $choices, $default = null)
    {
        if (null !== $default) {
            $values = array_flip($choices);
            $default = $values[$default] ?? $default;
        }

        return $this->askQuestion(new ChoiceQuestion($question, $choices, $default));
    }

    /**
     * {@inheritdoc}
     */
    public function progressStart(int $max = 0)
    {
        $this->progressBar = $this->createProgressBar($max);
        $this->progressBar->start();
    }

    /**
     * {@inheritdoc}
     */
    public function progressAdvance(int $step = 1)
    {
        $this->getProgressBar()->advance($step);
    }

    /**
     * {@inheritdoc}
     */
    public function progressFinish()
    {
        $this->getProgressBar()->finish();
        $this->newLine(2);
        $this->progressBar = null;
    }

    /**
     * @return mixed
     */
    public function askQuestion(Question $question)
    {
        if (!$this->questionHelper) {
            $this->questionHelper = new SymfonyQuestionHelper();
        }

        $answer = $this->questionHelper->ask($this->input, $this, $question);

        if ($this->input->isInteractive()) {
            $this->newLine();
        }

        return $answer;
    }

    /**
     * Returns a new instance which makes use of stderr if available.
     *
     * @return self
     */
    public function getErrorStyle()
    {
        return new self($this->input, $this->getErrorOutput());
    }

    private function getProgressBar(): ProgressBar
    {
        if (!$this->progressBar) {
            throw new RuntimeException('The ProgressBar is not started.');
        }

        return $this->progressBar;
    }

    private function createMessage(string|iterable $message, ?string $type = null, ?string $style = null, bool $escape = true): array
    {
        $message = !is_iterable($message) ? [$message] : $message;

        $messages = [];
        foreach ($message as $msg) {
            if ($type !== null) {
                $msg = sprintf('[%s] %s', $type, $msg);
            }

            if ($style !== null) {
                $msg = sprintf('<%s>%s</>', $style, $escape ? OutputFormatter::escape($msg) : $msg);
            }

            $messages[] = $msg;
        }

        return $messages;
    }
}
