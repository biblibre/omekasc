<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserSetPasswordCommand extends Command
{
	protected static $defaultName = 'SetPassword';

	protected function configure()
	{
		$this->setDescription('Set a new password');
		$this->addArgument('email', InputArgument::REQUIRED);
		$this->addArgument('newPassword', InputArgument::REQUIRED);
		$this->addArgument('confirmPassword', InputArgument::REQUIRED);
	}


	protected function execute(InputInterface $input, OutputInterface $output) {
		$omekaHelper = $this->getHelper('omeka');
		$application = $omekaHelper->getApplication();
		$services = $application->getServiceManager();
		$entityManager = $services->get('Omeka\EntityManager');
		$userRepository = $entityManager->getRepository('Omeka\Entity\User');
		$userEmail = $input->getArgument('email');
		$newPassword = $input->getArgument('newPassword');
		$confirmPassword = $input->getArgument('confirmPassword');

		$userTarget = $userRepository->findOneBy(['email'=>$userEmail]);

		if (empty($userTarget)) {
			$output->writeln(sprintf("User does not exist " .  $userEmail));
			return Command::FAILURE;
		}

		$userTarget->setPassword($newPassword);

			if ($confirmPassword != $newPassword){
				$output->writeln(sprintf("Passwords must be the same"));
			return Command::FAILURE;
		}	

		$entityManager->persist($userTarget);
		$entityManager->flush();	
		$output->writeln(sprintf("Password has been changed for " . $userEmail));	
		return Command::SUCCESS;
		

	}
}
?>

