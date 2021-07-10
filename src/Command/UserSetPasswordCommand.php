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
		$this->addOption('email','',  InputOption::VALUE_REQUIRED);
		$this->addOption('newPassword','', InputOption::VALUE_REQUIRED);
		$this->addOption('confirmPassword','', InputOption::VALUE_REQUIRED);
	}

	protected function interact(InputInterface $input, OutputInterface $output)
	{
		$io= new SymfonyStyle($input, $output);
		$io= new SymfonyStyle($input, $output);

		$userEmail = $input->getOption('email');
		if(null === $userEmail) {
			$userEmail= $io->ask('Enter the user\'s email', $userEmail, function ($userEmail ){
				return ($userEmail);
			});
			$input->setOption('email', $userEmail);
		}	


	}


	protected function execute(InputInterface $input, OutputInterface $output) {
		$omekaHelper = $this->getHelper('omeka');
		$application = $omekaHelper->getApplication();
		$services = $application->getServiceManager();
		$entityManager = $services->get('Omeka\EntityManager');
		$userRepository = $entityManager->getRepository('Omeka\Entity\User');

		$io = new SymfonyStyle($input, $output);

		try {
			$userTarget=$userRepository->findOneBy(['email'=>$input->getOption('email')]);
		} catch(\Exception $e){ 
			$io->error($e->getMessage());
			return Command::FAILURE;
		}

		if (empty($userTarget)) {
			$io->error('User does not exist ' .  $userEmail);
			return Command::FAILURE;
		}

		if($userTarget){

			$newPassword=$input->getOption('newPassword');
			if(null === $newPassword){
				$newPassword=$io->askHidden('Enter the new password',$newPassword, function($newPassword){ 
					return ($newPassword);
				});

				$input->setOption('newPassword', $newPassword);
				$confirmPassword = $io->askHidden('Confirm the password', null, function($confirmPassword) {
					return ($confirmPassword);
				});

			}

			if ($newPassword === $confirmPassword) {

				$userTarget->setPassword($newPassword);
				$entityManager->persist($userTarget);
				$entityManager->flush();	
				$io->success("Password has been changed");	
				return Command::SUCCESS;
			}

			$io->error("Passwords must be the same");
			return Command::FAILURE;
		}

	}
}
?>

