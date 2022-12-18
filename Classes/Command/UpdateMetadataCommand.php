<?php

declare(strict_types=1);

namespace Ayacoo\Twitch\Command;

use Ayacoo\Twitch\Domain\Repository\FileRepository;
use Ayacoo\Twitch\Helper\TwitchHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdateMetadataCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Updates the Twitch metadata');
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_OPTIONAL,
            'Defines the number of Twitch videos to be checked',
            10
        );
    }

    public function __construct(
        protected FileRepository     $fileRepository,
        protected MetaDataRepository $metadataRepository,
        protected ResourceFactory    $resourceFactory
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int)($input->getOption('limit') ?? 10);

        $twitchHelper = GeneralUtility::makeInstance(TwitchHelper::class, 'twitch');

        $videos = $this->fileRepository->getVideosByFileExtension('twitch', $limit);
        foreach ($videos as $video) {
            $file = $this->resourceFactory->getFileObject($video['uid']);
            $metaData = $twitchHelper->getMetaData($file);
            if (!empty($metaData)) {
                $this->metadataRepository->update($file->getUid(), [
                    'width' => (int)$metaData['width'],
                    'height' => (int)$metaData['height'],
                    'title' => $metaData['title'] ?? '',
                    'twitch_thumbnail' => $metaData['twitch_thumbnail'],
                ]);
                $io->success($file->getProperty('title') . '(UID: ' . $file->getUid() . ') was processed');
            }
        }

        return Command::SUCCESS;
    }
}
